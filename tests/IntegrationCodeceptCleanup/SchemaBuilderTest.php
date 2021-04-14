<?php


    namespace Tests\IntegrationCodeceptCleanup;

    use Codeception\TestCase\WPTestCase;
    use WpEloquent\ExtendsWpdb\DbFactory;
    use WpEloquent\MySqlSchemaBuilder;
    use WpEloquent\WpConnection;

    class SchemaBuilderTest extends WPTestCase
    {

        /**
         * @var MySqlSchemaBuilder
         */
        private $builder;

        /**
         * @var WpConnection
         */
        private $wp_connection;


        protected function setUp() : void
        {

            parent::setUp();

            global $wpdb;

            $correct_db = getenv('TEST_SITE_DB_NAME');

            if (  $correct_db  !== DB_NAME  ) {

                $wpdb->select($correct_db);
                $wpdb->dbname = $correct_db;
            }

            $this->wp_connection = new WpConnection($wpdb);
            $this->builder = new MySqlSchemaBuilder($this->wp_connection);

        }


        /** @test */
        public function all_tables_can_be_dropped()
        {

            $this->assertNotEmpty($this->builder->getAllTables());

            $this->builder->dropAllTables();

            $this->assertEmpty($this->builder->getAllTables());


        }


    }