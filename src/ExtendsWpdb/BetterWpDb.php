<?php /** @noinspection PhpUndefinedClassInspection */


    namespace WpEloquent\ExtendsWpdb;

    use mysqli;
    use WpEloquent\Traits\DelegatesToWpdb;
    use WpEloquent\Traits\PreparesQueries;

    /**
     * Class BetterWpDb
     *
     * We need this class in order to access necessary protected properties
     * of wpdb for which there currently are no getters.
     *
     */
    class BetterWpDb implements WpdbInterface
    {

        use DelegatesToWpdb;
        use PreparesQueries;

        /**
         * @var mysqli;
         */
        protected $mysqli;

        /**
         * @var wpdb
         */
        protected $wpdb;

        public function __construct(mysqli $mysqli, $wpdb)
        {

            $this->wpdb = $wpdb;

            $this->mysqli = $mysqli;


        }


        public function doSelect($query, $bindings) : array
        {



            $stmt = $this->preparedQuery($query, $bindings);

            return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);


        }

        public function doStatement(string $query, array $bindings) : bool
        {

            if (empty($bindings)) {

                $this->mysqli->query($query);

                return true;

            }

            $stmt = $this->preparedQuery($query, $bindings);

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

        public function startTransaction()
        {
            // TODO: Implement startTransaction() method.
        }

        public function commitTransaction()
        {
            // TODO: Implement commitTransaction() method.
        }

        public function rollbackTransaction($name = null)
        {
            // TODO: Implement rollbackTransaction() method.
        }

        public function createSavepoint(string $name)
        {
            // TODO: Implement createSavepoint() method.
        }

    }

