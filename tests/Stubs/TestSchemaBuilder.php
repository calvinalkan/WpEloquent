<?php


    namespace Tests\Stubs;

    use WpEloquent\MySqlSchemaBuilder;

    use function PHPUnit\Framework\assertSame;
    use function PHPUnit\Framework\assertTrue;

    /**
     * Class TestSchemaBuilder
     *
     * @see MySqlSchemaBuilder
     */
    class TestSchemaBuilder extends MySqlSchemaBuilder
    {


        /**
         * @var string|null
         */
        private $table;


        public function __construct($connection, $table = null)
        {

            $this->table = $table;

            parent::__construct($connection);
        }

        public function seeColumnOfType($column, $type)
        {

            $table = $this->table;

            assertTrue($this->hasColumn($table, $column),
                'Column: '.$column.' not found.');
            assertSame($type, $this->getColumnType($table, $column),
                'Column types dont match for column: '.$column);

        }


        public function seePrimaryKey($column)
        {

            $col = $this->getFullColumnInfo($this->table)[$column];
            assertTrue($col['Key'] === 'PRI');

        }

        public function seeNullableColumn(string $column) : bool
        {

            $col = $this->getFullColumnInfo($this->table)[$column];

            return $col['Null'] === 'YES';
        }

        public function seeUniqueColumn(string $column)
        {

            $col = $this->getFullColumnInfo($this->table)[$column];
            assertTrue($col['Key'] === 'UNI');
        }

        public function seeIndexColumn(string $column)
        {

            $col = $this->getFullColumnInfo($this->table)[$column];
            assertTrue($col['Key'] === 'MUL');

        }


    }