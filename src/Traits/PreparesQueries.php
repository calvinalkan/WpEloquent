<?php


    namespace WpEloquent\Traits;

    /**
     * Trait PreparesQueries
     *
     * @property \mysqli $mysqli
     *
     */
    trait PreparesQueries
    {


        private function preparedQuery($sql, $bindings, $types = "")
        {

            $types = $types ? : str_repeat("s", count($bindings));
            $stmt = $this->mysqli->prepare($sql);

            if ( ! empty($bindings)) {

                $stmt->bind_param($types, ...$bindings);

            }

            $success = $stmt->execute();

            return $stmt;
        }


    }