<?php


    namespace Tests\unit;

    use Mockery as m;
    use PHPUnit\Framework\TestCase;
    use wpdb;
    use WpEloquent\WordpressConnection;

    class WordpressConnectionTest extends TestCase
    {

        /**
         * @var wpdb
         */
        private $wpdb;

        protected function setUp () : void
        {

            parent::setUp();

            $this->wpdb = m::mock( wpdb::class );

        }

        protected function tearDown () : void
        {

            parent::tearDown();

            m::close();
        }


        /** @test */
        public function the_wordpress_connection_is_a_singleton ()
        {

            $wpdb_1 = m::mock( wpdb::class );
            $wpdb_2 = m::mock( wpdb::class );

            $connection_1 = WordpressConnection::instance( $wpdb_1 );
            $connection_2 = WordpressConnection::instance( $wpdb_2 );

            self::assertSame( $connection_1, $connection_2 );

        }

        /** @test */
        public function select_one_calls_get_row_and_returns_a_single_result ()
        {

            $connection = $this->getMockConnection();

            $this->wpdb->shouldReceive('get_row')->once()->
            $connection->expects( $this->once() )->method( 'select' )->with(
                'foo', [ 'bar' => 'baz' ]
            )->willReturn( [ 'foo' ] );
            $this->assertSame( 'foo', $connection->selectOne( 'foo', [ 'bar' => 'baz' ] ) );
        }

        private function getMockConnection() : WordpressConnection
        {

            return new WordpressConnection( $this->wpdb );

        }


    }
