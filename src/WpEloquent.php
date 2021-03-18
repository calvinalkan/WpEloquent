<?php
	
	namespace WpEloquent;
	
	use Illuminate\Database\Eloquent\Model as EloquentModel;
	
	class WpEloquent {
		
		public static function boot() {
			
			EloquentModel::setConnectionResolver( new Resolver() );
			
		}
		
	}