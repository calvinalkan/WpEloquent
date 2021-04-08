<?php


    namespace WpEloquent;

    use Illuminate\Database\Schema\MySqlBuilder;

    class MySqlSchemaBuilder extends MySqlBuilder
    {


        public function getColumnType($table, $column)
        {

            return parent::getColumnType($table, $column);
        }

        public function getAllTables() : array
        {

            $parent =  collect( parent::getAllTables() );


            return $parent->pluck(0)->toArray();



        }

    }