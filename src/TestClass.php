<?php
	
	namespace WpEloquent;
	
	class TestClass {
		
		public static function test() {
			
			
			return $post = \WpEloquent\Post::find(2);
			
			
		}
		
	}