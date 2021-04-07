<?php


    namespace Tests\unit;

    use WpEloquent\Resolver;
    use PHPUnit\Framework\TestCase;
    use Mockery as m;

    class ResolverTest extends TestCase
    {


        /** @test */
        public function the_resolver_always_returns_a_singleton_connection()
        {

            $wpdb1 = m::mock(\wpdb::class);
            $wpdb2 = m::mock(\wpdb::class);

            $resolver1 = new Resolver($wpdb1);
            $resolver2 = new Resolver($wpdb2);

            $instance1 = $resolver1->connection();
            $instance2 = $resolver2->connection();

            self::assertSame($instance1, $instance2);

        }


    }
