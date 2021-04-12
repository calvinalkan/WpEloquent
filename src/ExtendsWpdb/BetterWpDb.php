<?php /** @noinspection PhpUndefinedClassInspection */


    namespace WpEloquent\ExtendsWpdb;


    use mysqli;
    use wpdb;


    /**
     * Class BetterWpDb
     *
     * We need this class in order to access necessary protected properties
     * of wpdb for which there currently are no getters.
     *
     */
    class BetterWpDb extends wpdb implements WpdbInterface
    {

        /**
         * @var mysqli;
         */
        protected $mysqli;

        public function __construct($db_user, $db_password, $db_name, $db_host)
        {

            parent::__construct($db_user, $db_password, $db_name, $db_host);

            $this->mysqli = $this->dbh;


        }


        public function doSelect($query, $bindings) : array
        {



        }

    }

    $wpdb = new BetterWpDb(DB_USER, DB_PASSWORD, DB_NAME, DB_HOST);