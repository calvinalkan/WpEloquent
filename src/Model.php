<?php
	
	namespace WpEloquent;
	
	use Illuminate\Database\Eloquent\Model as Eloquent;
	use Illuminate\Database\Query\Builder;
	use Illuminate\Support\Str;
	
	/**
	 * Model Class
	 *
	 * @package WeDevs\ERP\Framework
	 */
	abstract class Model extends Eloquent {
		
		
		/**
		 * @param  array  $attributes
		 */
		public function __construct( array $attributes = array() ) {
			
			static::$resolver = new Resolver();
			
			parent::__construct( $attributes );
		}
		
		/**
		 * Get the database connection for the model.
		 *
		 * @return \WpEloquent\Connection
		 */
		public function getConnection(): Connection {
			return Connection::instance();
		}
		
		/**
		 * Get the table associated with the model.
		 *
		 * Append the WordPress table prefix with the table name if
		 * no table name is provided
		 *
		 * @return string
		 */
		public function getTable(): string {
			
			if ( isset( $this->table ) ) {
				return $this->table;
			}
			
			$table = str_replace( '\\', '', Str::snake( Str::plural( class_basename( $this ) ) ) );
			
			return $this->getConnection()->db->prefix . $table;
		}
		
		/**
		 * Get a new query builder instance for the connection.
		 *
		 * @return \Illuminate\Database\Query\Builder
		 */
		protected function newBaseQueryBuilder(): Builder {
			
			$connection = $this->getConnection();
			
			return new Builder(
				$connection, $connection->getQueryGrammar(), $connection->getPostProcessor()
			);
		}
		
	}