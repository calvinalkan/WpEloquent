<?php


    namespace WpEloquent;

    use Illuminate\Database\Schema\Grammars\MySqlGrammar as EloquentMySqlGrammar;

    class MySqlSchemaGrammar extends EloquentMySqlGrammar
    {

        public function compileGetColumnType () : string
        {

            return 'select data_type from information_schema.columns where table_name = ? and column_name = ?';

        }


        public function compileGetColumnsByOrdinalPosition () : string
        {

            return "select column_name FROM information_schema.columns where table_name = ? order by ordinal_position";

        }


        public function compileGetTableCollation() : string
        {

            return "show table status where name like ?";

        }

        public function compileGetFullColumnInfo() : string
        {

            return "show full columns from ?";

        }


    }