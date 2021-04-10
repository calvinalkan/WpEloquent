<?php


    namespace WpEloquent;

    class SanitizerFactory
    {

        /**
         * @var \wpdb
         */
        private $wpdb;

        public function __construct(\wpdb $wpdb )
        {

            $this->wpdb = $wpdb;
        }


        public function make(string $query, array $bindings ) : QuerySanitizer
        {

            return new QuerySanitizer($this->wpdb, $query, $bindings);

        }

    }