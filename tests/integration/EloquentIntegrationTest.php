<?php


    namespace Tests\integration;

    use Codeception\Test\Unit as CodeceptionUnitTest;
    use Codeception\TestCase\WPTestCase;
    use Illuminate\Database\Eloquent\Model;
    use WpEloquent\Resolver;

    class EloquentIntegrationTest extends WPTestCase
    {


        /**
         * @var \UnitTester
         */
        protected $tester;

        protected function setUp() : void
        {

            parent::setUp();

            global $wpdb;


            Model::setConnectionResolver(new Resolver(clone $wpdb));

        }


        /** @test */
        public function fly()
        {

            $user = User::first();

            $user->user_login = 'test';

            $user->save();

            $foo = 'bar';

        }

        /** @test */
        public function foobar () {


            /** @test */

            $foo = 'bar';


        }


    }


    class User extends Model
    {


        protected $primaryKey = 'ID';

        public $timestamps = null;

    }

