<?php
	
	namespace WpEloquent;
	
	
	use Faker\Generator;
	use Illuminate\Database\Eloquent\Factories\Factory as EloquentFactory;
	use Faker\Factory;
	
	
	abstract class ModelFactory extends EloquentFactory {
		
		protected function withFaker(): Generator {
			
			return Factory::create();
		}
		
	}