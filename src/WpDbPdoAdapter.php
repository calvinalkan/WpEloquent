<?php


    namespace WpEloquent;

    use WpEloquent\ExtendsWpdb\WpdbInterface;

    class WpDbPdoAdapter
    {

        /**
         * @var WpdbInterface
         */
        private $wpdb;

        public function __construct( WpdbInterface $wpdb)
        {

            $this->wpdb = $wpdb;

        }

        public function lastInsertId($sequence) : int
        {

            return $this->wpdb->insert_id;

        }

    }