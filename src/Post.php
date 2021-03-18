<?php
	
	namespace WpEloquent;
	
	
	
	use Illuminate\Database\Eloquent\Model;
	
	class Post extends Model {
		
		protected $primaryKey = 'ID';
		
		const CREATED_AT = 'post_date';
		
		const UPDATED_AT = 'post_modified';
		
	}
