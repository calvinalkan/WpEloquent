<?php


    namespace Tests\integration\Schema;

    use Codeception\TestCase\WPTestCase;
    use Illuminate\Database\Schema\Blueprint;
    use Illuminate\Database\Schema\Builder;
    use WpEloquent\MySqlSchemaBuilder;
    use WpEloquent\WpConnection;

    use function PHPUnit\Framework\assertFalse;
    use function PHPUnit\Framework\assertSame;
    use function PHPUnit\Framework\assertTrue;

    class SchemaBuilderTest extends WPTestCase
    {

        /**
         * @var \UnitTester;
         */
        protected $tester;

        /** @test */
        public function a_table_can_be_created()
        {

            $schema_builder = $this->newSchemaBuilder();

            $this->tester->dontSeeTableInDatabase('wp_test_table');

            $schema_builder->create('test_table', function (Blueprint $table) {

                $table->id();
                $table->string('name');
                $table->string('email');
                $table->timestamps();
            });

            $this->tester->seeTableInDatabase('wp_test_table');


        }


        /** @test */
        public function table_existence_can_be_checked()
        {


            $schema_builder = $this->newSchemaBuilder();

            self::assertFalse($schema_builder->hasTable('test_table'));

            $schema_builder->create('test_table', function (Blueprint $table) {

                $table->id();

            });

            self::assertTrue($schema_builder->hasTable('test_table'));


        }


        /** @test */
        public function column_existence_can_be_checked()
        {

            $schema_builder = $this->newSchemaBuilder();

            self::assertTrue($schema_builder->hasColumn('users', 'user_login'));
            self::assertFalse($schema_builder->hasColumn('users', 'user_profile_pic'));

            self::assertFalse($schema_builder->hasColumns('users',
                ['user_login', 'user_profile_pic']));
            self::assertTrue($schema_builder->hasColumns('users', ['user_login', 'user_email']));

        }

        /** @test */
        public function an_existing_table_can_be_updated()
        {

            $schema_builder = $this->newSchemaBuilder();

            $schema_builder->create('test_users', function (Blueprint $table) {

                $table->id();
                $table->string('name');
                $table->string('email');
                $table->timestamps();
            });

            assertFalse($schema_builder->hasColumn('test_users', 'phone'));

            $schema_builder->table('test_users', function (Blueprint $table) {

                $table->string('phone');

            });

            assertTrue($schema_builder->hasColumn('test_users', 'phone'));


        }


        /** @test */
        public function an_existing_column_can_be_renamed()
        {

            $this->newUserTable($builder = $this->newSchemaBuilder());

            assertTrue($builder->hasTable('test_users'));

            $builder->rename('test_users', 'test_users_new');

            assertTrue($builder->hasTable('test_users_new'));
            assertFalse($builder->hasTable('test_users'));


        }


        /** @test */
        public function a_table_can_be_dropped()
        {

            $this->newUserTable($builder = $this->newSchemaBuilder());

            assertTrue($builder->hasTable('test_users'));

            $builder->drop('test_users');

            assertFalse($builder->hasTable('test_users'));

            $builder->dropIfExists('test_users');

            $this->newUserTable($builder);

            assertTrue($builder->hasTable('test_users'));

            $builder->dropIfExists('test_users');

            assertFalse($builder->hasTable('test_users'));


        }


        /** @test */
        public function columns_can_be_dropped()
        {

            $this->newUserTable($builder = $this->newSchemaBuilder());

            $builder->dropColumns('test_users', ['id', 'name', 'email']);

            assertFalse($builder->hasColumn('test_users', 'name'));
            assertFalse($builder->hasColumn('test_users', 'email'));
            assertFalse($builder->hasColumn('test_users', 'id'));


        }


        /** @test */
        public function all_tables_can_be_retrieved()
        {


            $this->newUserTable($builder = $this->newSchemaBuilder());

            $tables = $builder->getAllTables();

            global $wpdb;

            $expected = array_values($wpdb->tables());

            $expected[] = 'wp_test_users';

            $this->assertEmpty(array_diff($expected, $tables));


        }


        /** @test */
        public function all_tables_can_be_dropped()
        {


            $this->newUserTable($builder = $this->newSchemaBuilder());

            $tables = $builder->getAllTables();

            global $wpdb;

            $expected = array_values($wpdb->tables());

            $expected[] = 'wp_test_users';

            $this->assertEmpty(array_diff($expected, $tables));

            $builder->dropAllTables();

            $this->assertEmpty($builder->getAllTables());


        }

        /** @test */
        public function the_column_type_can_be_found_for_the_last_query()
        {

            $this->newUserTable($builder = $this->newSchemaBuilder());

            $type = $builder->getColumnType('test_users', 'email');

            self::assertSame('varchar', $type);

        }


        /** @test */
        public function all_column_types_can_be_created()
        {

            $builder = $this->newTestBuilder();

            $builder->create('table1', function (Blueprint $table) {

                $table->bigIncrements('id_big');
                $table->bigInteger('votes');
                $table->binary('photo');
                $table->boolean('confirmed');
                $table->char('name', 100);
                $table->dateTimeTz('created_at_tz', $precision = 0);
                $table->dateTime('created_at', $precision = 0);
                $table->date('date');
                $table->decimal('amount_decimal', $precision = 8, $scale = 2);
                $table->double('amount_double', 8, 2);
                $table->enum('difficulty', ['easy', 'hard']);
                $table->float('amount_float', 8, 2);
                $table->foreignId('user_id');
                $table->geometryCollection('geometrycollection');
                $table->geometry('geometry');

            });

            $builder->seeColumnOfType('table1', 'id_big', 'bigint');
            $builder->seeColumnOfType('table1', 'votes', 'bigint');
            $builder->seeColumnOfType('table1', 'photo', 'blob');
            $builder->seeColumnOfType('table1', 'confirmed', 'tinyint');
            $builder->seeColumnOfType('table1', 'name', 'char');
            $builder->seeColumnOfType('table1', 'created_at_tz', 'datetime');
            $builder->seeColumnOfType('table1', 'created_at', 'datetime');
            $builder->seeColumnOfType('table1', 'date', 'date');
            $builder->seeColumnOfType('table1', 'amount_decimal', 'decimal');
            $builder->seeColumnOfType('table1', 'amount_double', 'double');

            // double is expected here. @see: https://github.com/laravel/framework/issues/18776
            $builder->seeColumnOfType('table1', 'amount_float', 'double');
            $builder->seeColumnOfType('table1', 'difficulty', 'enum');
            $builder->seeColumnOfType('table1', 'user_id', 'bigint');
            $builder->seeColumnOfType('table1', 'geometrycollection', 'geomcollection');
            $builder->seeColumnOfType('table1', 'geometry', 'geometry');

            $builder->create('table2', function (Blueprint $table) {

                $table->id('primary');
                $table->integer('votes');
                $table->ipAddress('visitor');
                $table->json('json');
                $table->jsonb('jsonb');
                $table->lineString('positions');
                $table->longText('description');
                $table->macAddress('device');
            });

            $builder->seeColumnOfType('table2', 'primary', 'bigint');
            $builder->seeColumnOfType('table2', 'votes', 'int');
            $builder->seeColumnOfType('table2', 'visitor', 'varchar');
            $builder->seeColumnOfType('table2', 'json', 'json');

            //expected type is json for jsonb,
            $builder->seeColumnOfType('table2', 'jsonb', 'json');
            $builder->seeColumnOfType('table2', 'positions', 'linestring');
            $builder->seeColumnOfType('table2', 'description', 'longtext');
            $builder->seeColumnOfType('table2', 'device', 'varchar');

            $builder->create('table3', function (Blueprint $table) {

                $table->increments('id');
                $table->nullableMorphs('taggable');
                $table->nullableUuidMorphs('likeable');
                $table->point('position');
                $table->polygon('polygon');
                $table->rememberToken();
                $table->set('flavors', ['strawberry', 'vanilla']);
            });

            $builder->seeColumnOfType('table3', 'id', 'int');
            $builder->seeColumnOfType('table3', 'taggable_id', 'bigint');
            $builder->seeColumnOfType('table3', 'taggable_type', 'varchar');
            $builder->seeColumnOfType('table3', 'likeable_id', 'char');
            $builder->seeColumnOfType('table3', 'likeable_type', 'varchar');
            $builder->seeColumnOfType('table3', 'position', 'point');
            $builder->seeColumnOfType('table3', 'polygon', 'polygon');
            $builder->seeColumnOfType('table3', 'remember_token', 'varchar');
            $builder->seeColumnOfType('table3', 'flavors', 'set');

            $builder->create('table4', function (Blueprint $table) {

                $table->mediumIncrements('id');
                $table->mediumInteger('votes');
                $table->mediumText('description');
                $table->morphs('taggable');
                $table->multiLineString('multiLineString');
                $table->multiPoint('multiPoint');
                $table->multiPolygon('multiPolygon');
                $table->nullableTimestamps(0);
            });

            $builder->seeColumnOfType('table4', 'id', 'mediumint');
            $builder->seeColumnOfType('table4', 'votes', 'mediumint');
            $builder->seeColumnOfType('table4', 'description', 'mediumtext');
            $builder->seeColumnOfType('table4', 'taggable_id', 'bigint');
            $builder->seeColumnOfType('table4', 'taggable_type', 'varchar');
            $builder->seeColumnOfType('table4', 'multiLineString', 'multilinestring');
            $builder->seeColumnOfType('table4', 'multiPoint', 'multipoint');
            $builder->seeColumnOfType('table4', 'multiPolygon', 'multipolygon');
            $builder->seeColumnOfType('table4', 'created_at', 'timestamp');
            $builder->seeColumnOfType('table4', 'updated_at', 'timestamp');

            $builder->create('table5', function (Blueprint $table) {


                $table->smallIncrements('id');
                $table->smallInteger('votes');
                $table->softDeletesTz($column = 'deleted_at_tz', $precision = 0);
                $table->softDeletes($column = 'deleted_at', $precision = 0);
                $table->string('name', 100);
                $table->text('description');
                $table->timeTz('sunrise_tz', $precision = 0);
                $table->time('sunrise', $precision = 0);
                $table->timestampTz('added_at_tz', $precision = 0);
                $table->timestamp('added_at', $precision = 0);
                $table->timestampsTz($precision = 0);
            });

            $builder->seeColumnOfType('table5', 'id', 'smallint');
            $builder->seeColumnOfType('table5', 'votes', 'smallint');
            $builder->seeColumnOfType('table5', 'deleted_at_tz', 'timestamp');
            $builder->seeColumnOfType('table5', 'deleted_at', 'timestamp');
            $builder->seeColumnOfType('table5', 'description', 'text');
            $builder->seeColumnOfType('table5', 'sunrise', 'time');
            $builder->seeColumnOfType('table5', 'sunrise_tz', 'time');
            $builder->seeColumnOfType('table5', 'added_at_tz', 'timestamp');
            $builder->seeColumnOfType('table5', 'added_at', 'timestamp');
            $builder->seeColumnOfType('table5', 'created_at', 'timestamp');
            $builder->seeColumnOfType('table5', 'updated_at', 'timestamp');

            $builder->create('table6', function (Blueprint $table) {

                $table->tinyIncrements('id');
                $table->tinyInteger('votes');
                $table->unsignedBigInteger('signature');
                $table->timestamps($precision = 0);
                $table->unsignedDecimal('amount', $precision = 8, $scale = 2);


            });

            $builder->seeColumnOfType('table6', 'id', 'tinyint');
            $builder->seeColumnOfType('table6', 'votes', 'tinyint');
            $builder->seeColumnOfType('table6', 'signature', 'bigint');
            $builder->seeColumnOfType('table6', 'created_at', 'timestamp');
            $builder->seeColumnOfType('table6', 'updated_at', 'timestamp');
            $builder->seeColumnOfType('table6', 'amount', 'decimal');

            $builder->create('table7', function (Blueprint $table) {

                $table->unsignedTinyInteger('vote_tiny');
                $table->unsignedInteger('votes');
                $table->unsignedMediumInteger('votes_medium');
                $table->unsignedSmallInteger('votes_small');
                $table->uuidMorphs('taggable');
                $table->uuid('uuid');
                $table->year('birth_year');
            });

            $builder->seeColumnOfType('table7', 'vote_tiny', 'tinyint');
            $builder->seeColumnOfType('table7', 'votes', 'int');
            $builder->seeColumnOfType('table7', 'votes_medium', 'mediumint');
            $builder->seeColumnOfType('table7', 'votes_small', 'smallint');
            $builder->seeColumnOfType('table7', 'taggable_id', 'char');
            $builder->seeColumnOfType('table7', 'taggable_type', 'varchar');
            $builder->seeColumnOfType('table7', 'uuid', 'char');
            $builder->seeColumnOfType('table7', 'birth_year', 'year');


        }


        /** @test */
        public function columns_can_be_added_with_column_modifiers()
        {

            $builder = $this->newTestBuilder();

            $builder->create('table1', function (Blueprint $table) {

                $table->string('email')->nullable();
                $table->integer('last')->autoIncrement();


            });

            $builder->table('table1', function (Blueprint $table) {

                $table->string('votes')->after('email');

                $table->after('votes', function ($table) {
                    $table->string('address_line1');
                    $table->string('address_line2');
                    $table->string('city');
                });

                $table->string('first')->first();

            });


            $builder->seeColumnOfType('table1', 'first', 'varchar');
            $builder->seeColumnOfType('table1', 'email', 'varchar');
            $builder->seeColumnOfType('table1', 'last', 'int');
            $builder->seeColumnOfType('table1', 'votes', 'varchar');
            $builder->seeColumnOfType('table1', 'address_line1', 'varchar');
            $builder->seeColumnOfType('table1', 'address_line2', 'varchar');
            $builder->seeColumnOfType('table1', 'city', 'varchar');


            $builder->create('table2', function (Blueprint $table) {

                $table->bigIncrements('id')->from(100);


            });

            $builder->seeColumnOfType('table2', 'id', 'bigint');

        }


        private function newSchemaBuilder()
        {

            global $wpdb;

            $wp_connection = new WpConnection($wpdb);

            return $wp_connection->getSchemaBuilder();


        }

        private function newUserTable(Builder $builder = null)
        {


            $builder = $builder ?? $this->newSchemaBuilder();

            $builder->create('test_users', function (Blueprint $table) {

                $table->id();
                $table->string('name');
                $table->string('email');
                $table->timestamps();
            });


        }

        private function newTestBuilder()
        {

            global $wpdb;

            $wp_connection = new WpConnection($wpdb);

            return new ColumnTester($wp_connection->getSchemaBuilder());


        }

    }


    /**
     * Class ColumnTester
     *
     * @see MySqlSchemaBuilder
     */
    class ColumnTester
    {


        /**
         * @var MySqlSchemaBuilder
         */
        private $builder;

        public function __construct(MySqlSchemaBuilder $builder)
        {

            $this->builder = $builder;
        }

        public function seeColumnOfType($table, $column, $type)
        {

            assertTrue($this->builder->hasColumn($table, $column),
                'Column: '.$column.' not found.');
            assertSame($type, $this->builder->getColumnType($table, $column),
                'Column types dont match for colum: '.$column);

        }


        public function create($table, \Closure $closure)
        {

            $this->builder->create($table, $closure);

        }

        public function table($table, \Closure $closure)
        {

            $this->builder->table($table, $closure);

        }




    }