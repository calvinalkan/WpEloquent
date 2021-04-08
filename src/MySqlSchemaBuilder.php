<?php


    namespace WpEloquent;

    use Illuminate\Database\Schema\MySqlBuilder;

    class MySqlSchemaBuilder extends MySqlBuilder
    {


        /**
         * Get the data type for the given column name.
         *
         * @param  string  $table
         * @param  string  $column
         * @return string
         */
        public function getColumnType($table, $column)
        {


            $query = $this->grammar->compileGetColumnType();

            $bindings = [ $this->connection->getTablePrefix() . $table, $column];

            return $this->connection->runWpDB($query, $bindings, function ($sql_query) {


                return $this->connection->getWpDB()->get_var($sql_query);


            });


        }


        public function getAllTables() : array
        {

            $parent =  collect( parent::getAllTables() );

            $key = 'Tables_in_' . $this->connection->getDatabaseName();

            return $parent->pluck($key)->toArray();


        }

    }