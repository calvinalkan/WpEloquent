<?php


    namespace WpEloquent;

    use Illuminate\Database\Schema\Grammars\MySqlGrammar as EloquentMySqlGrammar;

    class MySqlSchemaGrammar extends EloquentMySqlGrammar
    {

        public function compileGetColumnType () : string
        {

            return 'select data_type from information_schema.columns where table_name = ? and column_name = ?';

        }

    }