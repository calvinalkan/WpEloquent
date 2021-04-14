<?php


    namespace Tests\Unit;

    use Codeception\Test\Unit as CodeceptUnit;
    use Exception;
    use Illuminate\Database\Query\Builder;
    use Mockery as m;
    use Tests\Stubs\FakeWpdb;
    use WpEloquent\ExtendsWpdb\BetterWpDb;
    use WpEloquent\MySqlSchemaBuilder;
    use WpEloquent\WpConnection;
    use Illuminate\Database\Query\Grammars\MySqlGrammar as MySqlQueryGrammar;
    use Illuminate\Database\Query\Processors\MySqlProcessor;
    use Illuminate\Database\Schema\Grammars\MySqlGrammar as MySqlSchemaGrammar;

    class WpConnectionTest extends CodeceptUnit
    {


        /**
         * @var BetterWpDb
         */
        private $wpdb;


        protected function setUp() : void
        {

            parent::setUp();


            $this->wpdb = m::mock(FakeWpdb::class);
            $this->wpdb->prefix = 'wp_';
            $this->wpdb->dbname = 'wp_eloquent';


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


            $this->wpdb->shouldReceive('doSelect')
                       ->once()
                       ->with(
                           "select * from `wp_users` where `user_name` = ? and `id` = ? limit 1",
                           ['calvin', 1])
                       ->andReturn([

                           ['user_id' => 1, 'user_name' => 'calvin'],
                           ['user_id' => 2, 'user_name' => 'marlon']

                           ]);

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
        public function an_empty_array_is_returned_if_and_error_occurred_during_a_select_or_no_query_matched()
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

            $queries = $wp->pretend(function (WpConnection $wp) {

                $result1 = $wp->cursor('foo bar', ['baz', true]);
                $result2 = $wp->cursor('biz baz', ['boo', false]);

                foreach ($result1 as $item) {

                    $this->fail('This should not execute');

                }
                foreach ($result2 as $item) {

                    $this->fail('This should not execute');

                }


            });

            $this->assertSame('foo bar', $queries[0]['query']);
            $this->assertEquals(['baz', 1], $queries[0]['bindings']);
            $this->assertTrue(is_float($queries[0]['time']));

            $this->assertSame('biz baz', $queries[1]['query']);
            $this->assertEquals(['boo', 0], $queries[1]['bindings']);
            $this->assertTrue(is_float($queries[1]['time']));

            $this->wpdb->shouldNotHaveReceived('doCursorSelect');

        }


        /** @test */
        public function transaction_level_does_not_increment_when_an_exception_is_thrown()
        {

            $wp = $this->newWpTransactionConnection();

            $this->wpdb->shouldReceive('check_connection')->andReturnFalse();

            $this->wpdb->shouldReceive('startTransaction')->once()
                       ->andThrow(\Tests\Stubs\TestException::class);

            try {
                $wp->beginTransaction();
            }
            catch (\Tests\Stubs\TestException $e) {

                $this->assertEquals(0, $wp->transactionLevel());

            }
        }

        /** @test */
        public function begin_transaction_reconnects_on_lost_connection()
        {


            $wp = $this->newWpTransactionConnection();

            $this->wpdb->shouldReceive('startTransaction')->once()
                       ->andThrows(new Exception('the server has gone away'));
            $this->wpdb->shouldReceive('startTransaction');
            $this->wpdb->shouldReceive('createSavePoint')->once()->with('SAVEPOINT trans1');

            $wp->beginTransaction();

            self::assertSame(1, $wp->transactionLevel());

        }


        /** @test */
        public function if_an_exception_occurs_during_the_beginning_of_a_transaction_we_try_once_again()
        {

            $wp = $this->newWpTransactionConnection();

            $this->wpdb->shouldReceive('startTransaction')->once()
                       ->andThrow(\Tests\Stubs\TestException::class);

            try {
                $wp->beginTransaction();
            }
            catch (\Tests\Stubs\TestException $e) {

                $this->assertEquals(0, $wp->transactionLevel());

            }
        }

        /** @test */
        public function if_we_fail_once_beginning_a_transaction_but_succeed_the_second_time_the_count_is_increased()
        {

            $wp = $this->newWpTransactionConnection();

            $this->wpdb->shouldReceive('startTransaction')->once()
                       ->andThrows(new Exception('server has gone away'));
            $this->wpdb->shouldReceive('startTransaction')->once()->andReturnNull();
            $this->wpdb->shouldReceive('createSavepoint')->once()->with('SAVEPOINT trans1');

            try {

                $wp->beginTransaction();

                $this->assertEquals(1, $wp->transactionLevel());


            }
            catch (Exception $e) {

                $this->fail('Unexpected Exception: '.$e->getMessage());


            }


        }

        /** @test */
        public function different_save_points_can_be_created_manually()
        {

            $wp = $this->newWpTransactionConnection();

            $this->wpdb->shouldReceive('startTransaction')->once()->andReturnNull();
            $this->wpdb->shouldReceive('createSavepoint')->once()->with('SAVEPOINT trans1');
            $this->wpdb->shouldReceive('createSavepoint')->once()->with('SAVEPOINT trans2');

            try {

                $wp->beginTransaction();
                $wp->beginTransaction();

                $this->assertEquals(2, $wp->transactionLevel());


            }
            catch (Exception $e) {

                $this->fail('Unexpected Exception: '.$e->getMessage());


            }

        }

        /** @test */
        public function a_transaction_can_be_committed_manually()
        {

            $wp = $this->newWpTransactionConnection();

            $this->wpdb->shouldReceive('startTransaction')->once()->andReturnNull();
            $this->wpdb->shouldReceive('createSavepoint')->once()->with('SAVEPOINT trans1');

            $this->wpdb->shouldReceive('commitTransaction')->once();

            try {

                $wp->beginTransaction();

                $this->assertEquals(1, $wp->transactionLevel());

                $wp->commit();

            }
            catch (Exception $e) {

                $this->fail('Unexpected Exception: '.$e->getMessage());


            }

        }

        /** @test */
        public function a_transaction_can_be_committed_manually_with_several_custom_savepoints()
        {


            $wp = $this->newWpTransactionConnection();

            $this->wpdb->shouldReceive('startTransaction')->once()->andReturnNull();
            $this->wpdb->shouldReceive('createSavepoint')->once()->with('SAVEPOINT trans1');
            $this->wpdb->shouldReceive('createSavepoint')->once()->with('SAVEPOINT trans2');
            $this->wpdb->shouldReceive('commitTransaction')->once();

            try {

                $wp->beginTransaction();
                $wp->beginTransaction();

                $this->assertEquals(2, $wp->transactionLevel());

                $wp->commit();

                $this->assertEquals(0, $wp->transactionLevel());

            }
            catch (Exception $e) {

                $this->fail('Unexpected Exception: '.$e->getMessage());


            }

        }

        /** @test */
        public function manual_rollbacks_restore_the_latest_save_point_by_default()
        {


            $wp = $this->newWpTransactionConnection();

            $this->wpdb->shouldReceive('startTransaction')->once()->andReturnNull();
            $this->wpdb->shouldReceive('createSavepoint')->once()->with('SAVEPOINT trans1');
            $this->wpdb->shouldReceive('createSavepoint')->once()->with('SAVEPOINT trans2');
            $this->wpdb->shouldReceive('createSavepoint')->once()->with('SAVEPOINT trans3');
            $this->wpdb->shouldReceive('rollbackTransaction')->once()
                       ->with('ROLLBACK TO SAVEPOINT trans3');

            $wp->beginTransaction();
            $wp->beginTransaction();
            $wp->beginTransaction();

            // error happens here.

            $wp->rollBack();

            self::assertEquals(2, $wp->transactionLevel());


        }

        /** @test */
        public function nothing_happens_if_an_invalid_level_is_provided_for_rollbacks()
        {

            $wp = $this->newWpTransactionConnection();

            $this->wpdb->shouldReceive('startTransaction')->once()->andReturnNull();
            $this->wpdb->shouldReceive('createSavepoint')->once()->with('SAVEPOINT trans1');
            $this->wpdb->shouldReceive('createSavepoint')->once()->with('SAVEPOINT trans2');
            $this->wpdb->shouldNotReceive('rollbackTransaction');

            $wp->beginTransaction();
            $wp->beginTransaction();

            $this->assertEquals(2, $wp->transactionLevel());

            $wp->rollBack(-4);
            $wp->rollBack(3);


        }

        /** @test */
        public function manual_rollbacks_to_custom_levels_work()
        {

            $wp = $this->newWpTransactionConnection();

            $this->wpdb->shouldReceive('startTransaction')->once()->andReturnNull();
            $this->wpdb->shouldReceive('createSavepoint')->once()->with('SAVEPOINT trans1');
            $this->wpdb->shouldReceive('createSavepoint')->once()->with('SAVEPOINT trans2');
            $this->wpdb->shouldReceive('createSavepoint')->once()->with('SAVEPOINT trans3');
            $this->wpdb->shouldReceive('rollbackTransaction')->once()
                       ->with('ROLLBACK TO SAVEPOINT trans2');

            $wp->beginTransaction();
            $wp->beginTransaction();
            $wp->beginTransaction();

            // error happens here.

            $wp->rollBack(2);

            self::assertEquals(1, $wp->transactionLevel());

        }

        /** @test */
        public function the_transaction_is_rolled_back_to_the_standard_savepoint_if_only_once_savepoint_exists()
        {


            $wp = $this->newWpTransactionConnection();

            $this->wpdb->shouldReceive('startTransaction')->once()->andReturnNull();
            $this->wpdb->shouldReceive('createSavepoint')->once()->with('SAVEPOINT trans1');
            $this->wpdb->shouldReceive('rollbackTransaction')->once()
                       ->with('ROLLBACK TO SAVEPOINT trans1');

            $wp->beginTransaction();

            // error happens here.
            $wp->rollBack();

            self::assertEquals(0, $wp->transactionLevel());


        }


        /** @test */
        public function the_savepoint_methods_serves_as_an_alias_for_begin_transaction()
        {


            $wp = $this->newWpTransactionConnection();

            $this->wpdb->shouldReceive('startTransaction')->once()->andReturnNull();
            $this->wpdb->shouldReceive('createSavepoint')->once()->with('SAVEPOINT trans1');
            $this->wpdb->shouldReceive('createSavepoint')->once()->with('SAVEPOINT trans2');
            $this->wpdb->shouldReceive('createSavepoint')->once()->with('SAVEPOINT trans3');
            $this->wpdb->shouldReceive('rollbackTransaction')->once()
                       ->with('ROLLBACK TO SAVEPOINT trans2');

            $wp->beginTransaction();
            $wp->savepoint();
            $wp->savepoint();

            // error happens here.

            $wp->rollBack(2);

            self::assertEquals(1, $wp->transactionLevel());


        }


        /** @test */
        public function interacting_with_several_custom_savepoints_manually_works()
        {

            $wp = $this->newWpTransactionConnection();

            $this->wpdb->shouldReceive('startTransaction')->once();
            $this->wpdb->shouldReceive('createSavepoint')->once()->with('SAVEPOINT trans1');
            $this->wpdb->shouldReceive('createSavepoint')->once()->with('SAVEPOINT trans2');

            $this->wpdb->shouldReceive('rollbackTransaction')->once()
                       ->with('ROLLBACK TO SAVEPOINT trans2');

            $wp->beginTransaction();

            $this->wpdb->shouldReceive('doStatement')->once()->andReturnTrue();
            $this->wpdb->shouldReceive('doStatement')->once()->andThrow(Exception::class);

            try {

                $wp->insert('foobar', ['foo']);

                $wp->savepoint();

                $wp->insert('bizbar', ['foo']);

                $wp->savepoint();

                $wp->update('foobar', ['biz']);

            }

            catch (Exception $e) {

                $wp->rollBack();

                self::assertEquals(1, $wp->transactionLevel());

            }


        }


        /** @test */
        public function the_transaction_can_be_rolled_back_completely_when_if_zero_is_provided()
        {

            $wp = $this->newWpTransactionConnection();

            $this->wpdb->shouldReceive('startTransaction')->once()->andReturnNull();
            $this->wpdb->shouldReceive('createSavepoint')->once()->with('SAVEPOINT trans1');
            $this->wpdb->shouldReceive('createSavepoint')->once()->with('SAVEPOINT trans2');
            $this->wpdb->shouldReceive('createSavepoint')->once()->with('SAVEPOINT trans3');
            $this->wpdb->shouldReceive('rollbackTransaction')->once()->withNoArgs();

            $wp->beginTransaction();
            $wp->beginTransaction();
            $wp->beginTransaction();

            // error happens here.

            $wp->rollBack(0);

            self::assertEquals(0, $wp->transactionLevel());

        }

        /** @test */
        public function basic_automated_transactions_work_when_no_error_occurs()
        {

            $wp = $this->newWpTransactionConnection();
            $this->wpdb->shouldReceive('startTransaction')->once();
            $this->wpdb->shouldReceive('createSavepoint')->once()->with('SAVEPOINT trans1');
            $this->wpdb->shouldReceive('commitTransaction')->once();
            $this->wpdb->shouldReceive('doAffectingStatement')->once()->with('foo', ['bar'])
                       ->andReturn(3);

            $result = $wp->transaction(function (WpConnection $wp) {

                return $wp->update('foo', ['bar']);

            });

            self::assertSame(3, $result);

        }

        /** @test */
        public function when_an_error_occurs_in_the_actual_query_we_try_again_until_it_works_or_no_attempts_are_left()
        {

            $wp = $this->newWpTransactionConnection();
            $this->wpdb->shouldReceive('startTransaction')->times(4);
            $this->wpdb->shouldReceive('createSavepoint')->times(4)->with('SAVEPOINT trans1');

            $this->wpdb->shouldReceive('rollbackTransaction')->times(3)
                       ->with('ROLLBACK TO SAVEPOINT trans1');

            $this->wpdb->shouldReceive('commitTransaction')->once();

            $this->wpdb->shouldReceive('doAffectingStatement')->once()->with('foo', ['bar'])
                       ->andReturn(3);

            $result = $wp->transaction(function (WpConnection $wp) {

                static $count = 0;

                if ($count != 3) {

                    $count++;

                    throw new Exception();

                }

                return $wp->update('foo', ['bar']);

            }, 4);

            self::assertSame(3, $result);
            self::assertSame(0, $wp->transactionLevel());


        }

        /** @test */
        public function if_the_query_is_not_successful_after_the_max_attempt_we_throw_an_exception_all_the_way_out()
        {

            $wp = $this->newWpTransactionConnection();
            $this->wpdb->shouldReceive('startTransaction')->times(3);
            $this->wpdb->shouldReceive('createSavepoint')->times(3)->with('SAVEPOINT trans1');
            $this->wpdb->shouldReceive('rollbackTransaction')->times(2)
                       ->with('ROLLBACK TO SAVEPOINT trans1');
            $this->wpdb->shouldReceive('rollbackTransaction')->once()->withNoArgs();

            $this->wpdb->shouldNotReceive('commitTransaction');

            $this->expectExceptionMessage('Database Error');

            $wp->transaction(function () {

                throw new Exception('Database Error');

            }, 3);

            self::assertSame(0, $wp->transactionLevel());
        }

        /** @test */
        public function if_we_have_a_concurrency_error_we_retry_until_no_attempts_are_left()
        {

            $wp = $this->newWpTransactionConnection();
            $this->wpdb->shouldReceive('startTransaction')->times(1);
            $this->wpdb->shouldReceive('createSavepoint')->times(1)->with('SAVEPOINT trans1');
            $this->wpdb->shouldReceive('createSavepoint')->times(1)->with('SAVEPOINT trans2');
            $this->wpdb->shouldReceive('createSavepoint')->times(1)->with('SAVEPOINT trans3');
            $this->wpdb->shouldReceive('createSavepoint')->times(1)->with('SAVEPOINT trans4');

            $this->wpdb->shouldNotReceive('rollbackTransaction');
            $this->wpdb->shouldNotReceive('doAffectingStatement');

            $this->expectExceptionMessage('deadlock detected');

            $wp->transaction(function (WpConnection $wp) {

                static $count = 0;

                if ($count < 5) {

                    $count++;

                    throw new Exception('deadlock detected');

                }

                return $wp->update('foo', ['bar']);

            }, 4);

            self::assertSame(0, $wp->transactionLevel());

        }

        /** @test */
        public function concurrency_errors_during_commits_are_retried()
        {

            $wp = $this->newWpTransactionConnection();

            $this->wpdb->shouldReceive('startTransaction')->once();
            $this->wpdb->shouldReceive('createSavepoint');

            $this->wpdb->shouldReceive('commitTransaction')->twice()
                       ->andThrows(new Exception('deadlock detected'));
            $this->wpdb->shouldReceive('commitTransaction')->once();

            $count = $wp->transaction(function () {

                static $count = 0;

                $count++;

                return $count;

            }, 3);

            self::assertSame(3, $count);
            self::assertSame(0, $wp->transactionLevel());

        }

        /** @test */
        public function commit_errors_due_to_lost_connections_throw_an_exception()
        {


            $wp = $this->newWpTransactionConnection();

            $this->wpdb->shouldReceive('startTransaction')->once();
            $this->wpdb->shouldReceive('createSavepoint');

            $this->wpdb->shouldReceive('commitTransaction')->once()
                       ->andThrows(new Exception('server has gone away'));

            $this->expectExceptionMessage('server has gone away');

            $count = $wp->transaction(function () {

                static $count = 0;

                $count++;

                return $count;

            }, 3);

            self::assertNull($count);
            self::assertSame(0, $wp->transactionLevel());


        }

        /** @test */
        public function rollback_exceptions_reset_the_transaction_count_if_its_a_lost_connection()
        {

            $wp = $this->newWpTransactionConnection();

            $this->wpdb->shouldReceive('startTransaction')->once();
            $this->wpdb->shouldReceive('createSavepoint');

            $this->wpdb->shouldReceive('rollbackTransaction')
                       ->with('ROLLBACK TO SAVEPOINT trans1')
                       ->andThrow(new Exception('server has gone away'));

            $this->expectExceptionMessage('server has gone away');

            $wp->transaction(function () {

                throw new Exception();

            }, 3);

            self::assertSame(0, $wp->transactionLevel());


        }


        private function newWpConnection() : WpConnection
        {

            return new WpConnection($this->wpdb);

        }

        private function newWpTransactionConnection() : WpConnection
        {

            $this->wpdb = m::mock(FakeWpdb::class);
            $this->wpdb->prefix = 'wp_';
            $this->wpdb->dbname = 'wp_eloquent';
            $this->wpdb->shouldReceive('check_connection')->andReturn(true)->byDefault();

            return new WpConnection($this->wpdb);

        }

        private function newWpConnectionWithSpy() : WpConnection
        {

            $this->wpdb = m::spy(FakeWpdb::class);
            $this->wpdb->prefix = 'wp_';
            $this->wpdb->dbname = 'wp_';

            $connection = new WpConnection($this->wpdb);

            return $connection;

            // $connection->cursor('foo', ['bar']);

        }


    }
