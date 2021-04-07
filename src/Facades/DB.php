<?php
	
	namespace WpEloquent\Facades;
	
	
	use Illuminate\Support\Facades\Facade;
	use WpEloquent\WordpressConnection;
	
	/**
	 *
	 * Allow calling the QueryBuilder statically.
	 *
	 * @see \Illuminate\Database\DatabaseManager
	 * @see \Illuminate\Database\Connection
	 */
	class DB extends Facade
	{
		
		protected static function getFacadeAccessor()
		{
			return WordpressConnection::instance();
		}
	}