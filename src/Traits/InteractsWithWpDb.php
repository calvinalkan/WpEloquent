<?php


    namespace WpEloquent;

    use Illuminate\Database\QueryException;
    use Exception;
    use \Closure;

    trait InteractsWithWpDb
    {

        /*
        |
        |
        |--------------------------------------------------------------------------
        | The API to the wpdb class.
        |--------------------------------------------------------------------------
        |
        |
        | Every query is wrapped in an anonymous closure and gets passed into the runWpDb
        | method. This way we have one central way to catch errors etc.
        |
        |
        |
        |
        */

        /**
         * Run a SQL statement through the wpdb class.
         *
         * @param  string  $query
         * @param  array  $bindings
         * @param  Closure  $callback
         *
         * @return mixed
         * @throws QueryException
         */
        private function runWpDB(string $query, array $bindings, Closure $callback)
        {

            // To execute the statement, we'll simply call the callback, which will actually
            // run the SQL against the wpdb class .
            try {


                $bindings = $this->prepareBindings($bindings);
                $sql_query = $this->prepareQuery($query, $bindings);

                $start = microtime(true);

                $result = $callback($sql_query);

                if ( ! $result || $this->wpdb->last_error) {

                    $this->throwQueryException(
                        $sql_query, $bindings, new Exception($this->wpdb->last_error)
                    );

                }

                // Once we have run the query we will calculate the time that it took to run and
                // then log the query, bindings, and execution time so we will report them on
                // the event that the developer needs them. We'll log time in milliseconds.
                $this->logQuery($query, $bindings, $this->getElapsedTime($start));

                return $result;

            } catch (Exception $e) {

                $this->throwQueryException($query, $bindings, $e);

            }

        }


        /**
         * This functions just wraps the unprepared method() so that the api is not confusing.
         * For every interface method that requires a boolean return value we need to use this
         * method. This is due to wpdb not having a dedicated Exception handling but returning
         * FALSE on an error. We cant use the normal runWpDB method() because in there we catch a
         * FALSE return value and translate in to a exception.
         *
         * @param  string  $query
         * @param  array  $bindings
         *
         * @return bool
         */
        private function runWpDbAndReturnBool(string $query, array $bindings) : bool
        {

            if ($this->pretending) {
                return true;
            }

            $bindings = $this->prepareBindings($bindings);
            $sql_query = $this->prepareQuery($query, $bindings);

            $start = microtime(true);

            $result = $this->unprepared($sql_query);

            $this->logQuery($query, $bindings, $this->getElapsedTime($start));

            return $result;

        }


        /**
         * Run a raw, unprepared query against the wpdb class.
         * BIG CARE MUST BE TAKEN HERE BY THE CLIENT.
         * data will not be escaped.
         *
         * @param  string  $query
         *
         * @return bool
         */
        public function unprepared($query) : bool
        {

            $start = microtime(true);

            $result = $this->wpdb->query($query);

            $this->logQuery($query, [], $this->getElapsedTime($start));

            return is_int($result) ? true : $result;


        }


        private function throwQueryException($query, $bindings, Exception $e)
        {

            throw new QueryException(
                $query, $bindings, $e
            );

        }

    }