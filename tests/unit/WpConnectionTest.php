<?php


    namespace Tests\unit;

    use Mockery as m;
    use PHPUnit\Framework\TestCase;
    use WpEloquent\WpConnection;
    use Illuminate\Database\Query\Grammars\MySqlGrammar as MySqlQueryGrammar;
    use Illuminate\Database\Query\Processors\MySqlProcessor;
    use Illuminate\Database\Schema\Grammars\MySqlGrammar as MySqlSchemaGrammar;
    use Illuminate\Database\Schema\MySqlBuilder as MySqlSchemaBuilder;

    class WpConnectionTest extends TestCase
    {


        /**
         * @var \wpdb
         */
        private $wpdb;

        /**
         * @var string
         */
        private $db_name;

        /**
         * @var string
         */
        private $custom_db_prefix;


        protected function setUp() : void
        {

            parent::setUp();

            $this->wpdb = m::mock(\wpdb::class);
            $this->wpdb->prefix = 'wp_';
            $this->db_name = 'wp-eloquent';
            $this->custom_db_prefix = 'wp_custom';

        }


        protected function tearDown() : void
        {

            parent::tearDown();

            m::close();

        }

        /** @test */
        public function the_default_wpdb_prefix_is_used_when_no_prefix_is_explicitly_provided()
        {

            $wp_connection1 = $this->newWpConnection();

            $grammar = $wp_connection1->getQueryGrammar();

            self::assertSame('wp_', $grammar->getTablePrefix());

        }

        /** @test */
        public function the_custom_prefix_is_used_if_provided()
        {

            $wp_connection1 = $this->newWpConnection($this->custom_db_prefix);

            $grammar = $wp_connection1->getQueryGrammar();

            self::assertSame('wp_custom', $grammar->getTablePrefix());

        }

        /** @test */
        public function the_query_grammar_gets_set_up_correctly()
        {

            $wp_connection = $this->newWpConnection();

            $query_grammar = $wp_connection->getQueryGrammar();

            self::assertInstanceOf(MySqlQueryGrammar::class, $query_grammar);
            self::assertSame('wp_', $query_grammar->getTablePrefix());


        }

        /** @test */
        public function the_post_processor_gets_setup_correctly()
        {

            $wp_connection = $this->newWpConnection();
            $post_processor = $wp_connection->getPostProcessor();

            self::assertInstanceOf(MySqlProcessor::class, $post_processor);


        }

        /** @test */
        public function the_schema_grammar_gets_set_up_correctly()
        {
            $wp_connection = $this->newWpConnection();
            $schema_grammar = $wp_connection->getSchemaGrammar();

            self::assertInstanceOf(MySqlSchemaGrammar::class, $schema_grammar);
            self::assertSame('wp_', $schema_grammar->getTablePrefix());

        }

        /** @test */
        public function bindings_get_prepared_correctly()
        {

            $result = $this->newWpConnection()->prepareBindings([

                TRUE,
                FALSE,
                'string',
                10,
                new \DateTime('07.04.2021')

            ]);

            self::assertSame([

                1,
                0,
                'string',
                10,
                '2021-04-07 00:00:00'

            ], $result);

        }








        private function newWpConnection($db_prefix = null)
        {

            return new WpConnection($this->wpdb, $this->db_name, $db_prefix ?? '');

        }


    }
