<?php


    namespace Tests\Unit;

    use Mockery as m;
    use mysqli_sql_exception;
    use PHPUnit\Framework\TestCase;
    use WpEloquent\ExtendsWpdb\BetterWpDb;

    class BetterWpDbDelegationTest extends TestCase
    {

        /**
         * @var \wpdb
         */
        private $wpdb;

        /**
         * @var \mysqli
         */
        private $mysqli;

        /**
         * @var BetterWpDb
         */
        private $better_wpdb;

        protected function setUp() : void
        {

            parent::setUp();

            $this->wpdb = new FakeWpdb();
            $this->mysqli = m::mock(\mysqli::class);

            $this->better_wpdb = new BetterWpDb($this->mysqli, $this->wpdb);


        }

        /**
         *
         *
         *
         *
         *
         * Public properties
         *
         *
         *
         *
         *
         */

        /** @test */
        public function public_properties_can_be_accessed()
        {

            self::assertSame(false, $this->better_wpdb->show_errors);

            $this->better_wpdb->show_errors = true;

            self::assertSame(true, $this->better_wpdb->show_errors);


        }

        /** @test */
        public function protected_properties_can_be_accessed()
        {

            self::assertSame(5, $this->better_wpdb->reconnect_retries);

            $this->better_wpdb->reconnect_retries = 10;

            self::assertSame(10, $this->better_wpdb->reconnect_retries);


        }

        /** @test */
        public function private_properties_can_be_accessed()
        {

            self::assertSame(false, $this->better_wpdb->has_connected);

            $this->better_wpdb->has_connected = true;

            self::assertSame(true, $this->better_wpdb->has_connected);

        }

        /** @test */
        public function checking_property_existence_works()
        {

            self::assertTrue(isset($this->better_wpdb->show_errors));

            unset($this->better_wpdb->show_errors);

            self::assertFalse(isset($this->better_wpdb->show_errors));

        }

        /** @test */
        public function public_functions_can_be_called()
        {

            $query = $this->better_wpdb->prepare(
                'select * from users where `name` = %s or `age` = %d', 'calvin', 10
            );

            self::assertEquals(
                'Called prepare() with: "select * from users where `name` = %s or `age` = %d", [calvin, 10]',
                $query);


        }

        /** @test */
        public function protected_methods_cant_be_called()
        {

            try {

                $this->better_wpdb->check_safe_collation('foobar');

                $this->fail('No exception raised when it should');

            }
            catch (\Throwable $e) {

                if ($e->getMessage() === 'protected method check_safe_collation() called.') {

                    $this->fail($e->getMessage());

                }

                self::assertInstanceOf(\Error::class, $e);

            }

        }

        /** @test */
        public function private_methods_cant_be_called()
        {

            try {

                $this->better_wpdb->_doQuery('foobar');

                $this->fail('No exception raised when it should');

            }
            catch (\Throwable $e) {

                if ($e->getMessage() === 'private method _doQuery() called.') {

                    $this->fail($e->getMessage());

                }

                self::assertInstanceOf(\Error::class, $e);

            }

        }

        /** @test */
        public function exceptions_resulting_from_magic_methods_calls_always_get_handled_by_the_default_wpdb_class()
        {

            try {

                $this->assertFalse($this->wpdb->called('print_error'));

                $this->better_wpdb->query('foo');

                $this->assertTrue($this->wpdb->called('print_error'));


            }

            catch ( mysqli_sql_exception $e) {

                $this->fail('mysqli_sql_exception was thrown when it should not. ');

            }


        }

        /** @test */
        public function exceptions_from_better_wpdb_method_calls_do_not_get_handled_by_the_default_wpdb_class()
        {


            $this->mysqli
                ->shouldReceive('prepare')
                ->andThrow(new mysqli_sql_exception('Cant prepare statement'));

            try {

                $this->better_wpdb->doSelect('foo', ['bar']);

                $this->fail('Exception was not handled correctly');

            }
            catch (mysqli_sql_exception $e) {

              $this->assertInstanceOf(mysqli_sql_exception::class, $e);
              $this->assertSame('Cant prepare statement', $e->getMessage());
              $this->assertFalse($this->wpdb->called('print_error'));

            }


        }



    }


    class FakeWpdb
    {

        public    $show_errors       = false;
        protected $reconnect_retries = 5;
        private   $has_connected     = false;

        private $called = [];

        public function __get($name)
        {

            return $this->{$name};
        }

        public function __set($name, $value)
        {

            $this->{$name} = $value;
        }

        public function prepare($query, ...$args) : string
        {

            return 'Called prepare() with: "'.$query.'", ['.implode(', ', $args).']';

        }

        private function _doQuery($query)
        {

            throw new \Exception('private method _doQuery() called.');

        }

        protected function check_safe_collation($query)
        {

            throw new \Exception('protected method check_safe_collation() called.');

        }

        public function print_error()
        {

            $this->called['print_error'] = 'print_error';

        }

        public function query($string)
        {

            throw new mysqli_sql_exception();

        }

        public function called ($method) {

             return isset($this->called[$method]);

        }


    }
