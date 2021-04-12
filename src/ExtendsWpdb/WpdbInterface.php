<?php


    namespace WpEloquent\ExtendsWpdb;

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

        public function doSelectOne($query, $bindings) ;

        public function doCursorSelect($query, $bindings);

        public function startTransaction();

        public function commitTransaction();

        public function rollbackTransaction(  $name = null );

        public function createSavepoint( string $name );


    }