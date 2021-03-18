<?php
	
	namespace WpEloquent;
	
	use Illuminate\Database\Eloquent\Model;
	
	class WpEloquent {
		
		public static function boot() {
			
			Model::setConnectionResolver( new Resolver() );
			
		}
		
	}