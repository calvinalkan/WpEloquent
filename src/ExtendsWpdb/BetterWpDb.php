<?php

    namespace WpEloquent\ExtendsWpDb;


    use wpdb;

    /**
     * Class BetterWpDb
     *
     * We need this class in order to access necessary protected properties
     * of wpdb for which there currently are no getters.
     *
     */
    class BetterWpDb extends wpdb
    {


    }