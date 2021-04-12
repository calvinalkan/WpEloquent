<?php


    namespace Tests\integration\Schema;

    use Codeception\TestCase\WPTestCase;
    use Illuminate\Database\Query\Expression;
    use Illuminate\Database\Schema\Blueprint;
    use Illuminate\Database\Schema\Builder;
    use Illuminate\Support\Str;
    use WpEloquent\ExtendsWpdb\WpdbInterface;
    use WpEloquent\MySqlSchemaBuilder;
    use WpEloquent\WpConnection;

    use function PHPUnit\Framework\assertEmpty;
    use function PHPUnit\Framework\assertFalse;
    use function PHPUnit\Framework\assertSame;
    use function PHPUnit\Framework\assertTrue;

    /**
     * Class SchemaBuilderTest
     *
     * @todo Check that timezone functionality works.
     *
     */
    class SchemaBuilderTest extends WPTestCase
    {

        /**
         * @var \UnitTester;
         */
        protected $tester;

        /**
         * @var MySqlSchemaBuilder
         */
        private $builder;

        /**
         * @var WpConnection
         */
        private $wp_conn;


        protected function setUp() : void
        {

            parent::setUp();

            $this->wp_conn = new TestWpConnection(new SchemaBuilderWpDb());
            $this->builder = new MySqlSchemaBuilder($this->wp_conn);
        }


        private function assertDidQuery ($query) {

            $queries = $this->wp_conn->getLog();

            assertTrue(in_array($query, $queries));

        }

        /** @test */
        public function a_basic_table_can_be_created()
        {


            $this->builder->create('test_table', function (Blueprint $table) {

                $table->id();

            });

            $queries = $this->wp_conn->getLog();

            $this->assertDidQuery("create table `wp_test_table` (`id` bigint unsigned not null auto_increment primary key)");

        }


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
        public
        function table_existence_can_be_checked()
        {


            $schema_builder = $this->newSchemaBuilder();

            self::assertFalse($schema_builder->hasTable('test_table'));

            $schema_builder->create('test_table', function (Blueprint $table) {

                $table->id();

            });

            self::assertTrue($schema_builder->hasTable('test_table'));


        }

        /** @test */
        public
        function column_existence_can_be_checked()
        {

            $schema_builder = $this->newSchemaBuilder();

            self::assertTrue($schema_builder->hasColumn('users', 'user_login'));
            self::assertFalse($schema_builder->hasColumn('users', 'user_profile_pic'));

            self::assertFalse($schema_builder->hasColumns('users',
                ['user_login', 'user_profile_pic']));
            self::assertTrue($schema_builder->hasColumns('users', ['user_login', 'user_email']));

        }

        /** @test */
        public
        function an_existing_table_can_be_updated()
        {

            $schema_builder = $this->newSchemaBuilder();

            $schema_builder->create('test_users', function (Blueprint $table) {

                $table->id();
                $table->string('name');
                $table->string('email');
                $table->timestamps();
            });

            assertFalse($schema_builder->hasColumn('test_users', 'phone'));

            $schema_builder->modify('test_users', function (Blueprint $table) {

                $table->string('phone');

            });

            assertTrue($schema_builder->hasColumn('test_users', 'phone'));


        }

        /** @test */
        public
        function an_existing_column_can_be_renamed()
        {

            $this->newUserTable($builder = $this->newSchemaBuilder());

            assertTrue($builder->hasTable('test_users'));

            $builder->rename('test_users', 'test_users_new');

            assertTrue($builder->hasTable('test_users_new'));
            assertFalse($builder->hasTable('test_users'));


        }

        /** @test */
        public
        function a_table_can_be_dropped()
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
        public
        function columns_can_be_dropped()
        {

            $this->newUserTable($builder = $this->newSchemaBuilder());

            $builder->dropColumns('test_users', ['id', 'name', 'email']);

            assertFalse($builder->hasColumn('test_users', 'name'));
            assertFalse($builder->hasColumn('test_users', 'email'));
            assertFalse($builder->hasColumn('test_users', 'id'));

            $builder->create('table1', function (Blueprint $table) {

                $table->id();
                $table->string('name');
                $table->string('email');
                $table->string('phone');

            });

            assertTrue($builder->hasColumn('table1', 'name'));
            assertTrue($builder->hasColumn('table1', 'email'));
            assertTrue($builder->hasColumn('table1', 'phone'));

            $builder->modify('table1', function (Blueprint $table) {


                $table->dropColumn(['email', 'phone']);
                $table->dropColumn('name');

            });

            assertFalse($builder->hasColumn('table1', 'name'));
            assertFalse($builder->hasColumn('table1', 'email'));
            assertFalse($builder->hasColumn('table1', 'phone'));

        }

        /** @test */
        public
        function all_tables_can_be_retrieved()
        {


            $this->newUserTable($builder = $this->newSchemaBuilder());

            $tables = $builder->getAllTables();

            global $wpdb;

            $expected = array_values($wpdb->tables());

            $expected[] = 'wp_test_users';

            $this->assertEmpty(array_diff($expected, $tables));


        }

        /** @test */
        public
        function all_tables_can_be_dropped()
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
        public
        function the_column_type_can_be_found_for_the_last_query()
        {

            $builder = $this->newTestBuilder();

            $builder->create('table1', function (Blueprint $table) {

                $table->id();
                $table->string('email');

            });

            assertSame('varchar(255)', $builder->getColumnType('table1', 'email'));

        }

        /**
         *
         *
         *
         *
         *
         *
         *
         * TEST FOR CREATING EVERY COLUMN TYPE.
         *
         *
         *
         *
         *
         *
         *
         */

        /** @test */
        public
        function big_increments_works()
        {


            $builder = $this->newTestBuilder('table1');

            $builder->create('table1', function (Blueprint $table) {

                $table->bigIncrements('id');

            });

            $builder->seeColumnOfType('id', 'bigint unsigned');

            $builder->seePrimaryKey('id');

        }

        /** @test */
        public
        function big_integer_works()
        {

            $builder = $this->newTestBuilder('table1');

            $builder->create('table1', function (Blueprint $table) {

                $table->bigInteger('votes');

            });

            $builder->seeColumnOfType('votes', 'bigint');


        }

        /** @test */
        public
        function binary_works()
        {

            $builder = $this->newTestBuilder('table1');

            $builder->create('table1', function (Blueprint $table) {

                $table->binary('photo');

            });

            $builder->seeColumnOfType('photo', 'blob');


        }

        /** @test */
        public
        function boolean_works()
        {

            $builder = $this->newTestBuilder('table1');

            $builder->create('table1', function (Blueprint $table) {

                $table->boolean('confirmed');

            });

            $builder->seeColumnOfType('confirmed', 'tinyint(1)');


        }

        /** @test */
        public
        function char_works()
        {

            $builder = $this->newTestBuilder('table1');

            $builder->create('table1', function (Blueprint $table) {

                $table->char('name', 100);
                $table->char('email', 255);

            });

            $builder->seeColumnOfType('name', 'char(100)');
            $builder->seeColumnOfType('email', 'char(255)');


        }

        /** @test */
        public
        function date_time_tz_works()
        {

            $builder = $this->newTestBuilder('table1');

            $builder->create('table1', function (Blueprint $table) {

                $table->dateTimeTz('created_at', 1);
                $table->dateTimeTz('created_at_precise', 2);

            });

            $builder->seeColumnOfType('created_at', 'datetime(1)');
            $builder->seeColumnOfType('created_at_precise', 'datetime(2)');


        }

        /** @test */
        public
        function date_time_works()
        {

            $builder = $this->newTestBuilder('table1');

            $builder->create('table1', function (Blueprint $table) {

                $table->dateTime('created_at', 1);
                $table->dateTime('created_at_precise', 2);

            });

            $builder->seeColumnOfType('created_at', 'datetime(1)');
            $builder->seeColumnOfType('created_at_precise', 'datetime(2)');


        }

        /** @test */
        public
        function date_works()
        {

            $builder = $this->newTestBuilder('table1');

            $builder->create('table1', function (Blueprint $table) {

                $table->date('date');

            });

            $builder->seeColumnOfType('date', 'date');


        }

        /** @test */
        public
        function decimal_works()
        {

            $builder = $this->newTestBuilder('table1');

            $builder->create('table1', function (Blueprint $table) {

                $table->decimal('money');
                $table->decimal('vote_count', 10, 3);

            });

            $builder->seeColumnOfType('money', 'decimal(8,2)');
            $builder->seeColumnOfType('vote_count', 'decimal(10,3)');


        }

        /** @test */
        public
        function double_works()
        {

            $builder = $this->newTestBuilder('table1');

            $builder->create('table1', function (Blueprint $table) {

                $table->double('money');
                $table->double('vote_count', 10, 3);

            });

            $builder->seeColumnOfType('money', 'double');
            $builder->seeColumnOfType('vote_count', 'double(10,3)');


        }

        /** @test */
        public
        function enum_works()
        {

            $builder = $this->newTestBuilder('table1');

            $builder->create('table1', function (Blueprint $table) {

                $table->enum('difficulty', ['easy', 'hard']);

            });

            $builder->seeColumnOfType('difficulty', "enum('easy','hard')");


        }

        /** @test */
        public
        function float_works()
        {

            $builder = $this->newTestBuilder('table1');

            $builder->create('table1', function (Blueprint $table) {

                $table->float('amount');
                $table->float('money', 10, 3);

            });

            $builder->seeColumnOfType('amount', 'double(8,2)');
            $builder->seeColumnOfType('money', 'double(10,3)');


        }

        /** @test */
        public
        function foreign_id_works()
        {

            $builder = $this->newTestBuilder('table1');

            $builder->create('table1', function (Blueprint $table) {

                $table->foreignId('user_id');

            });

            $builder->seeColumnOfType('user_id', 'bigint unsigned');


        }

        /** @test */
        public
        function geometry_collection_works()
        {

            $builder = $this->newTestBuilder('table1');

            $builder->create('table1', function (Blueprint $table) {

                $table->geometryCollection('positions');

            });

            $builder->seeColumnOfType('positions', 'geomcollection');


        }

        /** @test */
        public
        function id_works()
        {

            $builder = $this->newTestBuilder('table1');

            $builder->create('table1', function (Blueprint $table) {

                $table->id('ID');

            });

            $builder->seeColumnOfType('ID', 'bigint unsigned');
            $builder->seePrimaryKey('ID');

        }

        /** @test */
        public
        function increments_works()
        {

            $builder = $this->newTestBuilder('table1');

            $builder->create('table1', function (Blueprint $table) {

                $table->increments('id');

            });

            $builder->seeColumnOfType('id', 'int unsigned');
            $builder->seePrimaryKey('id');

        }

        /** @test */
        public
        function integer_works()
        {

            $builder = $this->newTestBuilder('table1');

            $builder->create('table1', function (Blueprint $table) {

                $table->integer('amount');

            });

            $builder->seeColumnOfType('amount', 'int');


        }

        /** @test */
        public
        function ip_address_works()
        {

            $builder = $this->newTestBuilder('table1');

            $builder->create('table1', function (Blueprint $table) {

                $table->ipAddress('visitor');

            });

            $builder->seeColumnOfType('visitor', 'varchar(45)');


        }

        /** @test */
        public
        function json_works()
        {

            $builder = $this->newTestBuilder('table1');

            $builder->create('table1', function (Blueprint $table) {

                $table->json('options');

            });

            $builder->seeColumnOfType('options', 'json');


        }

        /** @test */
        public
        function json_b_works()
        {

            $builder = $this->newTestBuilder('table1');

            $builder->create('table1', function (Blueprint $table) {

                $table->jsonB('options');

            });

            $builder->seeColumnOfType('options', 'json');

        }

        /** @test */
        public
        function line_string_works()
        {

            $builder = $this->newTestBuilder('table1');

            $builder->create('table1', function (Blueprint $table) {

                $table->lineString('position');

            });

            $builder->seeColumnOfType('position', 'linestring');


        }

        /** @test */
        public
        function long_text_works()
        {

            $builder = $this->newTestBuilder('table1');

            $builder->create('table1', function (Blueprint $table) {

                $table->longText('description');

            });

            $builder->seeColumnOfType('description', 'longtext');


        }

        /** @test */
        public
        function mac_address_works()
        {

            $builder = $this->newTestBuilder('table1');

            $builder->create('table1', function (Blueprint $table) {

                $table->macAddress('device');

            });

            $builder->seeColumnOfType('device', 'varchar(17)');


        }

        /** @test */
        public
        function medium_increments_works()
        {

            $builder = $this->newTestBuilder('table1');

            $builder->create('table1', function (Blueprint $table) {

                $table->mediumIncrements('id');

            });

            $builder->seeColumnOfType('id', 'mediumint unsigned');
            $builder->seePrimaryKey('id');

        }

        /** @test */
        public
        function medium_integer_works()
        {

            $builder = $this->newTestBuilder('table1');

            $builder->create('table1', function (Blueprint $table) {

                $table->mediumInteger('votes');

            });

            $builder->seeColumnOfType('votes', 'mediumint');


        }

        /** @test */
        public
        function medium_text_works()
        {

            $builder = $this->newTestBuilder('table1');

            $builder->create('table1', function (Blueprint $table) {

                $table->mediumText('descriptions');

            });

            $builder->seeColumnOfType('descriptions', 'mediumtext');


        }

        /** @test */
        public
        function morphs_works()
        {

            $builder = $this->newTestBuilder('table1');

            $builder->create('table1', function (Blueprint $table) {

                $table->morphs('taggable');

            });

            $builder->seeColumnOfType('taggable_id', 'bigint unsigned');
            $builder->seeColumnOfType('taggable_type', 'varchar(255)');


        }

        /** @test */
        public
        function multi_line_string_works()
        {

            $builder = $this->newTestBuilder('table1');

            $builder->create('table1', function (Blueprint $table) {

                $table->multiLineString('positions');

            });

            $builder->seeColumnOfType('positions', 'multilinestring');

        }

        /** @test */
        public
        function multi_point_works()
        {

            $builder = $this->newTestBuilder('table1');

            $builder->create('table1', function (Blueprint $table) {

                $table->multiPoint('positions');

            });

            $builder->seeColumnOfType('positions', 'multipoint');


        }

        /** @test */
        public
        function multi_polygon_works()
        {

            $builder = $this->newTestBuilder('table1');

            $builder->create('table1', function (Blueprint $table) {

                $table->multiPolygon('positions');

            });

            $builder->seeColumnOfType('positions', 'multipolygon');


        }

        /** @test */
        public
        function nullable_timestamps_works()
        {

            $builder = $this->newTestBuilder('table1');

            $builder->create('table1', function (Blueprint $table) {

                $table->nullableTimestamps('1');

            });

            $builder->seeColumnOfType('created_at', 'timestamp(1)');
            $builder->seeColumnOfType('updated_at', 'timestamp(1)');
            assertTrue($builder->seeNullableColumn('created_at'));
            assertTrue($builder->seeNullableColumn('updated_at'));

        }

        /** @test */
        public
        function nullable_morphs_works()
        {

            $builder = $this->newTestBuilder('table1');

            $builder->create('table1', function (Blueprint $table) {

                $table->nullableMorphs('taggable');

            });

            $builder->seeColumnOfType('taggable_id', 'bigint unsigned');
            $builder->seeColumnOfType('taggable_type', 'varchar(255)');
            assertTrue($builder->seeNullableColumn('taggable_id'));
            assertTrue($builder->seeNullableColumn('taggable_type'));

        }

        /** @test */
        public
        function nullable_uuid_morphs_works()
        {

            $builder = $this->newTestBuilder('table1');

            $builder->create('table1', function (Blueprint $table) {

                $table->nullableUuidMorphs('taggable');

            });

            $builder->seeColumnOfType('taggable_id', 'char(36)');
            $builder->seeColumnOfType('taggable_type', 'varchar(255)');
            assertTrue($builder->seeNullableColumn('taggable_id'));
            assertTrue($builder->seeNullableColumn('taggable_type'));


        }

        /** @test */
        public
        function point_works()
        {

            $builder = $this->newTestBuilder('table1');

            $builder->create('table1', function (Blueprint $table) {

                $table->point('position');

            });

            $builder->seeColumnOfType('position', 'point');


        }

        /** @test */
        public
        function polygon_works()
        {

            $builder = $this->newTestBuilder('table1');

            $builder->create('table1', function (Blueprint $table) {

                $table->polygon('position');

            });

            $builder->seeColumnOfType('position', 'polygon');


        }

        /** @test */
        public
        function remember_token_works()
        {

            $builder = $this->newTestBuilder('table1');

            $builder->create('table1', function (Blueprint $table) {

                $table->rememberToken();

            });

            $builder->seeColumnOfType('remember_token', 'varchar(100)');


        }

        /** @test */
        public
        function set_works()
        {

            $builder = $this->newTestBuilder('table1');

            $builder->create('table1', function (Blueprint $table) {

                $table->set('flavors', ['strawberry', 'vanilla']);


            });

            $builder->seeColumnOfType('flavors', "set('strawberry','vanilla')");


        }

        /** @test */
        public
        function small_increments_works()
        {

            $builder = $this->newTestBuilder('table1');

            $builder->create('table1', function (Blueprint $table) {

                $table->smallIncrements('id');

            });

            $builder->seeColumnOfType('id', 'smallint unsigned');

            $builder->seePrimaryKey('id');

        }

        /** @test */
        public
        function small_integer_works()
        {

            $builder = $this->newTestBuilder('table1');

            $builder->create('table1', function (Blueprint $table) {

                $table->smallInteger('amount');

            });

            $builder->seeColumnOfType('amount', 'smallint');


        }

        /** @test */
        public
        function soft_deletes_tz_works()
        {

            $builder = $this->newTestBuilder('table1');

            $builder->create('table1', function (Blueprint $table) {

                $table->softDeletesTz('deleted_at');
                $table->softDeletesTz('deleted_at_precise', 2);

            });

            $builder->seeColumnOfType('deleted_at', 'timestamp');
            $builder->seeColumnOfType('deleted_at_precise', 'timestamp(2)');


        }

        /** @test */
        public
        function soft_deletes_works()
        {

            $builder = $this->newTestBuilder('table1');

            $builder->create('table1', function (Blueprint $table) {

                $table->softDeletes('deleted_at');
                $table->softDeletes('deleted_at_precise', 2);

            });

            $builder->seeColumnOfType('deleted_at', 'timestamp');
            $builder->seeColumnOfType('deleted_at_precise', 'timestamp(2)');


        }

        /** @test */
        public
        function string_works()
        {

            $builder = $this->newTestBuilder('table1');

            $builder->create('table1', function (Blueprint $table) {

                $table->string('name', 55);

            });

            $builder->seeColumnOfType('name', 'varchar(55)');


        }

        /** @test */
        public
        function text_works()
        {

            $builder = $this->newTestBuilder('table1');

            $builder->create('table1', function (Blueprint $table) {

                $table->text('description');

            });

            $builder->seeColumnOfType('description', 'text');


        }

        /** @test */
        public
        function time_tz_works()
        {

            $builder = $this->newTestBuilder('table1');

            $builder->create('table1', function (Blueprint $table) {

                $table->timeTz('sunrise', 2);

            });

            $builder->seeColumnOfType('sunrise', 'time(2)');


        }

        /** @test */
        public
        function time_works()
        {

            $builder = $this->newTestBuilder('table1');

            $builder->create('table1', function (Blueprint $table) {

                $table->time('sunrise', 2);

            });

            $builder->seeColumnOfType('sunrise', 'time(2)');


        }

        /** @test */
        public
        function timestamp_tz_works()
        {

            $builder = $this->newTestBuilder('table1');

            $builder->create('table1', function (Blueprint $table) {

                $table->timestampTz('added_at', 2);

            });

            $builder->seeColumnOfType('added_at', 'timestamp(2)');

        }

        /** @test */
        public
        function timestamp_works()
        {

            $builder = $this->newTestBuilder('table1');

            $builder->create('table1', function (Blueprint $table) {

                $table->timestamp('added_at', 2);

            });

            $builder->seeColumnOfType('added_at', 'timestamp(2)');

        }

        /** @test */
        public
        function timestamps_tz_works()
        {

            $builder = $this->newTestBuilder('table1');

            $builder->create('table1', function (Blueprint $table) {

                $table->timestampsTz(2);

            });

            $builder->seeColumnOfType('created_at', 'timestamp(2)');
            $builder->seeColumnOfType('updated_at', 'timestamp(2)');

        }

        /** @test */
        public
        function timestamps_works()
        {

            $builder = $this->newTestBuilder('table1');

            $builder->create('table1', function (Blueprint $table) {

                $table->timestamps(2);

            });

            $builder->seeColumnOfType('created_at', 'timestamp(2)');
            $builder->seeColumnOfType('updated_at', 'timestamp(2)');

        }

        /** @test */
        public
        function tiny_increments_works()
        {

            $builder = $this->newTestBuilder('table1');

            $builder->create('table1', function (Blueprint $table) {

                $table->tinyIncrements('id');

            });

            $builder->seeColumnOfType('id', 'tinyint unsigned');

            $builder->seePrimaryKey('id');


        }

        /** @test */
        public
        function tiny_integer_works()
        {

            $builder = $this->newTestBuilder('table1');

            $builder->create('table1', function (Blueprint $table) {

                $table->tinyInteger('amount');

            });

            $builder->seeColumnOfType('amount', 'tinyint');


        }

        /** @test */
        public
        function unsigned_big_integer_works()
        {

            $builder = $this->newTestBuilder('table1');

            $builder->create('table1', function (Blueprint $table) {

                $table->unsignedBigInteger('votes');

            });

            $builder->seeColumnOfType('votes', 'bigint unsigned');


        }

        /** @test */
        public
        function unsigned_decimal_works()
        {

            $builder = $this->newTestBuilder('table1');

            $builder->create('table1', function (Blueprint $table) {

                $table->unsignedDecimal('votes', '10', '2');

            });

            $builder->seeColumnOfType('votes', 'decimal(10,2) unsigned');


        }

        /** @test */
        public
        function unsigned_integer_works()
        {

            $builder = $this->newTestBuilder('table1');

            $builder->create('table1', function (Blueprint $table) {

                $table->unsignedInteger('votes');

            });

            $builder->seeColumnOfType('votes', 'int unsigned');


        }

        /** @test */
        public
        function unsigned_medium_integer_works()
        {

            $builder = $this->newTestBuilder('table1');

            $builder->create('table1', function (Blueprint $table) {

                $table->unsignedMediumInteger('votes');

            });

            $builder->seeColumnOfType('votes', 'mediumint unsigned');


        }

        /** @test */
        public
        function unsigned_small_integer_works()
        {

            $builder = $this->newTestBuilder('table1');

            $builder->create('table1', function (Blueprint $table) {

                $table->unsignedSmallInteger('votes');

            });

            $builder->seeColumnOfType('votes', 'smallint unsigned');


        }

        /** @test */
        public
        function unsigned_tiny_int_works()
        {

            $builder = $this->newTestBuilder('table1');

            $builder->create('table1', function (Blueprint $table) {

                $table->unsignedTinyInteger('votes');

            });

            $builder->seeColumnOfType('votes', 'tinyint unsigned');


        }

        /** @test */
        public
        function uuid_morphs_works()
        {

            $builder = $this->newTestBuilder('table1');

            $builder->create('table1', function (Blueprint $table) {

                $table->uuidMorphs('taggable');

            });

            $builder->seeColumnOfType('taggable_id', 'char(36)');
            $builder->seeColumnOfType('taggable_type', 'varchar(255)');


        }

        /** @test */
        public
        function uuid_works()
        {

            $builder = $this->newTestBuilder('table1');

            $builder->create('table1', function (Blueprint $table) {

                $table->uuid('id');

            });

            $builder->seeColumnOfType('id', 'char(36)');


        }

        /** @test */
        public
        function year_works()
        {

            $builder = $this->newTestBuilder('table1');

            $builder->create('table1', function (Blueprint $table) {

                $table->year('birt_year');

            });

            $builder->seeColumnOfType('birt_year', 'year');


        }

        /**
         *
         *
         *
         *
         *
         *
         * TEST FOR MODIFYING COLUMNS
         *
         *
         *
         *
         *
         *
         *
         *
         */

        /** @test */
        public
        function new_columns_can_be_inserted_after_existing_columns()
        {


            $builder = $this->newTestBuilder();

            $builder->create('table1', function (Blueprint $table) {

                $table->string('first_name');
                $table->string('email');


            });

            // Without after method()
            $builder->modify('table1', function (Blueprint $table) {

                $table->string('last_name');
                $table->string('phone');

            });

            assertSame(['first_name', 'email', 'last_name', 'phone'],
                $builder->getColumnsByOrdinalPosition('table1'));

            $builder->dropColumns('table1', ['last_name', 'phone']);

            // With after() method
            $builder->modify('table1', function (Blueprint $table) {

                $table->string('last_name')->after('first_name');
                $table->string('phone')->after('last_name');

            });

            assertSame(
                ['first_name', 'last_name', 'phone', 'email'],
                $builder->getColumnsByOrdinalPosition('table1')
            );

            $builder->drop('table1');

            $builder->create('table1', function (Blueprint $table) {

                $table->string('name');
                $table->string('email');


            });

            assertSame(
                ['name', 'email'],
                $builder->getColumnsByOrdinalPosition('table1')
            );

            $builder->modify('table1', function (Blueprint $table) {

                $table->after('name', function ($table) {

                    $table->string('address_line1');
                    $table->string('address_line2');
                    $table->string('city');
                });


            });

            assertSame(
                ['name', 'address_line1', 'address_line2', 'city', 'email'],
                $builder->getColumnsByOrdinalPosition('table1')
            );


        }

        /** @test */
        public
        function auto_incrementing_works()
        {

            $builder = $this->newTestBuilder('table1');

            $builder->create('table1', function (Blueprint $table) {


                $table->integer('user_id')->autoIncrement();
                $table->string('email');


            });

            $builder->seeColumnOfType('user_id', 'int');

            $this->tester->haveInDatabase('wp_table1', ['email' => 'calvin@gmail.com']);
            $this->tester->haveInDatabase('wp_table1',
                ['user_id' => 10, 'email' => 'calvin@gmx.com']);
            $this->tester->haveInDatabase('wp_table1', ['email' => 'calvin@web.com']);

            $this->tester->seeInDatabase('wp_table1',
                ['user_id' => 1, 'email' => 'calvin@gmail.com']);
            $this->tester->seeInDatabase('wp_table1',
                ['user_id' => 10, 'email' => 'calvin@gmx.com']);
            $this->tester->seeInDatabase('wp_table1',
                ['user_id' => 11, 'email' => 'calvin@web.com']);


        }

        /** @test */
        public
        function charset_can_be_set_for_table_and_column()
        {


            $builder = $this->newTestBuilder();

            $builder->create('table1', function (Blueprint $table) {


                $table->charset = 'utf8mb4';
                $table->id();
                $table->string('name')->charset('latin1')->collation('latin1_german1_ci');

            });

            $this->tester->seeTableInDatabase('wp_table1');

            assertSame('utf8mb4', $builder->getTableCharset('table1'));

            $columns = $builder->getFullColumnInfo('table1');

            assertSame('latin1', Str::before($columns['name']['Collation'], '_'));


        }

        /** @test */
        public
        function collation_can_be_set_for_table_an_column()
        {

            $builder = $this->newTestBuilder();

            $builder->create('table1', function (Blueprint $table) {

                $table->collation = 'utf8mb4_unicode_ci';
                $table->id();
                $table->string('name')->collation('latin1_german1_ci');

            });

            $this->tester->seeTableInDatabase('wp_table1');

            assertSame('utf8mb4_unicode_ci', $builder->getTableCollation('table1'));

            $columns = $builder->getFullColumnInfo('table1');

            assertSame('latin1_german1_ci', $columns['name']['Collation']);


        }

        /** @test */
        public
        function comments_can_be_added()
        {

            $builder = $this->newTestBuilder();

            $builder->create('table1', function (Blueprint $table) {

                $table->id();
                $table->string('name')->comment('My comment');

            });

            $this->tester->seeTableInDatabase('wp_table1');

            $name_col = $builder->getFullColumnInfo('table1')['name'];

            assertSame('My comment', $name_col['Comment']);

        }

        /** @test */
        public
        function a_default_value_can_be_set()
        {

            $builder = $this->newTestBuilder();

            $builder->create('table1', function (Blueprint $table) {

                $table->id();
                $table->integer('count')->default(10);
                $table->string('name')->default('calvin alkan');
                $table->json('movies')->default(new Expression('(JSON_ARRAY())'));
            });

            try {

                $this->tester->haveInDatabase('wp_table1', ['id' => 1]);

                $this->tester->seeInDatabase(
                    'wp_table1',
                    ['id' => 1, 'count' => 10, 'name' => 'calvin alkan',]
                );

            }

            catch (\PDOException $e) {

                $this->fail($e->getMessage());

            }


        }

        /** @test */
        public
        function a_column_can_be_added_at_the_first_place()
        {

            $builder = $this->newTestBuilder();

            $builder->create('table1', function (Blueprint $table) {

                $table->id();
                $table->integer('count');
                $table->string('name');

            });

            assertSame(['id', 'count', 'name'], $builder->getColumnsByOrdinalPosition('table1'));

            $builder->modify('table1', function (Blueprint $table) {


                $table->string('email')->first();


            });

            assertSame(['email', 'id', 'count', 'name'],
                $builder->getColumnsByOrdinalPosition('table1'));

        }

        /** @test */
        public
        function a_column_can_be_nullable()
        {

            $builder = $this->newTestBuilder('table1');

            $builder->create('table1', function (Blueprint $table) {

                $table->id();
                $table->string('email')->nullable(false);

            });

            $builder->seeColumnOfType('id', 'bigint unsigned');
            $builder->seeColumnOfType('email', 'varchar(255)');

            try {

                $this->tester->haveInDatabase('wp_table1', ['id' => 1]);
                $this->fail('Non-nullable column was created without default value');

            }
            catch (\PDOException $e) {

                assertSame("SQLSTATE[HY000]: General error: 1364 Field 'email' doesn't have a default value",
                    $e->getMessage());

            }

            $builder->dropColumns('table1', 'email');

            $builder->modify('table1', function (Blueprint $table) {

                $table->string('email')->nullable(true);

            });

            try {

                $this->tester->haveInDatabase('wp_table1', ['id' => 1]);

                assertTrue(true);

            }
            catch (\PDOException $e) {

                $this->fail($e->getMessage());

            }


        }

        /** @test */
        public
        function a_stored_column_can_be_created()
        {

            $builder = $this->newTestBuilder();

            $builder->create('table1', function (Blueprint $table) {

                $table->id();
                $table->string('first_name');
                $table->string('last_name');
                $table->string('full_name')->storedAs("CONCAT(first_name,' ',last_name)");
                $table->integer('price');
                $table->integer('discounted_price')->storedAs('price -5')->unsigned();

            });

            try {

                $this->tester->haveInDatabase('wp_table1', [
                    'id' => 1, 'first_name' => 'calvin', 'last_name' => 'alkan', 'price' => 10,
                ]);

                global $wpdb;

                $expected = [

                    '1',
                    'calvin',
                    'alkan',
                    'calvin alkan',
                    '10',
                    '5',

                ];

                assertSame($expected,
                    $wpdb->get_row("select * from `wp_table1` where `id` = '1'", ARRAY_N));

            }
            catch (\PDOException $e) {

                $this->fail($e->getMessage());

            }

        }

        /** @test */
        public
        function a_virtual_column_can_be_created()
        {

            $builder = $this->newTestBuilder();

            $builder->create('table1', function (Blueprint $table) {

                $table->id();
                $table->string('first_name');
                $table->string('last_name');
                $table->string('full_name')->virtualAs("CONCAT(first_name,' ',last_name)");
                $table->integer('price');
                $table->integer('discounted_price')->virtualAs('price -5')->unsigned();

            });

            try {

                $this->tester->haveInDatabase('wp_table1', [
                    'id' => 1, 'first_name' => 'calvin', 'last_name' => 'alkan', 'price' => 10,
                ]);

                global $wpdb;

                $expected = [

                    '1',
                    'calvin',
                    'alkan',
                    'calvin alkan',
                    '10',
                    '5',

                ];

                assertSame($expected,
                    $wpdb->get_row("select * from `wp_table1` where `id` = '1'", ARRAY_N));

            }
            catch (\PDOException $e) {

                $this->fail($e->getMessage());

            }


        }

        /** @test */
        public
        function integers_can_be_unsigned()
        {

            $builder = $this->newTestBuilder();

            $builder->create('table1', function (Blueprint $table) {

                $table->id();
                $table->integer('price')->unsigned();

            });

            try {

                $this->tester->haveInDatabase('wp_table1', ['id' => 1, 'price' => 10]);

                global $wpdb;

                $expected = ['1', '10'];

                assertSame($expected,
                    $wpdb->get_row("select * from `wp_table1` where `id` = '1'", ARRAY_N));

            }
            catch (\PDOException $e) {

                $this->fail($e->getMessage());

            }

            try {

                $this->tester->haveInDatabase('wp_table1', ['id' => 1, 'price' => -10]);

                $this->fail('Negative value inserted for unsigned interger');

            }
            catch (\PDOException $e) {

                assertSame(
                    "SQLSTATE[22003]: Numeric value out of range: 1264 Out of range value for column 'price' at row 1",
                    $e->getMessage()
                );


            }


        }

        /** @test */
        public
        function timestamps_can_use_the_current_time_as_default()
        {

            $builder = $this->newTestBuilder();

            $builder->create('table1', function (Blueprint $table) {

                $table->id();
                $table->timestamp('time')->nullable();

            });

            try {

                $this->tester->haveInDatabase('wp_table1', ['id' => 1]);

                global $wpdb;

                $expected = ['1', null];

                assertSame($expected,
                    $wpdb->get_row("select * from `wp_table1` where `id` = '1'", ARRAY_N));

            }
            catch (\PDOException $e) {

                $this->fail($e->getMessage());

            }

            $builder->drop('table1');

            $builder->create('table1', function (Blueprint $table) {

                $table->id();
                $table->timestamp('time')->useCurrent();

            });

            try {


                $this->tester->haveInDatabase('wp_table1', ['id' => 1]);

                global $wpdb;

                $row = $wpdb->get_row("select * from `wp_table1` where `id` = '1'", ARRAY_N);

                assertSame('1', $row[0]);
                self::assertNotNull($row[1]);


            }
            catch (\PDOException $e) {

                $this->fail($e->getMessage());

            }


        }

        /**
         *
         *
         *
         *
         *
         *
         * TESTS FOR DROPPING COLUMNS WITH ALIASES
         *
         *
         *
         *
         *
         *
         */

        /** @test */
        public
        function test_drop_morphs_works()
        {

            $builder = $this->newTestBuilder();

            $builder->create('table1', function (Blueprint $table) {

                $table->id();
                $table->morphs('taggable');


            });

            assertSame(['id', 'taggable_type', 'taggable_id'],
                $builder->getColumnsByOrdinalPosition('table1'));

            $builder->modify('table1', function (Blueprint $table) {

                $table->dropMorphs('taggable');

            });

            assertSame(['id'], $builder->getColumnsByOrdinalPosition('table1'));

        }

        /** @test */
        public
        function test_remember_token_works()
        {

            $builder = $this->newTestBuilder();

            $builder->create('table1', function (Blueprint $table) {

                $table->id();
                $table->rememberToken();


            });

            assertSame(['id', 'remember_token'], $builder->getColumnsByOrdinalPosition('table1'));

            $builder->modify('table1', function (Blueprint $table) {

                $table->dropRememberToken();

            });

            assertSame(['id'], $builder->getColumnsByOrdinalPosition('table1'));

        }

        /** @test */
        public
        function test_drop_soft_deletes_works()
        {

            $builder = $this->newTestBuilder();

            $builder->create('table1', function (Blueprint $table) {

                $table->id();
                $table->softDeletes();

            });

            assertSame(['id', 'deleted_at'], $builder->getColumnsByOrdinalPosition('table1'));

            $builder->modify('table1', function (Blueprint $table) {

                $table->dropSoftDeletes();

            });

            assertSame(['id'], $builder->getColumnListing('table1'));

        }

        /** @test */
        public
        function test_drop_soft_deletes_tz_works()
        {

            $builder = $this->newTestBuilder();

            $builder->create('table1', function (Blueprint $table) {

                $table->id();
                $table->softDeletesTz();

            });

            assertSame(['id', 'deleted_at'], $builder->getColumnsByOrdinalPosition('table1'));

            $builder->modify('table1', function (Blueprint $table) {

                $table->dropSoftDeletesTz();

            });

            assertSame(['id'], $builder->getColumnListing('table1'));

        }

        /** @test */
        public
        function test_drop_timestamps_works()
        {

            $builder = $this->newTestBuilder();

            $builder->create('table1', function (Blueprint $table) {

                $table->id();
                $table->timestamps();

            });

            assertSame(['id', 'created_at', 'updated_at'],
                $builder->getColumnsByOrdinalPosition('table1'));

            $builder->modify('table1', function (Blueprint $table) {

                $table->dropTimestamps();

            });

            assertSame(['id'], $builder->getColumnListing('table1'));

        }

        /** @test */
        public
        function test_drop_timestamps_tz_works()
        {

            $builder = $this->newTestBuilder();

            $builder->create('table1', function (Blueprint $table) {

                $table->id();
                $table->timestampsTz();

            });

            assertSame(['id', 'created_at', 'updated_at'],
                $builder->getColumnsByOrdinalPosition('table1'));

            $builder->modify('table1', function (Blueprint $table) {

                $table->dropTimestampsTz();

            });

            assertSame(['id'], $builder->getColumnListing('table1'));
        }

        /**
         *
         *
         *
         *
         *
         *
         *
         * Creating indexes
         *
         *
         *
         *
         *
         *
         *
         */

        /** @test */
        public
        function unique_indexes_work()
        {

            $builder = $this->newTestBuilder('table1');

            $builder->create('table1', function (Blueprint $table) {

                $table->id();
                $table->string('email')->unique();
                $table->string('name');


            });

            $builder->seeUniqueColumn('email');

            $builder->modify('table1', function (Blueprint $table) {

                $table->unique('name');

            });

            $builder->seeUniqueColumn('email');
            $builder->seeUniqueColumn('name');


        }

        /** @test */
        public
        function normal_indexes_work()
        {

            $builder = $this->newTestBuilder('table1');

            $builder->create('table1', function (Blueprint $table) {

                $table->id();
                $table->string('email')->index();
                $table->string('name');
                $table->string('address');


            });

            $builder->seeIndexColumn('email');

            $builder->modify('table1', function (Blueprint $table) {

                $table->index('address');

            });

            $builder->seeIndexColumn('address');


        }

        /** @test */
        public
        function a_composite_index_can_be_added()
        {


            $builder = $this->newTestBuilder('table1');

            $builder->create('table1', function (Blueprint $table) {


                $table->id();
                $table->string('name');
                $table->string('email');
                $table->string('address');

            });

            $builder->modify('table1', function (Blueprint $table) {

                $table->index(['name', 'email', 'address']);

            });

            $builder->seeIndexColumn('name');


        }

        /** @test */
        public
        function a_primary_key_index_can_be_created()
        {


            $builder = $this->newTestBuilder('table1');

            $builder->create('table1', function (Blueprint $table) {

                $table->string('email');
                $table->string('name')->primary();

            });

            $builder->seePrimaryKey('name');

        }

        /** @test */
        public
        function an_index_can_be_renamed()
        {


            $builder = $this->newTestBuilder('table1');

            $builder->create('table1', function (Blueprint $table) {

                $table->id();
                $table->string('name')->index();

            });

            $builder->seeIndexColumn('name');

            $builder->modify('table1', function (Blueprint $table) {

                $table->renameIndex('table1_name_index', 'new_index');

            });

            $builder->seeIndexColumn('name');

        }

        /**
         *
         *
         *
         *
         *
         *
         *
         * Dropping Indexes.
         *
         *
         *
         *
         *
         *
         *
         */

        /** @test */
        public
        function indexes_can_be_dropped()
        {

            $builder = $this->newTestBuilder('table1');

            $builder->create('table1', function (Blueprint $table) {

                $table->integer('amount')->primary();
                $table->string('name')->index();
                $table->string('phone')->index();
                $table->string('email')->unique();

            });

            $builder->seeIndexColumn('name');
            $builder->seePrimaryKey('amount');
            $builder->seeUniqueColumn('email');
            $builder->seeIndexColumn('phone');

            $builder->modify('table1', function (Blueprint $table) {

                $table->dropPrimary('table1_amount_primary');
                $table->dropUnique('table1_email_unique');
                $table->dropIndex(['phone']);
                $table->dropIndex(['name']);


            });

            $name = $builder->getFullColumnInfo('table1')['amount'];
            assertEmpty($name['Key']);

            $name = $builder->getFullColumnInfo('table1')['name'];
            assertEmpty($name['Key']);

            $email = $builder->getFullColumnInfo('table1')['email'];
            assertEmpty($email['Key']);

            $phone = $builder->getFullColumnInfo('table1')['phone'];
            assertEmpty($phone['Key']);


        }

        /**
         *
         *
         *
         *
         *
         *
         * Foreign Key Constraints
         *
         *
         *
         *
         *
         *
         */

        /** @test */
        public
        function foreign_key_can_be_created()
        {

            $builder1 = $this->newTestBuilder('authors');

            $builder1->create('authors', function (Blueprint $table) {

                $table->id();

            });

            $builder2 = $this->newTestBuilder('books');

            $builder2->create('books', function (Blueprint $table) {

                $table->id();

                $table->foreignId('author_id')->unique()
                      ->constrained()
                      ->onUpdate('cascade')
                      ->onDelete('cascade');

            });

            $builder1->seePrimaryKey('id');
            $builder2->seePrimaryKey('id');


        }

        /** @test */
        public
        function foreign_keys_cascade_correctly_on_update()
        {

            $builder1 = $this->newTestBuilder('authors');

            $builder1->create('authors', function (Blueprint $table) {

                $table->id();
                $table->string('author_name');

            });

            $builder2 = $this->newTestBuilder('books');

            $builder2->create('books', function (Blueprint $table) {

                $table->id();

                $table->foreignId('author_id')->unique()
                      ->constrained()
                      ->onUpdate('cascade');

            });

            $this->tester->haveInDatabase('wp_authors', ['author_name' => 'calvin alkan']);
            $this->tester->haveInDatabase('wp_books', ['id' => 1, 'author_id' => 1]);

            $this->tester->updateInDatabase('wp_authors', ['id' => '2'],
                ['author_name' => 'calvin alkan']);

            $this->tester->seeInDatabase('wp_books', ['id' => 1, 'author_id' => 2]);

        }

        /** @test */
        public
        function foreign_keys_cascade_correctly_on_delete()
        {

            $builder1 = $this->newTestBuilder('authors');

            $builder1->create('authors', function (Blueprint $table) {

                $table->id();
                $table->string('author_name');

            });

            $builder2 = $this->newTestBuilder('books');

            $builder2->create('books', function (Blueprint $table) {

                $table->id();

                $table->foreignId('author_id')->unique()
                      ->constrained()
                      ->onDelete('cascade');

            });

            $this->tester->haveInDatabase('wp_authors', ['author_name' => 'calvin alkan']);
            $this->tester->haveInDatabase('wp_books', ['id' => 1, 'author_id' => 1]);

            $this->tester->dontHaveInDatabase('wp_authors',
                ['id' => 1, 'author_name' => 'calvin alkan']);

            $this->tester->dontSeeInDatabase('wp_books', ['id' => 1, 'author_id' => 1]);


        }

        /** @test */
        public
        function foreign_keys_can_be_dropped()
        {

            $builder1 = $this->newTestBuilder('authors');

            $builder1->create('authors', function (Blueprint $table) {

                $table->id();
                $table->string('author_name');

            });

            $builder2 = $this->newTestBuilder('books');

            $builder2->create('books', function (Blueprint $table) {

                $table->id();

                $table->foreignId('author_id')->unique()
                      ->constrained()
                      ->onDelete('cascade');

            });

            $this->tester->haveInDatabase('wp_authors', ['author_name' => 'calvin alkan']);
            $this->tester->haveInDatabase('wp_books', ['id' => 1, 'author_id' => 1]);

            $builder2->modify('books', function (Blueprint $table) {


                $table->dropForeign(['author_id']);


            });

            $this->tester->dontHaveInDatabase('wp_authors',
                ['id' => 1, 'author_name' => 'calvin alkan']);

            $this->tester->seeInDatabase('wp_books', ['id' => 1, 'author_id' => 1]);


        }

        /**
         *
         *
         *
         *
         *
         * Helper methods
         *
         *
         *
         *
         *
         *
         */

        private
        function newSchemaBuilder()
        {

            global $wpdb;

            $wp_connection = new WpConnection($wpdb);

            return $wp_connection->getSchemaBuilder();


        }

        private
        function newUserTable(
            Builder $builder = null
        ) {


            $builder = $builder ?? $this->newSchemaBuilder();

            $builder->create('test_users', function (Blueprint $table) {

                $table->id();
                $table->string('name');
                $table->string('email');
                $table->timestamps();
            });


        }

        private
        function newTestBuilder(
            $table = null
        ) {

            global $wpdb;

            $wp_connection = new WpConnection($wpdb);

            return new TestSchemaBuilder($wp_connection, $table);


        }

    }


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


    class SchemaBuilderWpDb implements WpdbInterface
    {


        public $prefix = 'wp_';

        private $queries = [];

        public function getLog() {

            return $this->queries;

        }

        public function doSelect(string $query, array $bindings) : array
        {

            //

        }

        public function doStatement(string $query, array $bindings) : bool
        {

            $this->queries[] = Str::replaceArray('?', $bindings, $query);

            return true;

        }

        public function doAffectingStatement($query, array $bindings) : int
        {
            //
        }

        public function doUnprepared(string $query) : bool
        {
            //
        }

        public function doSelectOne($query, $bindings)
        {
            //
        }

        public function doCursorSelect($query, $bindings)
        {
            //
        }

        public function startTransaction()
        {
            //
        }

        public function commitTransaction()
        {
            //
        }

        public function rollbackTransaction($name = null)
        {
            //
        }

        public function createSavepoint(string $name)
        {
            //
        }


    }


    class TestWpConnection extends WpConnection {


        public function __construct(WpdbInterface $wpdb)
        {
            parent::__construct($wpdb);
        }

        public function getLog() {

            return $this->wpdb->getLog();

        }

    }




