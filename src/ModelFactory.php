<?php
	
	namespace WpEloquent;
	
	
	use Illuminate\Database\Eloquent\Factories\Factory;
	
	
	abstract class ModelFactory extends Factory {
		
		protected function withFaker(): \Faker\Generator {
			
			return \Faker\Factory::create();
	}
	
	}