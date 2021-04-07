<?php
	
	namespace WpEloquent;
	
	use Illuminate\Database\Eloquent\Model as EloquentModel;
	
	class WpEloquent {
		
		public static function boot() {

		    global $wpdb;

		    $resolver = new Resolver( clone $wpdb );

			EloquentModel::setConnectionResolver( $resolver );


		}
		
	}