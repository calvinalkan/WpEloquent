<?php


    namespace WpEloquent;

    use Illuminate\Database\ConnectionInterface;
    use Illuminate\Database\ConnectionResolverInterface;
    use wpdb;

    class Resolver implements ConnectionResolverInterface
    {


        /**
         *
         * An instance of the wpdb class.
         *
         * @var wpdb
         */
        private $wpdb;

        private $connection_name = 'wp-eloquent';

        public function __construct ( wpdb $wpdb )
        {

            $this->wpdb = $wpdb;

        }

        /**
         * Get a database connection instance.
         *
         * @param  string  $name
         *
         * @return \Illuminate\Database\ConnectionInterface
         */
        public function connection ( $name = NULL ) : ConnectionInterface
        {

            return WpConnection::instance( $this->wpdb );

        }


        /**
         * Get the default connection name.
         *
         * @return string
         */
        public function getDefaultConnection () : string
        {

           return $this->connection_name;

        }

        /**
         * Set the default connection name.
         *
         * @param  string  $name
         *
         * @return void
         */
        public function setDefaultConnection ( $name )
        {

           $this->connection_name = $name;

        }

    }
