<?php


    namespace WpEloquent;

    use Exception;

    class CompatibilityException extends Exception
    {

    	public function __construct( $actual_version, $needed_version ) {

		    parent::__construct( $this->message($actual_version, $needed_version) );
	    }

	    private function message ($actual_version, $needed_version) {

		   return 'Your Plugin requires version: ' . $actual_version . 'of BetterWpDb. Due to other plugin dependencies the min/max compatible version on this WP-install is: ' . $needed_version;

	    }

    }