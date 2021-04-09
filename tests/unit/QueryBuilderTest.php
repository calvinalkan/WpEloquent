<?php


    namespace Tests\unit;

    use Codeception\TestCase\WPTestCase;
    use Illuminate\Database\Query\Builder;
    use PHPUnit\Framework\TestCase;
    use WpEloquent\WpConnection;
    use Mockery as m;


    class QueryBuilderTest extends WPTestCase
    {

        /**
         * @test
         * @doesNotPerformAssertions
         */
        public function test_basic_select()
        {


            // $wpdb =  m::mock(\wpdb::class);
            // $wpdb->prefix = 'wp_';
            //
            // $wpdb->shouldReceive('get_results')
            //      ->once()
            //      ->with("select * from `wp_users`", ARRAY_A );
            // $wp = new WpConnection($wpdb);

            global $wpdb;

            $wp = new WpConnection( clone $wpdb );

            $builder = new Builder($wp);

            // $result = $builder->select('*')->from('users')->get();

            $foo = 'bar';

        }
        /**
         * @test
         * @doesNotPerformAssertions
         */
        public function test_basic_select1()
        {


            // $wpdb =  m::mock(\wpdb::class);
            // $wpdb->prefix = 'wp_';
            //
            // $wpdb->shouldReceive('get_results')
            //      ->once()
            //      ->with("select * from `wp_users`", ARRAY_A );
            // $wp = new WpConnection($wpdb);

            global $wpdb;

            $wp = new WpConnection( clone $wpdb );

            $builder = new Builder($wp);

            // $result = $builder->select('*')->from('users')->get();

            $foo = 'bar';

        }
        /**
         * @test
         * @doesNotPerformAssertions
         */
        public function test_basic_select2()
        {


            // $wpdb =  m::mock(\wpdb::class);
            // $wpdb->prefix = 'wp_';
            //
            // $wpdb->shouldReceive('get_results')
            //      ->once()
            //      ->with("select * from `wp_users`", ARRAY_A );
            // $wp = new WpConnection($wpdb);

            global $wpdb;

            $wp = new WpConnection( clone $wpdb );

            $builder = new Builder($wp);

            // $result = $builder->select('*')->from('users')->get();

            $foo = 'bar';

        }
        /**
         * @test
         * @doesNotPerformAssertions
         */
        public function test_basic_select3()
        {


            // $wpdb =  m::mock(\wpdb::class);
            // $wpdb->prefix = 'wp_';
            //
            // $wpdb->shouldReceive('get_results')
            //      ->once()
            //      ->with("select * from `wp_users`", ARRAY_A );
            // $wp = new WpConnection($wpdb);

            global $wpdb;

            $wp = new WpConnection( clone $wpdb );

            $builder = new Builder($wp);

            // $result = $builder->select('*')->from('users')->get();

            $foo = 'bar';

        }
        /**
         * @test
         * @doesNotPerformAssertions
         */
        public function test_basic_select4()
        {


            // $wpdb =  m::mock(\wpdb::class);
            // $wpdb->prefix = 'wp_';
            //
            // $wpdb->shouldReceive('get_results')
            //      ->once()
            //      ->with("select * from `wp_users`", ARRAY_A );
            // $wp = new WpConnection($wpdb);

            global $wpdb;

            $wp = new WpConnection( clone $wpdb );

            $builder = new Builder($wp);

            // $result = $builder->select('*')->from('users')->get();

            $foo = 'bar';

        }
        /**
         * @test
         * @doesNotPerformAssertions
         */
        public function test_basic_select5()
        {


            // $wpdb =  m::mock(\wpdb::class);
            // $wpdb->prefix = 'wp_';
            //
            // $wpdb->shouldReceive('get_results')
            //      ->once()
            //      ->with("select * from `wp_users`", ARRAY_A );
            // $wp = new WpConnection($wpdb);

            global $wpdb;

            $wp = new WpConnection( clone $wpdb );

            $builder = new Builder($wp);

            // $result = $builder->select('*')->from('users')->get();

            $foo = 'bar';

        }
        /**
         * @test
         * @doesNotPerformAssertions
         */
        public function test_basic_select6()
        {


            // $wpdb =  m::mock(\wpdb::class);
            // $wpdb->prefix = 'wp_';
            //
            // $wpdb->shouldReceive('get_results')
            //      ->once()
            //      ->with("select * from `wp_users`", ARRAY_A );
            // $wp = new WpConnection($wpdb);

            global $wpdb;

            $wp = new WpConnection( clone $wpdb );

            $builder = new Builder($wp);

            // $result = $builder->select('*')->from('users')->get();

            $foo = 'bar';

        }
        /**
         * @test
         * @doesNotPerformAssertions
         */
        public function test_basic_select7()
        {


            // $wpdb =  m::mock(\wpdb::class);
            // $wpdb->prefix = 'wp_';
            //
            // $wpdb->shouldReceive('get_results')
            //      ->once()
            //      ->with("select * from `wp_users`", ARRAY_A );
            // $wp = new WpConnection($wpdb);

            global $wpdb;

            $wp = new WpConnection( clone $wpdb );

            $builder = new Builder($wp);

            // $result = $builder->select('*')->from('users')->get();

            $foo = 'bar';

        }
        /**
         * @test
         * @doesNotPerformAssertions
         */
        public function test_basic_select8()
        {


            // $wpdb =  m::mock(\wpdb::class);
            // $wpdb->prefix = 'wp_';
            //
            // $wpdb->shouldReceive('get_results')
            //      ->once()
            //      ->with("select * from `wp_users`", ARRAY_A );
            // $wp = new WpConnection($wpdb);

            global $wpdb;

            $wp = new WpConnection( clone $wpdb );

            $builder = new Builder($wp);

            // $result = $builder->select('*')->from('users')->get();

            $foo = 'bar';

        }
        /**
         * @test
         * @doesNotPerformAssertions
         */
        public function test_basic_select9()
        {


            // $wpdb =  m::mock(\wpdb::class);
            // $wpdb->prefix = 'wp_';
            //
            // $wpdb->shouldReceive('get_results')
            //      ->once()
            //      ->with("select * from `wp_users`", ARRAY_A );
            // $wp = new WpConnection($wpdb);

            global $wpdb;

            $wp = new WpConnection( clone $wpdb );

            $builder = new Builder($wp);

            // $result = $builder->select('*')->from('users')->get();

            $foo = 'bar';

        }
        /**
         * @test
         * @doesNotPerformAssertions
         */
        public function test_basic_select10()
        {


            // $wpdb =  m::mock(\wpdb::class);
            // $wpdb->prefix = 'wp_';
            //
            // $wpdb->shouldReceive('get_results')
            //      ->once()
            //      ->with("select * from `wp_users`", ARRAY_A );
            // $wp = new WpConnection($wpdb);

            global $wpdb;

            $wp = new WpConnection( clone $wpdb );

            $builder = new Builder($wp);

            // $result = $builder->select('*')->from('users')->get();

            $foo = 'bar';

        }




    }