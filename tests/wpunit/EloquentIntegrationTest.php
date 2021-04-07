<?php


    namespace Tests\wpunit;


    use Codeception\TestCase\WPTestCase;
    use Illuminate\Database\Eloquent\Model;
    use WpEloquent\Resolver;

    class EloquentIntegrationTest extends WPTestCase {


        public function _setUp()
        {

            parent::_setUp();

            global $wpdb;

            Model::setConnectionResolver( new Resolver( clone $wpdb ));

        }

        /** @test */
        public function fly_test () {


            $users = User::first();
            $foo = 'bar';


        }



    }


    class User extends Model {


        protected $primaryKey = 'ID';


    }

