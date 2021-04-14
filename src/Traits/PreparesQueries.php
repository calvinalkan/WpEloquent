<?php


    namespace WpEloquent\Traits;

    use mysqli_stmt;

    /**
     * Trait PreparesQueries
     *
     * @property \mysqli $mysqli
     *
     */
    trait PreparesQueries
    {


        /**
         * @param $sql
         * @param $bindings
         * @param  string  $types
         *
         * @return false|mysqli_stmt
         */
        private function preparedStatement($sql, $bindings, $types = "")
        {

            $types = $types ? : str_repeat("s", count($bindings));
            $stmt = $this->mysqli->prepare($sql);

            if ( ! empty($bindings)) {

                $stmt->bind_param($types, ...$bindings);

            }

            return $stmt;
        }


    }