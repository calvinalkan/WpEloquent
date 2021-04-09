<?php


    namespace WpEloquent;

    use Illuminate\Database\Schema\MySqlBuilder;
    use Illuminate\Support\Str;
    use Closure;

    class MySqlSchemaBuilder extends MySqlBuilder
    {


        /**
         *
         * Alias for the table method.
         *
         * @param  string  $table
         * @param  Closure  $closure
         */
        public function modify (string $table , Closure $closure) {

            $this->table($table, $closure);

        }


        public function getAllTables() : array
        {

            $parent = collect(parent::getAllTables());

            $key = 'Tables_in_'.$this->connection->getDatabaseName();

            return $parent->pluck($key)->toArray();


        }

        public function getColumnsByOrdinalPosition($table)
        {

            $query = $this->grammar->compileGetColumnsByOrdinalPosition();

            $bindings = [$this->connection->getTablePrefix().$table];

            return $this->connection->runWpDB($query, $bindings, function ($sql_query) {


                $results = $this->connection->getWpDB()->get_results($sql_query, ARRAY_A);

                return collect($results)->pluck('COLUMN_NAME')->toArray();


            });


        }

        public function getTableCollation($table)
        {

            $query = $this->grammar->compileGetTableCollation();

            $bindings = [$this->connection->getTablePrefix().$table];

            return $this->connection->runWpDB($query, $bindings, function ($sql_query) {

                $results = $this->connection->getWpDB()->get_row($sql_query, ARRAY_A);

                return $results['Collation'];


            });

        }

        public function getTableCharset($table) : string
        {

            $collation = $this->getTableCollation($table);

            return Str::before($collation, '_');

        }

        public function getFullColumnInfo($table) : array
        {

            $query = $this->grammar->compileGetFullColumnInfo();

            $binding = $this->connection->getTablePrefix().$table;

            if ($this->hasTable($table)) {

                return $this->connection->runWpDbUnprepared(

                    str_replace('?', "`".$binding."`", $query), function ($sql) {

                    $col_info =  collect($this->connection->getWpDB()->get_results($sql, ARRAY_A));

                    $field_names = $col_info->pluck('Field');

                    return $field_names->combine($col_info)->toArray();


                }

                );

            }

            return [];

        }


        /**
         * Get the data type for the given column name.
         *
         * @param  string  $table
         * @param  string  $column
         *
         * @return string
         */
        public function getColumnType( $table,  $column) : string
        {

            return $this->getFullColumnInfo($table)[$column]['Type'] ?? '';

        }
    }