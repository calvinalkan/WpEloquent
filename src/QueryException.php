<?php


    namespace WpEloquent;

    use Exception;
    use mysqli_sql_exception;

    class QueryException extends Exception
    {

        private $sql;
        private $bindings;


        public function __construct( string $sql , array $bindings, mysqli_sql_exception $exception)
        {

            $this->sql = $sql;
            $this->bindings = $bindings;
            parent::__construct($this->formatMessage());

        }

        private function formatMessage()
        {

            return 'foo';

        }

        /**
         * Get the SQL for the query.
         *
         * @return string
         */
        public function getSql()
        {
            return $this->sql;
        }

        /**
         * Get the bindings for the query.
         *
         * @return array
         */
        public function getBindings()
        {
            return $this->bindings;
        }

    }