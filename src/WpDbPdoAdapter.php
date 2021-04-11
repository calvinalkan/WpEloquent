<?php


    namespace WpEloquent;

    use wpdb;

    class WpDbPdoAdapter
    {

        /**
         * @var wpdb
         */
        private $wpdb;

        public function __construct(wpdb $wpdb)
        {

            $this->wpdb = $wpdb;

        }

        public function lastInsertId($sequence) : int
        {

            return $this->wpdb->insert_id;

        }

    }