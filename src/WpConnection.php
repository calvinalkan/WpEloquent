<?php


    namespace WpEloquent;

    use Closure;
    use Illuminate\Database\ConnectionInterface;
    use Illuminate\Database\Grammar;
    use Illuminate\Database\Query\Builder as QueryBuilder;
    use Illuminate\Database\Query\Expression;
    use Illuminate\Database\Query\Grammars\MySqlGrammar as MySqlQueryGrammar;
    use Illuminate\Database\Query\Processors\MySqlProcessor;
    use Illuminate\Database\Schema\Grammars\MySqlGrammar as MySqlSchemaGrammar;
    use Illuminate\Database\Schema\MySqlBuilder as MySqlSchemaBuilder;
    use Illuminate\Support\Arr;
    use wpdb;

    class WpConnection implements ConnectionInterface
    {

        use InteractsWithWpDb;
        use LogsQueries;
        use ManagesTransactions;
        use DetectsConcurrencyErrors;

        /**e
         * The active wpdb connection
         *
         * @var wpdb
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
         * Create a new database connection instance.
         *
         * @param  wpdb  $pdo
         * @param  string  $db_name
         * @param  string  $table_prefix
         * @param  array  $config
         *
         * @return void
         */
        public function __construct(wpdb $wpdb, $db_name = '', $table_prefix = '', $config = [])
        {

            $this->wpdb = $wpdb;

            // First we will setup the default properties. We keep track of the DB
            // name we are connected to since it is needed when some reflective
            // type commands are run such as checking whether a table exists.
            $this->db_name = $db_name;

            $this->table_prefix = $table_prefix ?? $wpdb->prefix;

            $this->config = $config;

            // We need to initialize a query grammar and the query post processors
            // which are both very important parts of the database abstractions
            // so we initialize these to their default values while starting.
            $this->useDefaultQueryGrammar();

            $this->useDefaultPostProcessor();

        }


        public static function instance(wpdb $wpdb, $db_name = '', $table_prefix = '', $config = [])
        {

            static $instance = false;

            if ( ! $instance) {

                $instance = new self($wpdb, $db_name, $table_prefix, $config);
            }

            return $instance;

        }


        /*
        |
        |
        |--------------------------------------------------------------------------
        | Getters and Setters for the MySqlQueryGrammar
        |--------------------------------------------------------------------------
        |
        |
        | The QueryGrammar is used to "translate" the QueryBuilder instance into raw
        | SQL
        |
        |
        |
        |
        */

        /**
         * Set the query grammar to the default implementation.
         *
         * @return void
         */
        public function useDefaultQueryGrammar()
        {

            $this->query_grammar = $this->getDefaultQueryGrammar();
        }


        /**
         * Get the default query grammar instance.
         *
         * @return Grammar|MySqlQueryGrammar
         */
        protected function getDefaultQueryGrammar() : Grammar
        {

            return $this->withTablePrefix(new MySqlQueryGrammar);

        }

        /**
         * Get the query grammar used by the connection.
         *
         * @return MySqlQueryGrammar;
         */
        public function getQueryGrammar() : MySqlQueryGrammar
        {

            return $this->query_grammar;
        }


        /*
        |
        |
        |--------------------------------------------------------------------------
        |  Getters and Setters for the MySqlQueryPostProcessor
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
         * Set the query post processor to the default implementation.
         *
         * @return void
         */
        public function useDefaultPostProcessor()
        {

            $this->post_processor = $this->getDefaultPostProcessor();
        }


        /**
         * Get the default post processor instance.
         *
         * @return MySqlProcessor
         */
        protected function getDefaultPostProcessor() : MySqlProcessor
        {

            return new MySqlProcessor;
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


        /*
        |
        |
        |--------------------------------------------------------------------------
        | Getters and Setters for the MySqlSchemaGrammar
        |--------------------------------------------------------------------------
        |
        |
        | The SchemaGrammar is used for the SchemaBuilder that is necessary to
        | manipulate tables via the CLI
        |
        |
        |
        */

        /**
         * Set the schema grammar to the default implementation.
         *
         * @return void
         */
        public function useDefaultSchemaGrammar()
        {

            $this->schema_grammar = $this->getDefaultSchemaGrammar();
        }


        /**
         * Get the default schema grammar instance.
         *
         * @return Grammar|MySqlSchemaGrammar
         */
        protected function getDefaultSchemaGrammar() : Grammar
        {

            return $this->withTablePrefix(new MySqlSchemaGrammar);
        }


        /**
         * Get a schema builder instance for the connection.
         *
         * @return
         */
        public function getSchemaBuilder() : MySqlSchemaBuilder
        {

            if (is_null($this->schema_grammar)) {
                $this->useDefaultSchemaGrammar();
            }

            return new MySqlSchemaBuilder($this);
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
         * Set the table prefix and return the grammar for
         * a QueryBuilder or SchemaBuilder
         *
         * @param  Grammar  $grammar
         *
         * @return Grammar
         */
        public function withTablePrefix(Grammar $grammar) : Grammar
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
         * SQL escape the bindings and then vsprint them into the query placeholders
         *
         * @param $query
         * @param $bindings
         *
         * @return mixed
         */
        private function prepareQuery($query, $bindings)
        {

            $query = str_replace('"', '`', $query);

            $bindings = $this->prepareBindings($bindings);

            if ( ! $bindings) {
                return $query;
            }

            $bindings = array_map([$this, 'sanitizeSql'], $bindings);

            $query = str_replace(['%', '?'], ['%%', '%s'], $query);
            $query = vsprintf($query, $bindings);

            return $query;
        }


        /**
         * Here we escape any string values that might be passed from the query.
         * !important. esc_sql can only be used for values that are places inside backticks.
         * Users should NEVER EVER be allowed to dictate the columns, order or any order SQL
         * Keyword.
         *
         * @param $statement
         *
         * @return string|null
         */
        private function sanitizeSql($statement) : ?string
        {

            if (is_string($statement)) {

                $statement = "'".esc_sql($statement)."'";

            }

            return $statement ?? null;

        }


        /**
         * Prepare the query bindings for execution.
         *
         * @param  array  $bindings
         *
         * @return array
         */
        public function prepareBindings(array $bindings) : array
        {

            $grammar = $this->getQueryGrammar();

            foreach ($bindings as $key => $value) {

                // Micro-optimization: check for scalar values before instances
                if (is_bool($value)) {
                    $bindings[$key] = intval($value);
                }
                elseif (is_scalar($value)) {
                    continue;
                }
                elseif ($value instanceof \DateTime) {
                    // We need to transform all instances of the DateTime class into an actual
                    // date string. Each query grammar maintains its own date string format
                    // so we'll just ask the grammar for the format to get from the date.
                    $bindings[$key] = $value->format($grammar->getDateFormat());
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


            return $this->runWpDB($query, $bindings, function ($sql_query) {

                if ($this->pretending) {
                    return [];
                }

                return $this->wpdb->get_row($sql_query);

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

            return $this->runWpDB($query, $bindings, function ($sql_query) {

                if ($this->pretending) {
                    return [];
                }

                return $this->wpdb->get_results($sql_query);

            }
            );

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

            return $this->runWpDbAndReturnBool($query, $bindings);


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

            return $this->runWpDB($query, $bindings, function ($sql_query) {

                if ($this->pretending) {
                    return 0;
                }

                $this->wpdb->query($sql_query);

                return (int) $this->wpdb->rows_affected;


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
         * Run a select statement against the database and returns a generator.
         * I dont believe that this is currently possible like it is with laravel,
         * since wpdb does not use PDO.
         * Every wpdb method seems to be iterating over the result array.
         *
         * @param  string  $query
         * @param  array  $bindings
         * @param  bool  $useReadPdo
         *
         * @return \Generator
         */
        public function cursor($query, $bindings = [], $useReadPdo = true)
        {

            // For now just use this do not cause errors.
            return $this->select($query, $bindings);


        }



        /*
        |
        |
        |--------------------------------------------------------------------------
        | Helper functions
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
        public function getDatabaseName()
        {

            return $this->getConfig('name');

        }


    }