<?php
	
	namespace WpEloquent;
	
	use Illuminate\Database\ConnectionInterface;
	use Illuminate\Database\Query\Builder;
	use Illuminate\Database\Query\Grammars\Grammar;
	use Illuminate\Database\Query\Processors\Processor;
	use Illuminate\Database\Query\Expression;
	use Illuminate\Database\QueryException;
	use Illuminate\Support\Arr;
	
	class Connection implements ConnectionInterface {
		
		
		/**
		 *
		 * @var \wpdb;
		 *
		 */
		private $wpdb;
		
		private string $tablePrefix = '';
		
		/**
		 * Count of active transactions
		 *
		 * @var int
		 */
		public $transactionCount = 0;
		
		
		/**
		 * The database connection configuration options.
		 *
		 * @var array
		 */
		private $config;
		
		
		/**
		 * Initializes the Database class
		 *
		 * @return \WpEloquent\Connection
		 */
		public static function instance() {
			
			global $wpdb;
			
			static $instance = FALSE;
			
			if ( ! $instance ) {
				$instance = new self($wpdb);
			}
			
			return $instance;
			
		}
		
		
		/**
		 *
		 * @param        $database
		 * @param  null  $config
		 */
		public function __construct( $database, $config = null ) {
			
			$this->config = $config ?? [
				
					'name' => 'wp-eloquent',
					
				];
			
			
			$this->tablePrefix =  $database->base_prefix ?? '';
			
			$this->wpdb = $database;
				
			
			
		}
		
		
		/**
		 * Get the database connection name.
		 *
		 * @return string|null
		 */
		public function getName() {
			return $this->getConfig( 'name' );
		}
		
		/**
		 * Begin a fluent query against a database table.
		 *
		 * @param  \Closure|\Illuminate\Database\Query\Builder|string  $table
		 * @param  string|null                                         $as
		 *
		 * @return \Illuminate\Database\Query\Builder
		 */
		public function table( $table, $as = NULL ): Builder {
			
			$processor = $this->getPostProcessor();
			
			$table = $this->wpdb->prefix . $table;
			
			$query = new Builder( $this, $this->getQueryGrammar(), $processor );
			
			return $query->from( $table, $as );
		}
		
		/**
		 * Get a new raw query expression.
		 *
		 * @param  mixed  $value
		 *
		 * @return \Illuminate\Database\Query\Expression
		 */
		public function raw( $value ) {
			return new Expression( $value );
		}
		
		/**
		 * Get a new query builder instance.
		 *
		 * @return \Illuminate\Database\Query\Builder
		 */
		public function query() {
			return new Builder(
				$this, $this->getQueryGrammar(), $this->getPostProcessor()
			);
		}
		
		/**
		 * Run a select statement and return a single result.
		 *
		 * @param  string  $query
		 * @param  array   $bindings
		 * @param  bool    $useReadPdo
		 *
		 * @return mixed
		 * @throws QueryException
		 *
		 */
		public function selectOne( $query, $bindings = [], $useReadPdo = TRUE ) {
			
			$query = $this->bind_params( $query, $bindings );
			
			$result = $this->wpdb->get_row( $query );
			
			if ( $result === FALSE || $this->wpdb->last_error ) {
				throw new QueryException( $query, $bindings, new \Exception( $this->wpdb->last_error ) );
			}
			
			return $result;
		}
		
		/**
		 * Run a select statement against the database.
		 *
		 * @param  string  $query
		 * @param  array   $bindings
		 * @param  bool    $useReadPdo
		 *
		 * @return array
		 * @throws QueryException
		 *
		 */
		public function select( $query, $bindings = [], $useReadPdo = TRUE ) {
			$query = $this->bind_params( $query, $bindings );
			
			$result = $this->wpdb->get_results( $query );
			
			if ( $result === FALSE || $this->wpdb->last_error ) {
				throw new QueryException( $query, $bindings, new \Exception( $this->wpdb->last_error ) );
			}
			
			return $result;
		}
		
		/**
		 * Run a select statement against the database and returns a generator.
		 * TODO: Implement cursor and all the related sub-methods.
		 *
		 * @param  string  $query
		 * @param  array   $bindings
		 * @param  bool    $useReadPdo
		 *
		 * @return \Generator
		 */
		public function cursor( $query, $bindings = [], $useReadPdo = TRUE ) {
		
		}
		
		/**
		 * A hacky way to emulate bind parameters into SQL query
		 *
		 * @param $query
		 * @param $bindings
		 *
		 * @return mixed
		 */
		private function bind_params( $query, $bindings, $update = FALSE ) {
			
			$query    = str_replace( '"', '`', $query );
			$bindings = $this->prepareBindings( $bindings );
			
			if ( ! $bindings ) {
				return $query;
			}
			
			$bindings = array_map( function ( $replace ) {
				
				if ( is_string( $replace ) ) {
					$replace = "'" . esc_sql( $replace ) . "'";
				}
				elseif ( $replace === NULL ) {
					
					$replace = "null";
					
				}
				
				return $replace;
				
			}, $bindings );
			
			$query = str_replace( array( '%', '?' ), array( '%%', '%s' ), $query );
			$query = vsprintf( $query, $bindings );
			
			return $query;
		}
		
		/**
		 * Bind and run the query
		 *
		 * @param  string  $query
		 * @param  array   $bindings
		 *
		 * @return array
		 * @throws QueryException
		 *
		 */
		public function bind_and_run( $query, $bindings = array() ) {
			
			$new_query = $this->bind_params( $query, $bindings );
			
			$result = $this->wpdb->query( $new_query );
			
			if ( $result === FALSE || $this->wpdb->last_error ) {
				throw new QueryException( $new_query, $bindings, new \Exception( $this->wpdb->last_error ) );
			}
			
			return (array) $result;
		}
		
		/**
		 * Run an insert statement against the database.
		 *
		 * @param  string  $query
		 * @param  array   $bindings
		 *
		 * @return bool
		 */
		public function insert( $query, $bindings = array() ) {
			
			return $this->statement( $query, $bindings );
			
		}
		
		/**
		 * Run an update statement against the database.
		 *
		 * @param  string  $query
		 * @param  array   $bindings
		 *
		 * @return int
		 */
		public function update( $query, $bindings = array() ) {
			return $this->affectingStatement( $query, $bindings );
		}
		
		/**
		 * Run a delete statement against the database.
		 *
		 * @param  string  $query
		 * @param  array   $bindings
		 *
		 * @return int
		 */
		public function delete( $query, $bindings = array() ) {
			return $this->affectingStatement( $query, $bindings );
		}
		
		/**
		 * Execute an SQL statement and return the boolean result.
		 *
		 * @param  string  $query
		 * @param  array   $bindings
		 *
		 * @return bool
		 */
		public function statement( $query, $bindings = array() ) {
			$new_query = $this->bind_params( $query, $bindings, TRUE );
			
			return $this->unprepared( $new_query );
		}
		
		/**
		 * Run an SQL statement and get the number of rows affected.
		 *
		 * @param  string  $query
		 * @param  array   $bindings
		 *
		 * @return int
		 */
		public function affectingStatement( $query, $bindings = array() ) {
			$new_query = $this->bind_params( $query, $bindings, TRUE );
			
			$result = $this->wpdb->query( $new_query );
			
			if ( $result === FALSE || $this->wpdb->last_error ) {
				throw new QueryException( $new_query, $bindings, new \Exception( $this->wpdb->last_error ) );
			}
			
			return intval( $result );
		}
		
		/**
		 * Run a raw, unprepared query against the PDO connection.
		 *
		 * @param  string  $query
		 *
		 * @return bool
		 */
		public function unprepared( $query ) {
			
			$result = $this->wpdb->query( $query );
			
			return ( $result === FALSE || $this->wpdb->last_error );
		}
		
		/**
		 * Prepare the query bindings for execution.
		 *
		 * @param  array  $bindings
		 *
		 * @return array
		 */
		public function prepareBindings( array $bindings ) {
			$grammar = $this->getQueryGrammar();
			
			foreach ( $bindings as $key => $value ) {
				
				// Micro-optimization: check for scalar values before instances
				if ( is_bool( $value ) ) {
					$bindings[ $key ] = intval( $value );
				} elseif ( is_scalar( $value ) ) {
					continue;
				} elseif ( $value instanceof \DateTime ) {
					// We need to transform all instances of the DateTime class into an actual
					// date string. Each query grammar maintains its own date string format
					// so we'll just ask the grammar for the format to get from the date.
					$bindings[ $key ] = $value->format( $grammar->getDateFormat() );
				}
			}
			
			return $bindings;
		}
		
		/**
		 * Execute a Closure within a transaction.
		 *
		 * @param  \Closure  $callback
		 * @param  int       $attempts
		 *
		 * @return mixed
		 *
		 * @throws \Exception
		 */
		public function transaction( \Closure $callback, $attempts = 1 ) {
			$this->beginTransaction();
			try {
				$data = $callback();
				$this->commit();
				
				return $data;
			} catch ( \Exception $e ) {
				$this->rollBack();
				throw $e;
			}
		}
		
		/**
		 * Start a new database transaction.
		 *
		 * @return void
		 */
		public function beginTransaction() {
			$transaction = $this->unprepared( "START TRANSACTION;" );
			if ( FALSE !== $transaction ) {
				$this->transactionCount ++;
			}
		}
		
		/**
		 * Commit the active database transaction.
		 *
		 * @return void
		 */
		public function commit() {
			if ( $this->transactionCount < 1 ) {
				return;
			}
			$transaction = $this->unprepared( "COMMIT;" );
			if ( FALSE !== $transaction ) {
				$this->transactionCount --;
			}
		}
		
		/**
		 * Rollback the active database transaction.
		 *
		 * @return void
		 */
		public function rollBack() {
			if ( $this->transactionCount < 1 ) {
				return;
			}
			$transaction = $this->unprepared( "ROLLBACK;" );
			if ( FALSE !== $transaction ) {
				$this->transactionCount --;
			}
		}
		
		/**
		 * Get the number of active transactions.
		 *
		 * @return int
		 */
		public function transactionLevel() {
			return $this->transactionCount;
		}
		
		/**
		 * Execute the given callback in "dry run" mode.
		 *
		 * @param  \Closure  $callback
		 *
		 * @return array
		 */
		public function pretend( \Closure $callback ) {
			// TODO: Implement pretend() method.
		}
		
		public function getPostProcessor() {
			return new Processor();
		}
		
		public function getQueryGrammar() {
			
			return $this->withTablePrefix( new Grammar() );
			
		}
		
		/**
		 * @param  \Illuminate\Database\Grammar  $grammar
		 *
		 * @return \Illuminate\Database\Grammar  $grammar
		 */
		public function withTablePrefix( Grammar $grammar ) {
			
			$grammar->setTablePrefix( $this->tablePrefix );
			
			return $grammar;
			
		}
		
		/**
		 * Return self as PDO
		 *
		 * @return \WpEloquent\Connection
		 */
		public function getPdo(): Connection {
			return $this;
		}
		
		/**
		 * Return the last insert id
		 *
		 * @param  string  $args
		 *
		 * @return int
		 */
		public function lastInsertId( $args ) {
			return $this->wpdb->insert_id;
		}
		
		/**
		 * Get an option from the configuration options.
		 *
		 * @param  string|null  $option
		 *
		 * @return mixed
		 */
		public function getConfig( $option = NULL ) {
			return Arr::get( $this->config, $option );
		}
		
		
		/**
		 * @return string
		 */
		public function getDatabaseName() {
			
			return $this->getConfig( 'name' );
			
		}
		
		
	}
