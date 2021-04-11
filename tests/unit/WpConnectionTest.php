<?php


    namespace Tests\unit;

    use Illuminate\Database\Query\Builder;
    use Mockery as m;
    use PHPUnit\Framework\TestCase;
    use WpEloquent\MySqlSchemaBuilder;
    use WpEloquent\SanitizerFactory;
    use WpEloquent\WpConnection;
    use Illuminate\Database\Query\Grammars\MySqlGrammar as MySqlQueryGrammar;
    use Illuminate\Database\Query\Processors\MySqlProcessor;
    use Illuminate\Database\Schema\Grammars\MySqlGrammar as MySqlSchemaGrammar;

    class WpConnectionTest extends TestCase
    {


        /**
         * @var \wpdb
         */
        private $wpdb;


        protected function setUp() : void
        {

            parent::setUp();

            if ( ! defined('DB_NAME')) {
                define('DB_NAME', 'wp-eloquent');
            }

            if ( ! defined('ARRAY_A')) {
                define('ARRAY_A', 'wp-eloquent');
            }

            $this->wpdb = m::mock(\wpdb::class);
            $this->wpdb->prefix = 'wp_';


        }


        protected function tearDown() : void
        {

            parent::tearDown();

            m::close();

        }


        /** @test */
        public function constructing_the_wp_connection_correctly_sets_up_all_collaborators()
        {

            $wp_connection1 = $this->newWpConnection();

            $query_grammar = $wp_connection1->getQueryGrammar();
            self::assertSame('wp_', $query_grammar->getTablePrefix());
            self::assertInstanceOf(MySqlQueryGrammar::class, $query_grammar);

            $schema_grammar = $wp_connection1->getSchemaGrammar();
            self::assertSame('wp_', $schema_grammar->getTablePrefix());
            self::assertInstanceOf(MySqlSchemaGrammar::class, $schema_grammar);

            $processor = $wp_connection1->getPostProcessor();
            self::assertInstanceOf(MySqlProcessor::class, $processor);


        }


        /** @test */
        public function the_query_builder_uses_the_correct_grammar_and_processor()
        {

            $wp_connection = $this->newWpConnection();

            $query_builder = $wp_connection->query();

            self::assertInstanceOf(Builder::class, $query_builder);

            self::assertSame($wp_connection->getPostProcessor(), $query_builder->processor);
            self::assertSame($wp_connection->getQueryGrammar(), $query_builder->grammar);


        }


        /** @test */
        public function the_schema_builder_uses_the_correct_grammar_and_processor()
        {

            $wp_connection = $this->newWpConnection();

            $schema_builder = $wp_connection->getSchemaBuilder();

            self::assertInstanceOf(MySqlSchemaBuilder::class, $schema_builder);


        }


        /** @test */
        public function the_connection_can_begin_a_query_against_a_query_builder_table()
        {


            $wp_connection = $this->newWpConnection();

            $query_builder = $wp_connection->table('wp_users', 'users');

            self::assertInstanceOf(Builder::class, $query_builder);

            self::assertSame('wp_users as users', $query_builder->from);


        }


        /** @test */
        public function bindings_get_prepared_correctly()
        {

            $result = $this->newWpConnection()->prepareBindings([

                true,
                false,
                'string',
                10,
                new \DateTime('07.04.2021 15:00'),

            ]);

            self::assertSame([

                1,
                0,
                'string',
                10,
                '2021-04-07 15:00:00',

            ], $result);

        }

        /** @test */
        public function selecting_one_result_works_with_a_valid_query()
        {


            $safe_sql = "select * from `wp_users` where `user_name` = 'calvin' and `id` = 1 limit 1";

            $this->wpdb->shouldReceive('prepare')
                       ->once()
                       ->with("select * from `wp_users` where `user_name` = %s and `id` = %d limit 1",
                           'calvin', 1)
                       ->andReturn($safe_sql);

            $this->wpdb->shouldReceive('get_row')
                       ->once()
                       ->with($safe_sql, ARRAY_A)
                       ->andReturn(['id' => '1', 'user_name' => 'calvin']);

            $wp = $this->newWpConnection();

            $query = "select * from `wp_users` where `user_name` = ? and `id` = ? limit 1";

            $result = $wp->selectOne($query, ['calvin', 1]);

            self::assertSame($result, ['id' => '1', 'user_name' => 'calvin']);


        }

        /** @test */
        public function selecting_a_set_of_records_works_with_a_valid_query()
        {


            $safe_sql = "select customer_id, first_name, last_name from `wp_customer` where `first_name` = 'MARY' or `last_name` = 'JONES'";

            $this->wpdb->shouldReceive('prepare')
                       ->once()
                       ->with("select customer_id, first_name, last_name from `wp_customer` where `first_name` = %s or `last_name` = %s",
                           'MARY', 'JONES')
                       ->andReturn($safe_sql);

            $this->wpdb->shouldReceive('get_results')
                       ->once()
                       ->with($safe_sql, ARRAY_A)
                       ->andReturn([

                           ['id' => 1, 'first_name' => 'MARY', 'last_name' => 'SMITH'],
                           ['id' => 4, 'first_name' => 'BARBARA', 'last_name' => 'JONES'],

                       ]);

            $wp = $this->newWpConnection();

            $query = "select customer_id, first_name, last_name from `wp_customer` where `first_name` = ? or `last_name` = ?";

            $result = $wp->select($query, ['MARY', 'JONES']);

            self::assertSame($result, [

                ['id' => 1, 'first_name' => 'MARY', 'last_name' => 'SMITH'],
                ['id' => 4, 'first_name' => 'BARBARA', 'last_name' => 'JONES'],

            ]);

        }

        /** @test */
        public function select_from_write_connection_is_just_an_alias_for_select()
        {


            $safe_sql = "select customer_id, first_name, last_name from `wp_customer` where `first_name` = 'MARY' or `last_name` = 'JONES'";

            $this->wpdb->shouldReceive('prepare')
                       ->once()
                       ->with("select customer_id, first_name, last_name from `wp_customer` where `first_name` = %s or `last_name` = %s",
                           'MARY', 'JONES')
                       ->andReturn($safe_sql);

            $this->wpdb->shouldReceive('get_results')
                       ->once()
                       ->with($safe_sql, ARRAY_A)
                       ->andReturn([

                           ['id' => 1, 'first_name' => 'MARY', 'last_name' => 'SMITH'],
                           ['id' => 4, 'first_name' => 'BARBARA', 'last_name' => 'JONES'],

                       ]);

            $wp = $this->newWpConnection();

            $query = "select customer_id, first_name, last_name from `wp_customer` where `first_name` = ? or `last_name` = ?";

            $result = $wp->selectFromWriteConnection($query, ['MARY', 'JONES']);

            self::assertSame($result, [

                ['id' => 1, 'first_name' => 'MARY', 'last_name' => 'SMITH'],
                ['id' => 4, 'first_name' => 'BARBARA', 'last_name' => 'JONES'],

            ]);

        }

        /** @test */
        public function an_empty_array_is_returned_if_and_error_occurred_during_a_select()
        {

            $safe_sql = "select customer_id, first_name, last_name from `wp_customer` where `first_name` = 'MARY' or `last_name` = 'JONES'";

            $this->wpdb->shouldReceive('prepare')
                       ->once()
                       ->with("select customer_id, first_name, last_name from `wp_customer` where `first_name` = %s or `last_name` = %s",
                           'MARY', 'JONES')
                       ->andReturn($safe_sql);

            $this->wpdb->shouldReceive('get_results')
                       ->once()
                       ->with($safe_sql, ARRAY_A)
                       ->andReturn(['foobar']);

            $this->wpdb->last_error = 'SQL ERROR';

            $wp = $this->newWpConnection();

            $query = "select customer_id, first_name, last_name from `wp_customer` where `first_name` = ? or `last_name` = ?";

            $result = $wp->select($query, ['MARY', 'JONES']);

            self::assertSame($result, []);

        }

        /** @test */
        public function an_empty_array_is_returned_if_null_is_returned_from_wpdb()
        {

            $safe_sql = "select customer_id, first_name, last_name from `wp_customer` where `first_name` = 'MARY' or `last_name` = 'JONES'";

            $this->wpdb->shouldReceive('prepare')
                       ->once()
                       ->with("select customer_id, first_name, last_name from `wp_customer` where `first_name` = %s or `last_name` = %s",
                           'MARY', 'JONES')
                       ->andReturn($safe_sql);

            $this->wpdb->shouldReceive('get_results')
                       ->once()
                       ->with($safe_sql, ARRAY_A)
                       ->andReturn(null);

            $wp = $this->newWpConnection();

            $query = "select customer_id, first_name, last_name from `wp_customer` where `first_name` = ? or `last_name` = ?";

            $result = $wp->select($query, ['MARY', 'JONES']);

            self::assertSame($result, []);

        }

        /** @test */
        public function successful_inserts_return_true()
        {


            $safe_sql = "insert into `wp_customer` (`customer_id`, `first_name`, `store_id`) values ( 1, 'calvin', 1 ), (2, 'marlon' , 2)";

            $this->wpdb->shouldReceive('prepare')
                       ->once()
                       ->with("insert into `wp_customer` (`customer_id`, `first_name`, `store_id`) values (%d, %s, %d), (%d, %s, %d)",
                           1, 'calvin', 1, 2, 'marlon', 2)
                       ->andReturn($safe_sql);

            $this->wpdb->shouldReceive('query')->once()->with($safe_sql)->andReturn(2);

            $wp = $this->newWpConnection();

            $success = $wp->table('customer')->insert([

                ['customer_id' => 1, 'store_id' => 1, 'first_name' => 'calvin'],
                ['customer_id' => 2, 'store_id' => 2, 'first_name' => 'marlon'],

            ]);

            self::assertTrue($success);

        }

        /** @test */
        public function insert_without_affected_rows_return_false()
        {


            $this->wpdb->shouldReceive('prepare')
                       ->once()
                       ->with("insert into `wp_customer` (`customer_id`) values (%d)", 1000)
                       ->andReturn('sql');

            $this->wpdb->shouldReceive('query')->once()->with('sql')->andReturn(0);

            $wp = $this->newWpConnection();

            $success = $wp->table('customer')->insert([

                'customer_id' => 1000,

            ]);

            self::assertFalse($success);


        }

        /** @test */
        public function insert_with_errors_return_false()
        {


            $this->wpdb->shouldReceive('prepare')
                       ->once()
                       ->with("insert into `wp_customer` (`customer_id`) values (%d)", 1000)
                       ->andReturn('sql');

            $this->wpdb->shouldReceive('query')->once()->with('sql')->andReturn(false);

            $wp = $this->newWpConnection();

            $success = $wp->table('customer')->insert([

                'customer_id' => 1000,

            ]);

            self::assertFalse($success);


        }

        /** @test */
        public function successful_updates_return_the_number_of_affected_rows()
        {

            $safe_sql = "update `wp_customer` set `first_name` = 'calvin' where `customer_id` = 1";

            $this->wpdb->shouldReceive('prepare')
                       ->once()
                       ->with("update `wp_customer` set `first_name` = %s where `customer_id` = %d", 'calvin', 1)
                       ->andReturn($safe_sql);

            $this->wpdb->shouldReceive('query')->once()->with($safe_sql)->andReturn(1);

            $wp = $this->newWpConnection();

            $affected_rows = $wp->table('customer')
                          ->where('customer_id', 1)
                          ->update(['first_name' => 'calvin']);

            self::assertEquals($affected_rows, 1);

        }

        /** @test */
        public function updates_with_errors_return_zero()
        {

            $safe_sql = "update `wp_customer` set `first_name` = 'calvin' where `customer_id` = 1";

            $this->wpdb->shouldReceive('prepare')
                       ->once()
                       ->with("update `wp_customer` set `first_name` = %s where `customer_id` = %d", 'calvin', 1)
                       ->andReturn($safe_sql);


            $this->wpdb->shouldReceive('query')->once()->with($safe_sql)->andReturn(FALSE);

            $wp = $this->newWpConnection();

            $affected_rows = $wp->table('customer')
                                ->where('customer_id', 1)
                                ->update(['first_name' => 'calvin']);

            self::assertEquals($affected_rows, 0);

        }

        /** @test */
        public function deletes_return_the_amount_of_deleted_rows()
        {

            $safe_sql = "delete from `wp_customer` where `customer_id` < 3";

            $this->wpdb->shouldReceive('prepare')
                       ->once()
                       ->with("delete from `wp_customer` where `customer_id` < %d", 3)
                       ->andReturn($safe_sql);


            $this->wpdb->shouldReceive('query')->once()->with($safe_sql)->andReturn(2);

            $wp = $this->newWpConnection();

            $deleted_rows = $wp->table('customer')
                                ->where('customer_id', '<', 3)
                                ->delete();

            self::assertEquals($deleted_rows, 2);

        }

        /** @test */
        public function zero_gets_returned_if_no_row_got_deleted()
        {

            $safe_sql = "delete from `wp_customer` where `customer_id` > 3000";

            $this->wpdb->shouldReceive('prepare')
                       ->once()
                       ->with("delete from `wp_customer` where `customer_id` > %d", 3000)
                       ->andReturn($safe_sql);


            $this->wpdb->shouldReceive('query')->once()->with($safe_sql)->andReturn(0);

            $wp = $this->newWpConnection();

            $deleted_rows = $wp->table('customer')
                               ->where('customer_id', '>', 3000)
                               ->delete();

            self::assertEquals($deleted_rows, 0);

        }



        private function newWpConnection() : WpConnection
        {

            return new WpConnection($this->wpdb, new SanitizerFactory($this->wpdb));

        }

        private function newBuilder() : Builder
        {

            return new Builder($this->newWpConnection());

        }


    }
