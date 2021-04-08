<?php


    namespace Tests\unit;

    use WpEloquent\Resolver;
    use PHPUnit\Framework\TestCase;
    use Mockery as m;

    class ResolverUnitTest extends TestCase
    {


        /** @test */
        public function the_resolver_always_returns_a_singleton_connection()
        {

            $wpdb1 = m::mock(\wpdb::class);
            $wpdb2 = m::mock(\wpdb::class);

            $wpdb1->prefix = 'wp_';
            $wpdb2->prefix = 'wp_';

            $resolver1 = new Resolver($wpdb1);
            $resolver2 = new Resolver($wpdb2);

            $instance1 = $resolver1->connection();
            $instance2 = $resolver2->connection();

            self::assertSame($instance1, $instance2);

        }


        /** @test */
        public function the_default_connection_name_can_be_retrieved () {

            $wpdb1 = m::mock(\wpdb::class);
            $wpdb1->prefix = 'wp_';


            $resolver1 = new Resolver($wpdb1);

            self::assertSame('wp-eloquent', $resolver1->getDefaultConnection());

        }

        /** @test */
        public function the_default_connection_name_can_be_set () {

            $wpdb1 = m::mock(\wpdb::class);
            $wpdb1->prefix = 'wp_';


            $resolver1 = new Resolver($wpdb1);

            $resolver1->setDefaultConnection('new_connection');

            self::assertSame('new_connection', $resolver1->getDefaultConnection());

        }


    }
