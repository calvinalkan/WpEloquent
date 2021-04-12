<?php


    namespace Tests\unit;

    use Illuminate\Database\Query\Builder;
    use Mockery as m;
    use PHPUnit\Framework\TestCase;
    use Tests\stubs\FakeWpdb;
    use WpEloquent\ExtendsWpdb\BetterWpDb;
    use WpEloquent\MySqlSchemaBuilder;
    use WpEloquent\SanitizerFactory;
    use WpEloquent\WpConnection;
    use Illuminate\Database\Query\Grammars\MySqlGrammar as MySqlQueryGrammar;
    use Illuminate\Database\Query\Processors\MySqlProcessor;
    use Illuminate\Database\Schema\Grammars\MySqlGrammar as MySqlSchemaGrammar;

    class WpConnectionTest extends TestCase
    {


        /**
         * @var BetterWpDb
         */
        private $wpdb;


        protected function setUp() : void
        {

            parent::setUp();

            if ( ! defined('DB_NAME')) {
                define('DB_NAME', 'wp-eloquent');
            }

            $this->wpdb = m::mock(FakeWpdb::class);
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


            $this->wpdb->shouldReceive('doSelectOne')
                       ->once()
                       ->with(
                           "select * from `wp_users` where `user_name` = ? and `id` = ? limit 1",
                           ['calvin', 1])
                       ->andReturn(['user_id' => 1, 'user_name' => 'calvin']);

            $wp = $this->newWpConnection();

            $query = "select * from `wp_users` where `user_name` = ? and `id` = ? limit 1";

            $result = $wp->selectOne($query, ['calvin', 1]);

            self::assertSame($result, ['user_id' => 1, 'user_name' => 'calvin']);


        }

        /** @test */
        public function selecting_a_set_of_records_works_with_a_valid_query()
        {

            $sql = "select `customer_id`, `first_name`, `last_name` from `wp_customer` where `first_name` = ? and `last_name` = ?";

            $this->wpdb->shouldReceive('doSelect')
                       ->once()
                       ->with($sql, ['MARY', 'JONES'])
                       ->andReturn([

                           ['id' => 1, 'first_name' => 'MARY', 'last_name' => 'SMITH'],
                           ['id' => 4, 'first_name' => 'BARBARA', 'last_name' => 'JONES'],

                       ]);

            $wp = $this->newWpConnection();

            $builder = $wp->table('customer')->select(['customer_id', 'first_name', 'last_name'])
                          ->where('first_name', 'MARY')
                          ->where('last_name', 'JONES');

            $result = $wp->select($builder->toSql(), $builder->getBindings());

            self::assertSame($result, [

                ['id' => 1, 'first_name' => 'MARY', 'last_name' => 'SMITH'],
                ['id' => 4, 'first_name' => 'BARBARA', 'last_name' => 'JONES'],

            ]);

        }

        /** @test */
        public function select_from_write_connection_is_just_an_alias_for_select()
        {


            $sql = "select `customer_id`, `first_name`, `last_name` from `wp_customer` where `first_name` = ? and `last_name` = ?";

            $this->wpdb->shouldReceive('doSelect')
                       ->once()
                       ->with($sql, ['MARY', 'JONES'])
                       ->andReturn([

                           ['id' => 1, 'first_name' => 'MARY', 'last_name' => 'SMITH'],
                           ['id' => 4, 'first_name' => 'BARBARA', 'last_name' => 'JONES'],

                       ]);

            $wp = $this->newWpConnection();

            $builder = $wp->table('customer')->select(['customer_id', 'first_name', 'last_name'])
                          ->where('first_name', 'MARY')
                          ->where('last_name', 'JONES');

            $result = $wp->selectFromWriteConnection($builder->toSql(), $builder->getBindings());

            self::assertSame($result, [

                ['id' => 1, 'first_name' => 'MARY', 'last_name' => 'SMITH'],
                ['id' => 4, 'first_name' => 'BARBARA', 'last_name' => 'JONES'],

            ]);

        }

        /** @test */
        public function an_empty_array_is_returned_if_and_error_occurred_during_a_select_or_no_query_matched(
        )
        {

            $sql = "select `customer_id`, `first_name`, `last_name` from `wp_customer` where `first_name` = ? and `last_name` = ?";

            $this->wpdb->shouldReceive('doSelect')
                       ->once()
                       ->with($sql, ['MARY', 'JONES'])
                       ->andReturn([]);

            $wp = $this->newWpConnection();

            $builder = $wp->table('customer')->select(['customer_id', 'first_name', 'last_name'])
                          ->where('first_name', 'MARY')
                          ->where('last_name', 'JONES');

            $result = $wp->select($builder->toSql(), $builder->getBindings());

            self::assertSame($result, []);

        }

        /** @test */
        public function successful_inserts_return_true()
        {


            $sql = "insert into `wp_customer` (`customer_id`, `first_name`, `store_id`) values (?, ?, ?), (?, ?, ?)";

            $this->wpdb->shouldReceive('doStatement')
                       ->once()
                       ->with($sql, [1, 'calvin', 1, 2, 'marlon', 2])
                       ->andReturn(true);

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


            $this->wpdb->shouldReceive('doStatement')
                       ->once()
                       ->with("insert into `wp_customer` (`customer_id`) values (?)", [1000])
                       ->andReturn(false);

            $wp = $this->newWpConnection();

            $success = $wp->table('customer')->insert([

                'customer_id' => 1000,

            ]);

            self::assertFalse($success);


        }

        /** @test */
        public function successful_updates_return_the_number_of_affected_rows()
        {

            $safe_sql = "update `wp_customer` set `first_name` = ? where `customer_id` = ?";

            $this->wpdb->shouldReceive('doAffectingStatement')
                       ->once()
                       ->with($safe_sql, ['calvin', 1])
                       ->andReturn(1);

            $wp = $this->newWpConnection();

            $affected_rows = $wp->table('customer')
                                ->where('customer_id', 1)
                                ->update(['first_name' => 'calvin']);

            self::assertEquals($affected_rows, 1);

        }

        /** @test */
        public function updates_with_no_affected_rows_return_zero()
        {

            $sql = "update `wp_customer` set `first_name` = ? where `customer_id` = ?";

            $this->wpdb->shouldReceive('doAffectingStatement')
                       ->once()
                       ->with($sql, ['calvin', 1])
                       ->andReturn(0);

            $wp = $this->newWpConnection();

            $affected_rows = $wp->table('customer')
                                ->where('customer_id', 1)
                                ->update(['first_name' => 'calvin']);

            self::assertEquals($affected_rows, 0);

        }

        /** @test */
        public function deletes_return_the_amount_of_deleted_rows()
        {

            $sql = "delete from `wp_customer` where `customer_id` < ?";

            $this->wpdb->shouldReceive('doAffectingStatement')
                       ->once()
                       ->with($sql, [3])
                       ->andReturn(2);

            $wp = $this->newWpConnection();

            $deleted_rows = $wp->table('customer')
                               ->where('customer_id', '<', 3)
                               ->delete();

            self::assertEquals($deleted_rows, 2);

        }

        /** @test */
        public function zero_gets_returned_if_no_row_got_deleted()
        {

            $sql = "delete from `wp_customer` where `customer_id` > ?";

            $this->wpdb->shouldReceive('doAffectingStatement')
                       ->once()
                       ->with($sql, [3000])
                       ->andReturn(0);

            $wp = $this->newWpConnection();

            $deleted_rows = $wp->table('customer')
                               ->where('customer_id', '>', 3000)
                               ->delete();

            self::assertEquals($deleted_rows, 0);

        }


        /**
         *
         *
         *
         *
         *
         * Logging tests
         *
         *
         *
         *
         *
         */

        /** @test */
        public function nothing_gets_executed_in_for_selects()
        {

            $wp = $this->newWpConnectionWithSpy();

            $queries = $wp->pretend(function ($wp) {

                $result1 = $wp->select('foo bar', ['baz', true]);
                $result2 = $wp->select('biz baz', ['boo', false]);

                $this->assertSame([], $result1);
                $this->assertSame([], $result2);

            });

            $this->assertSame('foo bar', $queries[0]['query']);
            $this->assertEquals(['baz', 1], $queries[0]['bindings']);
            $this->assertTrue(is_float($queries[0]['time']));

            $this->assertSame('biz baz', $queries[1]['query']);
            $this->assertEquals(['boo', 0], $queries[1]['bindings']);
            $this->assertTrue(is_float($queries[1]['time']));

            $this->wpdb->shouldNotHaveReceived('doSelect');

        }

        /** @test */
        public function nothing_gets_executed_in_for_select_one()
        {

            $wp = $this->newWpConnectionWithSpy();

            $queries = $wp->pretend(function ($wp) {

                $result1 = $wp->selectOne('foo bar', ['baz', true]);
                $result2 = $wp->selectOne('biz baz', ['boo', false]);

                $this->assertSame([], $result1);
                $this->assertSame([], $result2);

            });

            $this->assertSame('foo bar', $queries[0]['query']);
            $this->assertEquals(['baz', 1], $queries[0]['bindings']);
            $this->assertTrue(is_float($queries[0]['time']));

            $this->assertSame('biz baz', $queries[1]['query']);
            $this->assertEquals(['boo', 0], $queries[1]['bindings']);
            $this->assertTrue(is_float($queries[1]['time']));

            $this->wpdb->shouldNotHaveReceived('doSelectOne');

        }

        /** @test */
        public function nothing_gets_executed_in_for_inserts()
        {

            $wp = $this->newWpConnectionWithSpy();

            $queries = $wp->pretend(function ($wp) {

                $result1 = $wp->insert('foo bar', ['baz', true]);
                $result2 = $wp->insert('biz baz', ['boo', false]);

                $this->assertSame(true, $result1);
                $this->assertSame(true, $result2);

            });

            $this->assertSame('foo bar', $queries[0]['query']);
            $this->assertEquals(['baz', 1], $queries[0]['bindings']);
            $this->assertTrue(is_float($queries[0]['time']));

            $this->assertSame('biz baz', $queries[1]['query']);
            $this->assertEquals(['boo', 0], $queries[1]['bindings']);
            $this->assertTrue(is_float($queries[1]['time']));

            $this->wpdb->shouldNotHaveReceived('doStatement');

        }

        /** @test */
        public function nothing_gets_executed_in_for_updates()
        {

            $wp = $this->newWpConnectionWithSpy();

            $queries = $wp->pretend(function ($wp) {

                $result1 = $wp->update('foo bar', ['baz', true]);
                $result2 = $wp->update('biz baz', ['boo', false]);

                $this->assertSame(0, $result1);
                $this->assertSame(0, $result2);

            });

            $this->assertSame('foo bar', $queries[0]['query']);
            $this->assertEquals(['baz', 1], $queries[0]['bindings']);
            $this->assertTrue(is_float($queries[0]['time']));

            $this->assertSame('biz baz', $queries[1]['query']);
            $this->assertEquals(['boo', 0], $queries[1]['bindings']);
            $this->assertTrue(is_float($queries[1]['time']));

            $this->wpdb->shouldNotHaveReceived('doAffectingStatement');

        }

        /** @test */
        public function nothing_gets_executed_in_for_deletes()
        {

            $wp = $this->newWpConnectionWithSpy();

            $queries = $wp->pretend(function ($wp) {

                $result1 = $wp->delete('foo bar', ['baz', true]);
                $result2 = $wp->delete('biz baz', ['boo', false]);

                $this->assertSame(0, $result1);
                $this->assertSame(0, $result2);

            });

            $this->assertSame('foo bar', $queries[0]['query']);
            $this->assertEquals(['baz', 1], $queries[0]['bindings']);
            $this->assertTrue(is_float($queries[0]['time']));

            $this->assertSame('biz baz', $queries[1]['query']);
            $this->assertEquals(['boo', 0], $queries[1]['bindings']);
            $this->assertTrue(is_float($queries[1]['time']));

            $this->wpdb->shouldNotHaveReceived('doAffectingStatement');

        }

        /** @test */
        public function nothing_gets_executed_in_for_unprepared_queries()
        {

            $wp = $this->newWpConnectionWithSpy();

            $queries = $wp->pretend(function ($wp) {

                $result1 = $wp->unprepared('foo bar', ['baz', true]);
                $result2 = $wp->unprepared('biz baz', ['boo', false]);

                $this->assertSame(true, $result1);
                $this->assertSame(true, $result2);

            });

            $this->assertSame('foo bar', $queries[0]['query']);
            $this->assertEquals([], $queries[0]['bindings']);
            $this->assertTrue(is_float($queries[0]['time']));

            $this->assertSame('biz baz', $queries[1]['query']);
            $this->assertEquals([], $queries[1]['bindings']);
            $this->assertTrue(is_float($queries[1]['time']));

            $this->wpdb->shouldNotHaveReceived('doUnprepared');

        }

        /** @test */
        public function nothing_gets_executed_for_cursor_selects()
        {

            $wp = $this->newWpConnectionWithSpy();

            $queries = $wp->pretend(function ($wp) {

                $result1 = $wp->cursor('foo bar', ['baz', true]);
                $result2 = $wp->cursor('biz baz', ['boo', false]);

                $this->assertSame([], $result1);
                $this->assertSame([], $result2);

            });

            $this->assertSame('foo bar', $queries[0]['query']);
            $this->assertEquals(['baz', 1], $queries[0]['bindings']);
            $this->assertTrue(is_float($queries[0]['time']));

            $this->assertSame('biz baz', $queries[1]['query']);
            $this->assertEquals(['boo', 0], $queries[1]['bindings']);
            $this->assertTrue(is_float($queries[1]['time']));

            $this->wpdb->shouldNotHaveReceived('doCursorSelect');

        }


        /**
         *
         *
         *
         *
         *
         *
         *
         *
         *
         * Transaction Tests
         *
         *
         *
         *
         *
         *
         *
         *
         */

        private function newWpConnection() : WpConnection
        {

            return new WpConnection($this->wpdb);

        }

        private function newWpConnectionWithSpy() : WpConnection
        {

            $this->wpdb = m::spy(FakeWpdb::class);
            $this->wpdb->prefix = 'wp_';

            return new WpConnection($this->wpdb);

        }


    }
