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

        /**
         * @var mixed|string
         */
        private $connection_name;

        /**
         * @var mixed|string
         */
        private $table_prefix;

        public function __construct ( wpdb $wpdb, $db_name = 'wp-eloquent', $table_prefix = '' )
        {

            $this->wpdb =  $wpdb;
            $this->connection_name = $db_name;
            $this->table_prefix = $table_prefix;

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

            static $instance = false;

            if ( ! $instance ) {

                $instance = new WpConnection(
                    $this->wpdb,
                    new SanitizerFactory($this->wpdb)
                );

            }

            return $instance;


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
