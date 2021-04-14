<?php







    namespace Tests\Unit;

    use Codeception\Test\Unit as CodeceptUnit;
    use mysqli_sql_exception;
    use WpEloquent\DatabaseErrorHandler;
    use WpEloquent\QueryException;

    /** @todo Remove Brain Monkey later when we integrate BetterWpHooks */
    use Brain\Monkey;

    class DatabaseErrorHandlerTest extends CodeceptUnit
    {


        protected function setUp() : void
        {

            parent::setUp();

            Monkey\setUp();


        }

        protected function tearDown() : void
        {

            Monkey\tearDown();

            parent::tearDown();



        }

        /** @test */
        public function query_exceptions_are_thrown_out_in_debug_mode () {



            $exception = new QueryException('select foo', ['bar'], new mysqli_sql_exception());

            $this->expectExceptionObject($exception);

            $handler = new DatabaseErrorHandler(true, true );

            $handler->handle($exception);

            $this->assertFalse( has_filter('wp_php_error_message', 'function ($message)' ) );

        }


        /** @test */
        public function query_exceptions_are_translated_to_human_readable_warnings_in_production () {


            $exception = new QueryException('select foo', ['bar'], new mysqli_sql_exception());

            $handler = new DatabaseErrorHandler(false, false );
            $handler->handle($exception);

            $this->assertTrue( has_filter('wp_php_error_message', 'function ($message)' ) != null );

        }

    }


