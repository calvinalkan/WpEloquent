<?php


    namespace WpEloquent;

    use WpEloquent\ExtendsWpdb\WpdbInterface;

    class SanitizerFactory
    {

        /**
         * @var \wpdb
         */
        private $wpdb;

        public function __construct( WpdbInterface $wpdb )
        {

            $this->wpdb = $wpdb;
        }


        public function make(string $query, array $bindings ) : QuerySanitizer
        {

            return new QuerySanitizer($this->wpdb, $query, $bindings);

        }

    }