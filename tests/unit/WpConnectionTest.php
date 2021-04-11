<?php


    namespace Tests\unit;

    use Illuminate\Database\Query\Builder;
    use Mockery as m;
    use PHPUnit\Framework\TestCase;
    use WpEloquent\MySqlSchemaBuilder;
    use WpEloquent\SanitizerFactory;
    use WpEloquent\WpConnection;
    use Illuminate\Database\Query\Grammars\MySqlGrammar as MySqlQueryGrammar;
    use Illuminate\Database\Query\Processors\MySqlProcessor;
    use Illuminate\Database\Schema\Grammars\MySqlGrammar as MySqlSchemaGrammar;

    class WpConnectionTest extends TestCase
    {


        /**
         * @var \wpdb
         */
        private $wpdb;


        protected function setUp() : void
        {

            parent::setUp();

            if ( ! defined('DB_NAME')) {
                define('DB_NAME', 'wp-eloquent');
            }

            $this->wpdb = m::mock(\wpdb::class);
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
                new \DateTime('07.04.2021'),

            ]);

            self::assertSame([

                1,
                0,
                'string',
                10,
                '2021-04-07 00:00:00',

            ], $result);

        }


        private function newWpConnection() : WpConnection
        {

            return new WpConnection($this->wpdb, new SanitizerFactory($this->wpdb));

        }


    }
