<?php


    namespace WpEloquent;

    use Closure;
    use DateTime;
    use Exception;
    use Generator;
    use Illuminate\Database\Grammar;
    use Illuminate\Database\Query\Builder as QueryBuilder;
    use Illuminate\Database\Query\Expression;
    use Illuminate\Database\Query\Grammars\MySqlGrammar as MySqlQueryGrammar;
    use Illuminate\Database\Query\Processors\MySqlProcessor;
    use Illuminate\Database\QueryException;
    use Illuminate\Support\Arr;
    use Illuminate\Support\Str;
    use Throwable;
    use WpEloquent\ExtendsWpdb\WpdbInterface;
    use WpEloquent\Traits\DetectsConcurrencyErrors;
    use WpEloquent\Traits\LogsQueries;
    use WpEloquent\Traits\ManagesTransactions;

    class WpConnection implements WpConnectionInterface
    {

        use LogsQueries;
        use ManagesTransactions;

        /**e
         * The active wpdb connection
         *
         * @var WpdbInterface
         */
        protected $wpdb;

        /**
         * The name of the connected database.
         *
         * @var string
         */
        protected $db_name;

        /**
         * The table prefix for the connection.
         *
         * @var string
         */
        protected $table_prefix = '';

        /**
         * The database connection configuration options.
         *
         * @var array
         */
        protected $config = [];

        /**
         * The query grammar implementation.
         *
         * @var MySqlQueryGrammar
         */
        protected $query_grammar;

        /**
         * The schema grammar implementation.
         *
         * @var MySqlSchemaGrammar
         */
        protected $schema_grammar;

        /**
         * The query post processor implementation.
         *
         * @var MySqlProcessor
         */
        protected $post_processor;


        /**
         * All of the queries run against the connection.
         *
         * @var array
         */
        protected $query_log = [];

        /**
         * Indicates whether queries are being logged.
         *
         * @var bool
         */
        protected $logging_queries = false;

        /**
         * Indicates if the connection is in a "dry run".
         *
         * @var bool
         */
        protected $pretending = false;


        /**
         * The number of active transactions.
         *
         * @var int
         */
        protected $transaction_count = 0;


        /**
         * @var WpDbPdoAdapter
         */
        private $wpdb_to_pdo_adapter;


        /**
         * Create a new database connection instance.
         *
         * @param  WpdbInterface  $wpdb
         */
        public function __construct(WpdbInterface $wpdb)
        {

            $this->wpdb = $wpdb;

            $this->db_name = DB_NAME;

            $this->table_prefix = $wpdb->prefix;

            $this->query_grammar = $this->withTablePrefix(new MySqlQueryGrammar);

            $this->schema_grammar = $this->withTablePrefix(new MySqlSchemaGrammar());

            $this->post_processor = new MySqlProcessor();

            $this->wpdb_to_pdo_adapter = new WpDbPdoAdapter($wpdb);


        }


        /**
         * Get the query grammar used by the connection.
         * The QueryGrammar is used to "translate" the QueryBuilder instance into raw
         * SQL
         *
         * @return MySqlQueryGrammar;
         */
        public function getQueryGrammar() : MySqlQueryGrammar
        {

            return $this->query_grammar;
        }


        /**
         * Get the query post processor used by the connection.
         *
         * @return MySqlProcessor
         */
        public function getPostProcessor() : MySqlProcessor
        {

            return $this->post_processor;
        }


        /**
         * Get the schema grammar used by the connection.
         *
         * @return MySqlSchemaGrammar
         */
        public function getSchemaGrammar() : MySqlSchemaGrammar
        {

            return $this->schema_grammar;
        }

        /**
         * Get a schema builder instance for the connection.
         *
         * @return MySqlSchemaBuilder
         */
        public function getSchemaBuilder() : MySqlSchemaBuilder
        {

            return new MySqlSchemaBuilder($this);

        }


        /**
         * Set the table prefix and return the grammar for
         * a QueryBuilder or SchemaBuilder
         *
         * @param  Grammar  $grammar
         *
         * @return Grammar
         */
        private function withTablePrefix(Grammar $grammar) : Grammar
        {

            $grammar->setTablePrefix($this->table_prefix);

            return $grammar;

        }



        /*
        |
        |
        |--------------------------------------------------------------------------
        | Sanitizing and preparing the data to be passed into wpdb
        |--------------------------------------------------------------------------
        |
        |
        | We have to do all the sanitization ourselves and cant rely on the wpdb
        | class.
        |
        |
        |
        */

        /**
         * Prepare the query bindings for execution.
         *
         * @param  array  $bindings
         *
         * @return array
         */
        public function prepareBindings(array $bindings) : array
        {

            foreach ($bindings as $key => $binding) {

                // Micro-optimization: check for scalar values before instances
                if (is_bool($binding)) {
                    $bindings[$key] = intval($binding);
                }
                elseif (is_scalar($binding)) {

                    continue;

                }
                elseif ($binding instanceof DateTime) {

                    // We need to transform all instances of the DateTime class into an actual
                    // date string. Each query grammar maintains its own date string format
                    // so we'll just ask the grammar for the format to get from the date.
                    $bindings[$key] = $binding->format($this->getQueryGrammar()->getDateFormat());

                }
            }

            return $bindings;

        }



        /*
        |
        |
        |--------------------------------------------------------------------------
        | Query methods defined in the ConnectionInterface
        |--------------------------------------------------------------------------
        |
        |
        | Here is where we have to do most of the work since we need to
        | process all queries through the active wpdb instance in order to not
        | open a new db connection.
        |
        |
        */

        /**
         * Begin a fluent query against a database table.
         *
         * @param  Closure| QueryBuilder |string  $table
         * @param  string|null  $as
         *
         * @return QueryBuilder
         */
        public function table($table, $as = null) : QueryBuilder
        {

            return $this->query()->from($table, $as);

        }


        /**
         * Get a new query builder instance.
         *
         * @return QueryBuilder
         */
        public function query() : QueryBuilder
        {

            return new QueryBuilder(
                $this, $this->getQueryGrammar(), $this->getPostProcessor()
            );

        }


        /**
         * Run a select statement and return a single result.
         *
         * @param  string  $query
         * @param  array  $bindings
         * @param  bool  $useReadPdo  , can be ignored.
         *
         * @return mixed
         */
        public function selectOne($query, $bindings = [], $useReadPdo = true)
        {

            return $this->runWpDB($query, $bindings, function ($query, $bindings ) {

                if ($this->pretending) {
                    return [];
                }

               return $this->wpdb->doSelectOne($query, $bindings);

            }

            );


        }


        /**
         * Run a select statement against the database and return a set of rows
         *
         * @param  string  $query
         * @param  array  $bindings
         * @param  bool  $useReadPdo
         *
         * @return array
         */
        public function select($query, $bindings = [], $useReadPdo = true) : array
        {

            return $this->runWpDB($query, $bindings, function ($query, $bindings) {

                if ($this->pretending) {
                    return [];
                }

                return $this->wpdb->doSelect($query, $bindings);


            }
            );

        }


        public function selectFromWriteConnection($query, $bindings = []) : array
        {

            return $this->select($query, $bindings, false);

        }


        /**
         * Run an insert statement against the database.
         *
         * @param  string  $query
         * @param  array  $bindings
         *
         * @return bool
         */
        public function insert($query, $bindings = []) : bool
        {

            return $this->statement($query, $bindings);

        }


        /**
         * Run an update statement against the database.
         *
         * @param  string  $query
         * @param  array  $bindings
         *
         * @return int
         */
        public function update($query, $bindings = []) : int
        {

            return $this->affectingStatement($query, $bindings);
        }

        /**
         * Run a delete statement against the database.
         *
         * @param  string  $query
         * @param  array  $bindings
         *
         * @return int
         */
        public function delete($query, $bindings = []) : int
        {

            return $this->affectingStatement($query, $bindings);

        }


        /**
         * Execute an SQL statement and return the boolean result.
         *
         * @param  string  $query
         * @param  array  $bindings
         *
         * @return bool
         */
        public function statement($query, $bindings = []) : bool
        {

            return $this->runWpDB($query, $bindings, function ($query, $bindings) {

                if ($this->pretending) {
                    return true;
                }

                return $this->wpdb->doStatement($query, $bindings);


            });


        }


        /**
         * Run an SQL statement and get the number of rows affected.
         *
         * @param  string  $query
         * @param  array  $bindings
         *
         * @return int
         */
        public function affectingStatement($query, $bindings = []) : int
        {

            return $this->runWpDB($query, $bindings, function ($query, $bindings) {

                if ($this->pretending) {
                    return 0;
                }

                return $this->wpdb->doAffectingStatement($query, $bindings);


            });

        }


        /**
         * Get a new raw query expression.
         *
         * @param  mixed  $value
         *
         * @return Expression
         */
        public function raw($value) : Expression
        {

            return new Expression($value);
        }


        /**
         * Run a raw, unprepared query against the mysqli connection.
         *
         * @param  string  $query
         *
         * @return bool
         */
        public function unprepared($query) : bool
        {

            return $this->runWpDB($query, [], function ($query) {

                if ($this->pretending) {
                    return true;
                }

                return $this->wpdb->doUnprepared($query);

            });


        }


        /**
         * Run a select statement against the database and returns a generator.
         * I dont believe that this is currently possible like it is with laravel,
         * since wpdb does not use PDO.
         * Every wpdb method seems to be iterating over the result array.
         *
         * @param  string  $query
         * @param  array  $bindings
         * @param  bool  $useReadPdo
         *
         * @return Generator
         */
        public function cursor($query, $bindings = [], $useReadPdo = true)
        {

            return $this->runWpDB($query, $bindings, function ($query, $bindings) {

                if ($this->pretending) {
                    return [];
                }

                return $this->wpdb->doCursorSelect($query, $bindings);


            }
            );


        }


        /**
         * Run a SQL statement through the wpdb class.
         *
         * @param  string  $query
         * @param  array  $bindings
         * @param  Closure  $callback
         *
         * @return mixed
         * @throws QueryException
         */
        public function runWpDB(string $query, array $bindings, Closure $callback)
        {

            // To execute the statement, we'll simply call the callback, which will actually
            // run the SQL against the wpdb class .
            try {


                $start = microtime(true);

                $result = $callback($query, $bindings = $this->prepareBindings($bindings));


                if ( $this->logging_queries ) {

                    $this->logQuery($query, $bindings, $this->getElapsedTime($start));

                }


                return $result;

            }
            catch (Exception $e) {

                throw new QueryException($query, $bindings, $e);

            }

        }


        private function isConcurrencyError( Throwable $e) : bool
        {

            $message = $e->getMessage();

            return Str::contains($message, [
                'Deadlock found when trying to get lock',
                'deadlock detected',
                'The database file is locked',
                'database is locked',
                'database table is locked',
                'A table in the database is locked',
                'has been chosen as the deadlock victim',
                'Lock wait timeout exceeded; try restarting transaction',
                'WSREP detected deadlock/conflict and aborted the transaction. Try restarting the transaction',
            ]);
        }





        /*
        |
        |
        |--------------------------------------------------------------------------
        | MISC getters and setters
        |--------------------------------------------------------------------------
        |
        |
        |
        |
        |
        |
        |
        */

        /**
         * Get an option from the configuration options.
         *
         * @param  string|null  $option
         *
         * @return mixed
         */
        public function getConfig($option = null)
        {

            return Arr::get($this->config, $option);
        }


        /**
         * Returns the name of the database connection.
         *
         * @return string
         */
        public function getDatabaseName() : string
        {

            return $this->db_name;

        }

        /**
         * Returns the name of the database connection.
         *
         * Redundant but required for Eloquent.
         *
         * @return string
         */
        public function getName() : string
        {

            return $this->getDatabaseName();

        }


        public function getTablePrefix() : string
        {

            return $this->table_prefix;

        }


        public function getPdo() : WpDbPdoAdapter
        {

            return $this->wpdb_to_pdo_adapter;

        }

    }