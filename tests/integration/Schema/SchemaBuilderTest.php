<?php


    namespace Tests\integration\Schema;

    use Codeception\TestCase\WPTestCase;
    use Illuminate\Database\Schema\Blueprint;
    use Illuminate\Database\Schema\Builder;
    use WpEloquent\WpConnection;

    use function PHPUnit\Framework\assertFalse;
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
        public function table_column_existence_can_be_checked()
        {

            $schema_builder = $this->newSchemaBuilder();

            self::assertTrue($schema_builder->hasColumn('users', 'user_login'));
            self::assertFalse($schema_builder->hasColumn('users', 'user_profile_pic'));

            self::assertFalse($schema_builder->hasColumns('users', ['user_login', 'user_profile_pic'] ));
            self::assertTrue($schema_builder->hasColumns('users', ['user_login', 'user_email'] ));

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
        public function a_table_can_be_dropped ()
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
        public function columns_can_be_dropped ()
        {

            $this->newUserTable($builder = $this->newSchemaBuilder());

            $builder->dropColumns('test_users', ['id', 'name', 'email'] );

            assertFalse($builder->hasColumn('test_users', 'name'));
            assertFalse($builder->hasColumn('test_users', 'email'));
            assertFalse($builder->hasColumn('test_users', 'id'));


        }


        /** @test */
        public function all_tables_can_be_retrieved () {


            $this->newUserTable($builder = $this->newSchemaBuilder());

            $tables = $builder->getAllTables();

            global $table_prefix;

            foreach ($tables as $table) {

                if ( ! $test = $this->tester->seeTableInDatabase($table)) {

                    $this->fail('Table: ' . $table . ' could not be found in the Database');

                }

            }

            assertTrue(TRUE );

        }


        private function newSchemaBuilder()
        {

            global $wpdb;

            $wp_connection = new WpConnection($wpdb);

            return $wp_connection->getSchemaBuilder();


        }


        private function newUserTable(Builder $builder = null ) {


            $builder = $builder ?? $this->newSchemaBuilder();

            $builder->create('test_users', function (Blueprint $table) {

                $table->id();
                $table->string('name');
                $table->string('email');
                $table->timestamps();
            });


        }

    }