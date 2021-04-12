<?php


    namespace WpEloquent\ExtendsWpdb;

    use mysqli;
    use wpdb;

    /**
     * Class DbFactory
     *
     * @property  mysqli $dbh
     * @see \wpdb
     *
     */
    class DbFactory
    {


        /**
         * @var mysqli
         */
        private $mysqli;

        /**
         * @var wpdb
         */
        private $wpdb;


        public function __construct( $wpdb )
        {

            $this->wpdb = $wpdb;

            try {

                $this->mysqli = $this->wpdb->dbh;

            }

            catch (\Throwable $e) {

                // This will work for sure if Wordpress where ever
                // to delete magic method accessors, which tbh will probably never happen.
                $this->mysqli = (function () {

                    return $this->dbh;

                })->call($wpdb);

            }



        }


        public static function make ( $wpdb ) : BetterWpDb
        {

            $factory = new self($wpdb);

            return new BetterWpDb($factory->mysqli, $factory->wpdb);

        }

    }





