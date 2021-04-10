<?php


    namespace Tests\unit;

    use Illuminate\Database\Query\Builder;
    use Mockery as m;
    use PHPUnit\Framework\TestCase;
    use wpdb;
    use WpEloquent\QuerySanitizer;
    use WpEloquent\WpConnection;

    class QuerySanitizerTest extends TestCase
    {


        /**
         * @var WpConnection
         */
        private $wp;

        /**
         * @var wpdb
         */
        private $wpdb;


        protected function setUp() : void
        {

            parent::setUp();

            if ( ! defined('DB_NAME')) {

                define('DB_NAME', 'wp-eloquent');

            }

            if ( ! defined('ARRAY_A')) {

                define('ARRAY_A', 'ARRAY_A');

            }

            $this->wpdb = m::mock(wpdb::class);
            $this->wpdb->prefix = 'wp_';
            $this->wp = new WpConnection($this->wpdb);

        }

        protected function tearDown() : void
        {

            parent::tearDown();

            m::close();
        }


        /**
         * @test
         * @doesNotPerformAssertions
         */
        public function can_distinguish_between_integer_and_string_values()
        {

            $this->wpdbSpy();

            $builder = $this->newBuilder();

            $builder->select('*')
                    ->from('cities')
                    ->where('id', 1)
                    ->where('name', 'tokyo');

            $sanitizer = $this->newSanitizer($builder->toSql(), $builder->getBindings());

            $sanitizer->sanitize();

            $this->wpdb->shouldHaveReceived('prepare')
                       ->once()
                       ->with("select * from `wp_cities` where `id` = %d and `name` = %s", 1,
                           'tokyo');


        }


        /**
         * @test
         * @doesNotPerformAssertions
         */
        public function can_distinguish_floats_from_integers()
        {


            $this->wpdbSpy();

            $builder = $this->newBuilder();

            $builder->select('*')
                    ->from('accounts')
                    ->where('id', 10)
                    ->where('balance', 999.234454);

            $sanitizer = $this->newSanitizer($builder->toSql(), $builder->getBindings());

            $sanitizer->sanitize();

            $this->wpdb->shouldHaveReceived('prepare')
                       ->once()
                       ->with("select * from `wp_accounts` where `id` = %d and `balance` = %f", 10,
                           999.234454);


        }


        /**
         * @test
         * @doesNotPerformAssertions
         */
        public function like_statements_can_process_underscores_and_percent_signs()
        {

            $this->wpdb->shouldReceive('prepare')
                       ->once()
                       ->with("select * from `wp_cities` where `city` like %s", 'L_nd%')
                       ->andReturn('string');

            $builder = $this->newBuilder();

            $builder->select('*')
                    ->from('cities')
                    ->where('city', 'like', '{L}_{nd}%');

            $sanitizer = new QuerySanitizer(
                $this->wpdb,
                $builder->toSql(),
                $builder->getBindings()
            );

            $sanitizer->sanitize();


        }


        /**
         * @test
         * @doesNotPerformAssertions
         */
        public function like_statements_process_underscores_and_percent_signs_when_using_variables()
        {

            $this->wpdb->shouldReceive('prepare')
                       ->once()
                       ->with("select * from `wp_cities` where `city` like %s", 'Lon%')
                       ->andReturn('string');

            $builder = $this->newBuilder();

            $user_input = 'Lon';

            $builder->select('*')
                    ->from('cities')
                    ->where('city', 'like', '{'.$user_input.'}%');

            $sanitizer = new QuerySanitizer(
                $this->wpdb,
                $builder->toSql(),
                $builder->getBindings()
            );

            $sanitizer->sanitize();

        }


        /**
         * @test
         * @doesNotPerformAssertions
         */
        public function like_statements_can_be_combined_with_normal_statements()
        {


            $this->wpdb->shouldReceive('prepare')
                       ->once()
                       ->with("select * from `wp_cities` where `name` like %s and `id` = %d and `population` >= %d",
                           'L_nd%', 10, 100000);

            $builder = $this->newBuilder();

            $builder->select('*')
                    ->from('cities')
                    ->where('name', 'like', '{L}_{nd}%')
                    ->where('id', 10)
                    ->where('population', '>=', 100000);

            $sanitizer = new QuerySanitizer(
                $this->wpdb,
                $builder->toSql(),
                $builder->getBindings()
            );

            $sanitizer->sanitize();


        }


        /**
         * @test
         * @doesNotPerformAssertions
         */
        public function like_statements_get_escaped_properly_when_including_a_percent_sign()
        {


            $this->wpdb->shouldReceive('prepare')
                       ->once()
                       ->with("select * from `wp_cities` where `city` like %s", 'Lo\%n%')
                       ->andReturn('string');

            $builder = $this->newBuilder();

            $user_input = 'Lo%n';

            $builder->select('*')
                    ->from('cities')
                    ->where('city', 'like', '{'.$user_input.'}%');

            $sanitizer = new QuerySanitizer(
                $this->wpdb,
                $builder->toSql(),
                $builder->getBindings()
            );

            $sanitizer->sanitize();


        }


        /**
         * @test
         * @doesNotPerformAssertions
         */
        public function like_statements_get_escaped_properly_when_including_an_underscore()
        {


            $this->wpdb->shouldReceive('prepare')
                       ->once()
                       ->with("select * from `wp_cities` where `city` like %s", 'L\_on%')
                       ->andReturn('string');

            $builder = $this->newBuilder();

            $user_input = 'L_on';

            $builder->select('*')
                    ->from('cities')
                    ->where('city', 'like', '{'.$user_input.'}%');

            $sanitizer = new QuerySanitizer(
                $this->wpdb,
                $builder->toSql(),
                $builder->getBindings()
            );

            $sanitizer->sanitize();


        }





        private function newBuilder() : Builder
        {

            return new Builder($this->wp);

        }

        /**
         * @param  string  $query
         * @param  array  $bindings
         *
         * @return QuerySanitizer
         */
        private function newSanitizer(string $query, array $bindings) : QuerySanitizer
        {

            return new QuerySanitizer($this->wpdb, $query, $bindings);

        }

        private function wpdbSpy()
        {

            $this->wpdb = m::spy(wpdb::class);
            $this->wpdb->prefix = 'wp_';
            $this->wp = new WpConnection($this->wpdb);


        }

    }


