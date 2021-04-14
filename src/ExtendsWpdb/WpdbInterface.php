<?php


    namespace WpEloquent\ExtendsWpdb;

    use mysqli_result;

    /**
     * Interface WpdbInterface
     *
     * @property $prefix
     * @property $insert_id
     * @mixin \wpdb
     *
     */
    interface WpdbInterface
    {

        public function doSelect(string $query, array $bindings) : array;

        public function doStatement(string $query, array $bindings) : bool;

        public function doAffectingStatement($query, array $bindings) : int;

        public function doUnprepared(string $query) : bool;

        public function doCursorSelect($query, $bindings) : mysqli_result;

        public function startTransaction();

        public function commitTransaction();

        public function rollbackTransaction(  $name = null );

        public function createSavepoint( string $name );


    }