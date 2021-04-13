<?php


    namespace Tests\Unit;

    use Codeception\PHPUnit\TestCase;
    use mysqli;
    use ReflectionClass;
    use WpEloquent\ExtendsWpdb\BetterWpDb;
    use WpEloquent\ExtendsWpdb\DbFactory;
    use Mockery as m;

    class DbFactoryTest extends TestCase
    {


        /** @test */
        public function the_factory_constructs_a_correct_instance_of_the_better_wpdb_class()
        {

            $wpdb = new WpdbStub(m::mock(mysqli::class));

            $instance = DbFactory::make($wpdb);

            self::assertInstanceOf(BetterWpDb::class, $instance);

            self::assertInstanceOf(mysqli::class, $this->accessProtected($instance, 'mysqli') );

            self::assertSame($wpdb, $this->accessProtected($instance, 'wpdb'));

            self::assertSame($wpdb->getDbh(), $this->accessProtected($instance, 'mysqli'));

        }


        private function accessProtected($obj, $prop)
        {

            $reflection = new ReflectionClass($obj);
            $property = $reflection->getProperty($prop);
            $property->setAccessible(true);
            return $property->getValue($obj);
        }


    }


    class WpdbStub
    {


        protected $dbh;


        public function __construct(mysqli $mysqli)
        {

            $this->dbh = $mysqli;
        }

        /**
         * @return mysqli
         */
        public function getDbh() : mysqli
        {

            return $this->dbh;
        }

        public function __get($name)
        {
            return $this->$name;
        }


    }