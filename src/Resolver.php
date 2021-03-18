<?php
	
	namespace WpEloquent;
	
	use Illuminate\Database\ConnectionInterface;
	use Illuminate\Database\ConnectionResolverInterface;
	
	class Resolver implements ConnectionResolverInterface {
		
		
		/**
		 * Get a database connection instance.
		 *
		 * @param  string  $name
		 *
		 * @return \Illuminate\Database\ConnectionInterface
		 */
		public function connection( $name = NULL ): ConnectionInterface {
			
			return Connection::instance();
			
		}
		
		
		/**
		 * Get the default connection name.
		 *
		 * @return string
		 */
		public function getDefaultConnection(): string {
			
			// Nothing to implement
			
		}
		
		/**
		 * Set the default connection name.
		 *
		 * @param  string  $name
		 *
		 * @return void
		 */
		public function setDefaultConnection( $name ) {
			
			// Nothing to implement
			
		}
		
	}
