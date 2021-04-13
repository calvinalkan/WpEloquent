<?php


    namespace Tests\Integration;

    use Codeception\TestCase\WPTestCase;


    class BetterWpDbTest extends WPTestCase
    {


        protected function tearDown() : void
        {

            parent::tearDown();

        }


        /** @test */
        public function foobar (){

            $foo = 'bar';

        }


    }
