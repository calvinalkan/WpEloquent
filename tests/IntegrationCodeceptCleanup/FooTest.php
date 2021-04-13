<?php


    namespace Tests\IntegrationCodeceptCleanup;

    use Codeception\Test\Unit as CodeceptUnit;

    class FooTest extends CodeceptUnit
    {

        /** @test */
        public function testFoo()
        {

            self::assertSame('foo', 'foo');

        }


    }