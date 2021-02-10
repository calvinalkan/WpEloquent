<?php

	namespace WpEloquent;

	use Illuminate\Database\Capsule\Manager;
	use Illuminate\Database\ConnectionInterface;
	use Illuminate\Database\Eloquent\Model as Eloquent;
	use Illuminate\Database\Query\Builder;
	use Illuminate\Support\Str;

	/**
	 * Model Class
	 *
	 * @package WeDevs\ERP\Framework
	 */
	abstract class Model extends Eloquent {

		private $mock_connection;

		protected bool $isWP;

		/**
		 * @param  array  $attributes
		 */
		public function __construct( array $attributes = array() ) {

			global $wpdb;

			$this->isWP = ($wpdb != null );

			if(!$this->isWp) {

				$capsule = new Manager();

				$capsule->addConnection( [
					'driver'    => 'mysql',
					'host'      => '127.0.0.1',
					'database'  => 'testing',
					'username'  => 'root',
					'password'  => 'root',
					'charset'   => 'utf8',
					'collation' => 'utf8_unicode_ci',
					'prefix'    => 'wp_',
				] );

				$this->mock_connection = $capsule->getConnection();

			}

			static::$resolver = new Resolver();

			parent::__construct( $attributes );
		}

		/**
		 * Get the database connection for the model.
		 *
		 * @return ConnectionInterface
		 */
		public function getConnection(): ConnectionInterface {

			global $wpdb;

			if ( $wpdb ) {

				return Connection::instance();

			} else {

				return $this->mock_connection;

			}

		}


		/**
		 * Set the connection associated with the model.
		 *
		 * @param  string|null  $name
		 *
		 * @return $this
		 */
		public function setConnection( $name ): Model {

			global $wpdb;

			if ( $wpdb ) {

				$this->connection = $name;

			} else {

				$this->connection = $this->mock_connection;

			}

			return $this;
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

			$table_prefix = $this->getConnection()->getTablePrefix();

			if ( isset( $this->table ) ) {

				$table = $this->addTablePrefix( $this->table, $table_prefix );

				return $table;

			}

			$table = str_replace( '\\', '', Str::snake( Str::plural( class_basename( $this ) ) ) );

			return $this->addTablePrefix( $table, $table_prefix );

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


		private function addTablePrefix( string $table, string $table_prefix ) {

			if(!$this->isWP) return $table;

			$table_prefix = trim( $table_prefix, '_' ) . '_';

			$prefix_exists = count(explode($table_prefix, $table)) > 1;

			return ( $prefix_exists ) ? $table : $table_prefix . $table;

		}

	}
