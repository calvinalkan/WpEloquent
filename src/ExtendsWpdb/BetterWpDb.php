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

        public function __construct($db_user, $db_password, $db_name, $db_host, mysqli $mysqli = null )
        {

            parent::__construct($db_user, $db_password, $db_name, $db_host);

            $this->mysqli = $mysqli ?? $this->dbh;


        }


        public function doSelect($query, $bindings) : array
        {


        }

        public function doStatement(string $query, array $bindings) : bool
        {
            // TODO: Implement doStatement() method.
        }

        public function doAffectingStatement($query, array $bindings) : int
        {
            // TODO: Implement doAffectingStatement() method.
        }

        public function doUnprepared(string $query) : bool
        {
            // TODO: Implement doUnprepared() method.
        }

        public function doSelectOne($query, $bindings)
        {
            // TODO: Implement doSelectOne() method.
        }

        public function doCursorSelect($query, $bindings)
        {
            // TODO: Implement doCursorSelect() method.
        }

    }

    $wpdb = new BetterWpDb(DB_USER, DB_PASSWORD, DB_NAME, DB_HOST);