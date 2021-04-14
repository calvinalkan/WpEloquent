<?php /** @noinspection PhpUndefinedClassInspection */


    namespace WpEloquent\ExtendsWpdb;

    use mysqli;
    use mysqli_result;
    use WpEloquent\Traits\DelegatesToWpdb;
    use WpEloquent\Traits\PreparesQueries;


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

        public function __construct( mysqli $mysqli, $wpdb)
        {

            $this->wpdb = $wpdb;

            $this->mysqli = $mysqli;

            mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);


        }


        public function doSelect($query, $bindings) : array
        {

            $stmt = $this->preparedStatement($query, $bindings);

            $stmt->execute();

            return $stmt->get_result()->fetch_all(MYSQLI_ASSOC) ?? [];

        }

        public function doStatement(string $query, array $bindings) : bool
        {

            if (empty($bindings)) {

                $result = $this->mysqli->query($query);

                return $result !== false;

            }

            $stmt = $this->preparedStatement($query, $bindings);

            return $stmt->execute();


        }

        public function doAffectingStatement($query, array $bindings) : int
        {

            if (empty($bindings)) {

                $this->mysqli->query($query);

                return $this->mysqli->affected_rows;

            }

            $this->preparedStatement($query, $bindings)->execute();

            return $this->mysqli->affected_rows;


        }

        public function doUnprepared(string $query) : bool
        {

            $result = $this->mysqli->query($query);

            return $result !== false;

        }


        public function doCursorSelect($query, $bindings) : mysqli_result
        {

            $statement = $this->preparedStatement($query, $bindings);

            $statement->execute();

            return $statement->get_result();

        }

        public function startTransaction()
        {
            $this->mysqli->begin_transaction();
        }

        public function commitTransaction()
        {
            $this->mysqli->commit();
        }

        public function rollbackTransaction($name = null)
        {
            if ( $name ) {

                $this->mysqli->rollback(0, $name);

            }

            $this->mysqli->rollback();

        }

        public function createSavepoint(string $name)
        {
            $this->mysqli->savepoint($name);

        }




    }

