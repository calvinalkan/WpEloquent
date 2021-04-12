<?php


    namespace Tests\stubs;

    use WpEloquent\ExtendsWpdb\WpdbInterface;

    class FakeWpdb implements WpdbInterface
    {

        public function doSelect($query, $bindings) : array
        {
            // TODO: Implement doSelect() method.
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