<?php


    namespace WpEloquent;

    use Illuminate\Database\Schema\Grammars\MySqlGrammar as EloquentMySqlGrammar;

    class MySqlSchemaGrammar extends EloquentMySqlGrammar
    {




        public function compileGetTableCollation() : string
        {

            return "show table status where name like ?";

        }

        public function compileGetFullColumnInfo() : string
        {

            return "show full columns from ?";

        }


    }