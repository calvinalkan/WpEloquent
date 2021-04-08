<?php


    namespace Tests\integration\Schema;

    use Codeception\TestCase\WPTestCase;
    use Illuminate\Database\Query\Expression;
    use Illuminate\Database\Schema\Blueprint;
    use Illuminate\Database\Schema\Builder;
    use Illuminate\Support\Carbon;
    use Illuminate\Support\Str;
    use PHPUnit\Framework\TestBuilder;
    use WpEloquent\MySqlSchemaBuilder;
    use WpEloquent\WpConnection;

    use function PHPUnit\Framework\assertFalse;
    use function PHPUnit\Framework\assertSame;
    use function PHPUnit\Framework\assertThat;
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
       public function the_column_type_can_be_found_for_the_last_query ()
       {

           $builder = $this->newTestBuilder();

           $builder->create('table1', function (Blueprint $table) {

               $table->id();
               $table->string('email');

           });

           assertSame('varchar(255)',$builder->getColumnType('table1', 'email'));

       }


        /**
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
         */
        /** @test */
        public function big_increments_works ()
        {


            $builder = $this->newTestBuilder('table1');

            $builder->create('table1', function (Blueprint $table) {

               $table->bigIncrements('id');

            });

            $builder->seeColumnOfType( 'id', 'bigint unsigned');


        }


        // /** @test */
        // public function all_column_types_can_be_created()
        // {
        //
        //     $builder = $this->newTestBuilder();
        //
        //     $builder->create('table1', function (Blueprint $table) {
        //
        //         $table->bigIncrements('id_big');
        //         $table->bigInteger('votes');
        //         $table->binary('photo');
        //         $table->boolean('confirmed');
        //         $table->char('name', 100);
        //         $table->dateTimeTz('created_at_tz', $precision = 0);
        //         $table->dateTime('created_at', $precision = 0);
        //         $table->date('date');
        //         $table->decimal('amount_decimal', $precision = 8, $scale = 2);
        //         $table->double('amount_double', 8, 2);
        //         $table->enum('difficulty', ['easy', 'hard']);
        //         $table->float('amount_float', 8, 2);
        //         $table->foreignId('user_id');
        //         $table->geometryCollection('geometrycollection');
        //         $table->geometry('geometry');
        //
        //     });
        //
        //     $builder->seeColumnOfType('table1', 'id_big', 'bigint');
        //     $builder->seeColumnOfType('table1', 'votes', 'bigint');
        //     $builder->seeColumnOfType('table1', 'photo', 'blob');
        //     $builder->seeColumnOfType('table1', 'confirmed', 'tinyint');
        //     $builder->seeColumnOfType('table1', 'name', 'char');
        //     $builder->seeColumnOfType('table1', 'created_at_tz', 'datetime');
        //     $builder->seeColumnOfType('table1', 'created_at', 'datetime');
        //     $builder->seeColumnOfType('table1', 'date', 'date');
        //     $builder->seeColumnOfType('table1', 'amount_decimal', 'decimal');
        //     $builder->seeColumnOfType('table1', 'amount_double', 'double');
        //
        //     // double is expected here. @see: https://github.com/laravel/framework/issues/18776
        //     $builder->seeColumnOfType('table1', 'amount_float', 'double');
        //     $builder->seeColumnOfType('table1', 'difficulty', 'enum');
        //     $builder->seeColumnOfType('table1', 'user_id', 'bigint');
        //     $builder->seeColumnOfType('table1', 'geometrycollection', 'geomcollection');
        //     $builder->seeColumnOfType('table1', 'geometry', 'geometry');
        //
        //     $builder->create('table2', function (Blueprint $table) {
        //
        //         $table->id('primary');
        //         $table->integer('votes');
        //         $table->ipAddress('visitor');
        //         $table->json('json');
        //         $table->jsonb('jsonb');
        //         $table->lineString('positions');
        //         $table->longText('description');
        //         $table->macAddress('device');
        //     });
        //
        //     $builder->seeColumnOfType('table2', 'primary', 'bigint');
        //     $builder->seeColumnOfType('table2', 'votes', 'int');
        //     $builder->seeColumnOfType('table2', 'visitor', 'varchar');
        //     $builder->seeColumnOfType('table2', 'json', 'json');
        //
        //     //expected type is json for jsonb,
        //     $builder->seeColumnOfType('table2', 'jsonb', 'json');
        //     $builder->seeColumnOfType('table2', 'positions', 'linestring');
        //     $builder->seeColumnOfType('table2', 'description', 'longtext');
        //     $builder->seeColumnOfType('table2', 'device', 'varchar');
        //
        //     $builder->create('table3', function (Blueprint $table) {
        //
        //         $table->increments('id');
        //         $table->nullableMorphs('taggable');
        //         $table->nullableUuidMorphs('likeable');
        //         $table->point('position');
        //         $table->polygon('polygon');
        //         $table->rememberToken();
        //         $table->set('flavors', ['strawberry', 'vanilla']);
        //     });
        //
        //     $builder->seeColumnOfType('table3', 'id', 'int');
        //     $builder->seeColumnOfType('table3', 'taggable_id', 'bigint');
        //     $builder->seeColumnOfType('table3', 'taggable_type', 'varchar');
        //     $builder->seeColumnOfType('table3', 'likeable_id', 'char');
        //     $builder->seeColumnOfType('table3', 'likeable_type', 'varchar');
        //     $builder->seeColumnOfType('table3', 'position', 'point');
        //     $builder->seeColumnOfType('table3', 'polygon', 'polygon');
        //     $builder->seeColumnOfType('table3', 'remember_token', 'varchar');
        //     $builder->seeColumnOfType('table3', 'flavors', 'set');
        //
        //     $builder->create('table4', function (Blueprint $table) {
        //
        //         $table->mediumIncrements('id');
        //         $table->mediumInteger('votes');
        //         $table->mediumText('description');
        //         $table->morphs('taggable');
        //         $table->multiLineString('multiLineString');
        //         $table->multiPoint('multiPoint');
        //         $table->multiPolygon('multiPolygon');
        //         $table->nullableTimestamps(0);
        //     });
        //
        //     $builder->seeColumnOfType('table4', 'id', 'mediumint');
        //     $builder->seeColumnOfType('table4', 'votes', 'mediumint');
        //     $builder->seeColumnOfType('table4', 'description', 'mediumtext');
        //     $builder->seeColumnOfType('table4', 'taggable_id', 'bigint');
        //     $builder->seeColumnOfType('table4', 'taggable_type', 'varchar');
        //     $builder->seeColumnOfType('table4', 'multiLineString', 'multilinestring');
        //     $builder->seeColumnOfType('table4', 'multiPoint', 'multipoint');
        //     $builder->seeColumnOfType('table4', 'multiPolygon', 'multipolygon');
        //     $builder->seeColumnOfType('table4', 'created_at', 'timestamp');
        //     $builder->seeColumnOfType('table4', 'updated_at', 'timestamp');
        //
        //     $builder->create('table5', function (Blueprint $table) {
        //
        //
        //         $table->smallIncrements('id');
        //         $table->smallInteger('votes');
        //         $table->softDeletesTz($column = 'deleted_at_tz', $precision = 0);
        //         $table->softDeletes($column = 'deleted_at', $precision = 0);
        //         $table->string('name', 100);
        //         $table->text('description');
        //         $table->timeTz('sunrise_tz', $precision = 0);
        //         $table->time('sunrise', $precision = 0);
        //         $table->timestampTz('added_at_tz', $precision = 0);
        //         $table->timestamp('added_at', $precision = 0);
        //         $table->timestampsTz($precision = 0);
        //     });
        //
        //     $builder->seeColumnOfType('table5', 'id', 'smallint');
        //     $builder->seeColumnOfType('table5', 'votes', 'smallint');
        //     $builder->seeColumnOfType('table5', 'deleted_at_tz', 'timestamp');
        //     $builder->seeColumnOfType('table5', 'deleted_at', 'timestamp');
        //     $builder->seeColumnOfType('table5', 'description', 'text');
        //     $builder->seeColumnOfType('table5', 'sunrise', 'time');
        //     $builder->seeColumnOfType('table5', 'sunrise_tz', 'time');
        //     $builder->seeColumnOfType('table5', 'added_at_tz', 'timestamp');
        //     $builder->seeColumnOfType('table5', 'added_at', 'timestamp');
        //     $builder->seeColumnOfType('table5', 'created_at', 'timestamp');
        //     $builder->seeColumnOfType('table5', 'updated_at', 'timestamp');
        //
        //     $builder->create('table6', function (Blueprint $table) {
        //
        //         $table->tinyIncrements('id');
        //         $table->tinyInteger('votes');
        //         $table->unsignedBigInteger('signature');
        //         $table->timestamps($precision = 0);
        //         $table->unsignedDecimal('amount', $precision = 8, $scale = 2);
        //
        //
        //     });
        //
        //     $builder->seeColumnOfType('table6', 'id', 'tinyint');
        //     $builder->seeColumnOfType('table6', 'votes', 'tinyint');
        //     $builder->seeColumnOfType('table6', 'signature', 'bigint');
        //     $builder->seeColumnOfType('table6', 'created_at', 'timestamp');
        //     $builder->seeColumnOfType('table6', 'updated_at', 'timestamp');
        //     $builder->seeColumnOfType('table6', 'amount', 'decimal');
        //
        //     $builder->create('table7', function (Blueprint $table) {
        //
        //         $table->unsignedTinyInteger('vote_tiny');
        //         $table->unsignedInteger('votes');
        //         $table->unsignedMediumInteger('votes_medium');
        //         $table->unsignedSmallInteger('votes_small');
        //         $table->uuidMorphs('taggable');
        //         $table->uuid('uuid');
        //         $table->year('birth_year');
        //     });
        //
        //     $builder->seeColumnOfType('table7', 'vote_tiny', 'tinyint');
        //     $builder->seeColumnOfType('table7', 'votes', 'int');
        //     $builder->seeColumnOfType('table7', 'votes_medium', 'mediumint');
        //     $builder->seeColumnOfType('table7', 'votes_small', 'smallint');
        //     $builder->seeColumnOfType('table7', 'taggable_id', 'char');
        //     $builder->seeColumnOfType('table7', 'taggable_type', 'varchar');
        //     $builder->seeColumnOfType('table7', 'uuid', 'char');
        //     $builder->seeColumnOfType('table7', 'birth_year', 'year');
        //
        //
        // }


        /**
         * TEST FOR MODIFYING COLUMNS
         */

        /** @test */
        public function new_columns_can_be_inserted_after_existing_columns()
        {


            $builder = $this->newTestBuilder();

            $builder->create('table1', function (Blueprint $table) {

                $table->string('first_name');
                $table->string('email');


            });

            // Without after method()
            $builder->table('table1', function (Blueprint $table) {

                $table->string('last_name');
                $table->string('phone');

            });

            assertSame(['first_name', 'email', 'last_name', 'phone'],
                $builder->getColumnsByOrdinalPosition('table1'));

            $builder->dropColumns('table1', ['last_name', 'phone']);

            // With after() method
            $builder->table('table1', function (Blueprint $table) {

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

            $builder->table('table1', function (Blueprint $table) {

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
        public function auto_incrementing_works()
        {

            $builder = $this->newTestBuilder('table1');

            $builder->create('table1', function (Blueprint $table) {


                $table->integer('user_id')->autoIncrement();
                $table->string('email');


            });

            $builder->seeColumnOfType( 'user_id', 'int');

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
        public function charset_can_be_set_for_table_and_column()
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
        public function collation_can_be_set_for_table_an_column()
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
        public function comments_can_be_added()
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
        public function a_default_value_can_be_set()
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
        public function a_column_can_be_added_at_the_first_place()
        {

            $builder = $this->newTestBuilder();

            $builder->create('table1', function (Blueprint $table) {

                $table->id();
                $table->integer('count');
                $table->string('name');

            });

            assertSame(['id', 'count', 'name'], $builder->getColumnsByOrdinalPosition('table1'));

            $builder->table('table1', function (Blueprint $table) {


                $table->string('email')->first();


            });

            assertSame(['email', 'id', 'count', 'name'],
                $builder->getColumnsByOrdinalPosition('table1'));

        }

        /** @test */
        public function a_column_can_be_nullable()
        {

            $builder = $this->newTestBuilder('table1');

            $builder->create('table1', function (Blueprint $table) {

                $table->id();
                $table->string('email')->nullable(false);

            });

            $builder->seeColumnOfType( 'id', 'bigint unsigned');
            $builder->seeColumnOfType( 'email', 'varchar(255)');

            try {

                $this->tester->haveInDatabase('wp_table1', ['id' => 1]);
                $this->fail('Non-nullable column was created without default value');

            }
            catch (\PDOException $e) {

                assertSame("SQLSTATE[HY000]: General error: 1364 Field 'email' doesn't have a default value",
                    $e->getMessage());

            }

            $builder->dropColumns('table1', 'email');

            $builder->table('table1', function (Blueprint $table) {

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
        public function a_stored_column_can_be_created()
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
        public function a_virtual_column_can_be_created()
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
        public function integers_can_be_unsigned()
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
        public function timestamps_can_use_the_current_time_as_default()
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
         * TESTS FOR DROPPING COLUMNS WITH ALIASES
         */

        /** @test */
        public function test_drop_morphs_works()
        {

            $builder = $this->newTestBuilder();

            $builder->create('table1', function (Blueprint $table) {

                $table->id();
                $table->morphs('taggable');


            });

            assertSame(['id', 'taggable_type', 'taggable_id'],
                $builder->getColumnsByOrdinalPosition('table1'));

            $builder->table('table1', function (Blueprint $table) {

                $table->dropMorphs('taggable');

            });

            assertSame(['id'], $builder->getColumnsByOrdinalPosition('table1'));

        }

        /** @test */
        public function test_remember_token_works()
        {

            $builder = $this->newTestBuilder();

            $builder->create('table1', function (Blueprint $table) {

                $table->id();
                $table->rememberToken();


            });

            assertSame(['id', 'remember_token'], $builder->getColumnsByOrdinalPosition('table1'));

            $builder->table('table1', function (Blueprint $table) {

                $table->dropRememberToken();

            });

            assertSame(['id'], $builder->getColumnsByOrdinalPosition('table1'));

        }

        /** @test */
        public function test_drop_soft_deletes_works()
        {

            $builder = $this->newTestBuilder();

            $builder->create('table1', function (Blueprint $table) {

                $table->id();
                $table->softDeletes();

            });

            assertSame(['id', 'deleted_at'], $builder->getColumnsByOrdinalPosition('table1'));

            $builder->table('table1', function (Blueprint $table) {

                $table->dropSoftDeletes();

            });

            assertSame(['id'], $builder->getColumnListing('table1'));

        }

        /** @test */
        public function test_drop_soft_deletes_tz_works()
        {

            $builder = $this->newTestBuilder();

            $builder->create('table1', function (Blueprint $table) {

                $table->id();
                $table->softDeletesTz();

            });

            assertSame(['id', 'deleted_at'], $builder->getColumnsByOrdinalPosition('table1'));

            $builder->table('table1', function (Blueprint $table) {

                $table->dropSoftDeletesTz();

            });

            assertSame(['id'], $builder->getColumnListing('table1'));

        }

        /** @test */
        public function test_drop_timestamps_works()
        {

            $builder = $this->newTestBuilder();

            $builder->create('table1', function (Blueprint $table) {

                $table->id();
                $table->timestamps();

            });

            assertSame(['id', 'created_at', 'updated_at'], $builder->getColumnsByOrdinalPosition('table1'));

            $builder->table('table1', function (Blueprint $table) {

                $table->dropTimestamps();

            });

            assertSame(['id'], $builder->getColumnListing('table1'));

        }

        /** @test */
        public function test_drop_timestamps_tz_works()
        {

            $builder = $this->newTestBuilder();

            $builder->create('table1', function (Blueprint $table) {

                $table->id();
                $table->timestampsTz();

            });

            assertSame(['id', 'created_at', 'updated_at'], $builder->getColumnsByOrdinalPosition('table1'));

            $builder->table('table1', function (Blueprint $table) {

                $table->dropTimestampsTz();

            });

            assertSame(['id'], $builder->getColumnListing('table1'));
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

        private function newTestBuilder($table = null )
        {

            global $wpdb;

            $wp_connection = new WpConnection($wpdb);

            return new TestSchemaBuilder($wp_connection, $table );


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


        public function __construct( $connection, $table = null )
        {
            $this->table = $table;

            parent::__construct($connection);
        }

        public function seeColumnOfType( $column, $type)
        {

            $table = $this->table;

            assertTrue($this->hasColumn($table, $column),
                'Column: '.$column.' not found.');
            assertSame($type, $this->getColumnType($table, $column),
                'Column types dont match for colum: '.$column);

        }



    }