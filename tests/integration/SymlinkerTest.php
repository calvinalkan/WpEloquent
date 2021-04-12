<?php


    namespace Tests\integration;

    use Codeception\TestCase\WPTestCase;
    use WpEloquent\ExtendsWpDb\BetterWpDb;
    use WpEloquent\ExtendsWpDb\BetterWpDbQM;
    use WpEloquent\Symlinker;



    class SymlinkerTest extends WPTestCase
    {

        /**
         * @var \UnitTester
         */
        protected $tester;

        private $db_drop_in;

        protected function setUp() : void
        {

            $this->db_drop_in = getenv('WP_ROOT_FOLDER').'/wp-content/db.php';

            if (is_link($this->db_drop_in)) {

                $success = unlink($this->db_drop_in);

                self::assertTrue($success);
            }

            parent::setUp();


        }

        protected function tearDown() : void
        {

            parent::tearDown();

            if (is_link($this->db_drop_in)) {

                $this->unlink($this->db_drop_in);

            }

            self::assertFalse(is_link($this->db_drop_in));
            self::assertFalse(file_exists($this->db_drop_in));


        }

        /** @test */
        public function a_symlink_gets_created_when_wp_loads_and_no_previous_db_drop_in_exists()
        {

            self::assertFalse(is_link($this->db_drop_in));
            self::assertFalse(file_exists($this->db_drop_in));

            $this->activatePlugin1();

            self::assertTrue(is_link($this->db_drop_in));
            self::assertTrue(file_exists($this->db_drop_in));

            $reflector = new \ReflectionClass(BetterWpDb::class);
            $is_symlink_to = $reflector->getFileName();

            self::assertSame($is_symlink_to, readlink($this->db_drop_in));

        }

        /** @test */
        public function a_symlink_gets_created_to_the_qm_drop_in_extension_if_qm_is_active()
        {

            $qm_db = __DIR__.'/Stubs/query-monitor/wp-content/db.php';

            $this->activateQueryMonitor();

            self::assertTrue(is_link($this->db_drop_in));
            self::assertTrue(file_exists($this->db_drop_in));
            self::assertSame($qm_db, readlink($this->db_drop_in));

            $this->activatePlugin1();

            self::assertTrue(is_link($this->db_drop_in));
            self::assertTrue(file_exists($this->db_drop_in));

            $reflector = new \ReflectionClass(BetterWpDbQM::class);
            $is_symlink_to = $reflector->getFileName();
            self::assertSame($is_symlink_to, readlink($this->db_drop_in));

        }

        /** @test */
        public function if_a_symlink_is_created_successfully_an_option_is_stored_in_the_db()
        {

            self::assertFalse(is_link($this->db_drop_in));
            self::assertFalse(file_exists($this->db_drop_in));

            $this->activatePlugin1();

            self::assertSame(true, get_option('better-wp-db-symlink-created'));


        }

        /** @test */
        public function if_the_symlink_could_not_be_set_its_marked_in_the_database_and_an_exception_is_thrown()
        {

            $this->activeUnsupportedPlugin();

            self::assertTrue(is_link($this->db_drop_in));
            self::assertTrue(file_exists($this->db_drop_in));

            $this->tester->dontSeeOptionInDatabase('better-wp-db-symlink-created');

            try {

                $this->activatePlugin1();
                $this->fail('No Exception was thrown when one was expected');
            }

            catch (\Exception $e) {

                self::assertSame('The database drop-in is already symlinked to the file: '.__FILE__,
                    $e->getMessage());

                self::assertSame(false, get_option('better-wp-db-symlink-created'));


            }


        }

        /** @test */
        public function the_symlinker_wont_run_again_if_the_installed_option_is_set_in_the_db()
        {

            update_option('better-wp-db-symlink-created', true);

            $this->activatePlugin1();

            self::assertFalse(is_link($this->db_drop_in));
            self::assertFalse(file_exists($this->db_drop_in));


        }

        /** @test */
        public function symlink_and_db_option_get_deleted_when_the_destroy_method_gets_called()
        {

            $this->activatePlugin1();

            $this->assertSymlinkSet();

            $this->deactivatePlugin1();

            $this->assertSymlinkNotSet();

            self::assertFalse(get_option('better-wp-db-symlink-created'));

        }

        /** @test */
        public function the_symlink_wont_get_removed_if_there_is_another_plugin_relying_on_it()
        {

            $this->activatePlugin1();
            $this->activatePlugin2();

            $this->deactivatePlugin1();

            $this->assertSymlinkSet();


            self::assertTrue(get_option('better-wp-db-symlink-created'));


        }

        /** @test */
        public function if_no_dependent_is_active_anymore_symlink_and_db_settings_are_removed()
        {

            $this->activatePlugin1();
            $this->activatePlugin2();

            $this->assertSymlinkSet();
            self::assertTrue(get_option('better-wp-db-symlink-created'));

            $this->deactivatePlugin1();
            $this->deactivatePlugin2();

            $this->assertSymlinkNotSet();
            self::assertFalse(get_option('better-wp-db-symlink-created'));

        }



        private function assertSymlinkSet()
        {

            self::assertTrue(file_exists($this->db_drop_in),
                'The file: '.$this->db_drop_in.' doesnt exists.');
            self::assertTrue(is_link($this->db_drop_in),
                'The file: '.$this->db_drop_in.' is not a symlink.');

        }

        private function assertSymlinkNotSet()
        {

            self::assertFalse(file_exists($this->db_drop_in));
            self::assertFalse(is_link($this->db_drop_in));

        }

        private function activatePlugin1()
        {

            Symlinker::create('plugin1');

        }

        private function activatePlugin2()
        {

            Symlinker::create('plugin2');

        }

        private function deactivatePlugin1()
        {

            Symlinker::destroy('plugin1');

        }

        private function deactivatePlugin2()
        {

            Symlinker::destroy('plugin2');

        }

        private function activeUnsupportedPlugin()
        {

            $success = symlink(__FILE__, $this->db_drop_in);

            self::assertTrue($success);

        }

        private function activateQueryMonitor()
        {

            $db = WP_CONTENT_DIR.'/db.php';

            $target = __DIR__.'/Stubs/query-monitor/wp-content/db.php';

            symlink($target, $db);

            update_option('active_plugins', ['query-monitor/query-monitor.php']);


        }


    }
