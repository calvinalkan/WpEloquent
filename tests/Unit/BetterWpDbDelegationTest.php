<?php


    namespace Tests\Unit;

    use Mockery as m;
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


    }


    class FakeWpdb
    {

        public    $show_errors       = false;
        protected $reconnect_retries = 5;
        private   $has_connected     = false;

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


    }
