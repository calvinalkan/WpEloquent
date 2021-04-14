<?php


    namespace Tests\Integration;

    use Codeception\TestCase\WPTestCase;
    use WpEloquent\Symlinker;

    class SymlinkerTest extends WPTestCase
    {

        /**
         * @var \UnitTester
         */
        protected $tester;

        private $db_drop_in;

        private $target_link;

        private $stub_dir;

        /**
         * @var Symlinker
         */
        private $pluginA;
        /**
         * @var Symlinker
         */
        private $pluginB;

        protected function setUp() : void
        {

            $this->db_drop_in = getenv('WP_ROOT_FOLDER').'/wp-content/db.php';

            if (is_link($this->db_drop_in)) {

                $success = unlink($this->db_drop_in);

                self::assertTrue($success);

            }

            $this->target_link = getenv('PACKAGE_ROOT').'/src/ExtendsWpdb/drop-in.php';

            $this->stub_dir = getenv('PACKAGE_ROOT').'/tests/Stubs';

            $this->createTestSymlinks();

            $this->pluginA = new Symlinker('plugin-a/vendor');
            $this->pluginB = new Symlinker('plugin-b/vendor');

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

            $this->deleteTestSymlinks();


        }


        /** @test */
        public function a_symlink_gets_created_when_a_dependent_plugin_gets_activated_and_no_previous_db_drop_in_exists()
        {

            self::assertFalse(is_link($this->db_drop_in));
            self::assertFalse(file_exists($this->db_drop_in));

            $this->activatePluginA();

            $this->assertSymlinkSet('plugin-a/vendor');


        }

        /** @test */
        public function if_a_symlink_is_created_successfully_an_option_is_stored_in_the_db()
        {

            self::assertFalse(is_link($this->db_drop_in));
            self::assertFalse(file_exists($this->db_drop_in));

            $this->activatePluginA();

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

                $this->activatePluginA();
                $this->fail('No Exception was thrown when one was expected');
            }

            catch (\Exception $e) {

                self::assertSame('The database drop-in is already symlinked to the file: '.__FILE__, $e->getMessage());

                self::assertSame(false, get_option('better-wp-db-symlink-created'));


            }


        }

        /** @test */
        public function the_symlinker_wont_run_if_the_installed_option_is_set_in_the_db()
        {

            update_option('better-wp-db-symlink-created', true);

            $this->activatePluginA();

            self::assertFalse(is_link($this->db_drop_in));
            self::assertFalse(file_exists($this->db_drop_in));


        }

        /** @test */
        public function a_new_dependent_is_always_added_no_matter_what()
        {

            update_option('better-wp-db-symlink-created', true);

            $this->activatePluginA();
            $this->activatePluginB();

            $dependents = get_option('better-wp-db-dependents');

            $this->assertArrayHasKey('plugin-a/vendor', $dependents);
            $this->assertArrayHasKey('plugin-b/vendor', $dependents);


        }


        /** @test */
        public function symlink_and_db_option_get_deleted_when_the_destroy_method_gets_called()
        {

            $this->activatePluginA();

            $this->assertSymlinkSet('plugin-a/vendor');

            $this->deactivatePluginA();

            $this->assertSymlinkNotSet();

            self::assertFalse(get_option('better-wp-db-symlink-created'));

        }


        /** @test */
        public function if_no_dependent_is_active_anymore_symlink_and_db_settings_are_removed()
        {

            $this->activatePluginA();
            $this->activatePluginB();

            $this->deactivatePluginA();
            $this->deactivatePluginB();

            $this->assertSymlinkNotSet();
            self::assertFalse(get_option('better-wp-db-symlink-created'));

        }

        /** @test */
        public function the_symlink_gets_changed_to_another_plugins_vendor_folder_when_a_plugin_gets_deactivated()
        {


            $this->activatePluginA();
            $this->activatePluginB();

            $this->assertSymlinkSet('plugin-a/vendor');
            $this->assertTrue(get_option('better-wp-db-symlink-created'));

            $this->deactivatePluginA();

            $this->assertSymlinkSet('plugin-b/vendor');
            $this->assertTrue(get_option('better-wp-db-symlink-created'));


        }


        private function assertSymlinkSet($plugin_vendor_path = null)
        {

            self::assertTrue(file_exists($this->db_drop_in),
                'The file: '.$this->db_drop_in.' doesnt exists.');
            self::assertTrue(is_link($this->db_drop_in),
                'The file: '.$this->db_drop_in.' is not a symlink.');

            $symlink = $plugin_vendor_path
                ? $this->stub_dir.DIRECTORY_SEPARATOR.$plugin_vendor_path.'/calvinalkan/wp-eloquent/src/ExtendsWpDb/drop-in.php'
                : $this->target_link;

            self::assertSame($symlink, readlink($this->db_drop_in));


        }

        private function assertSymlinkNotSet()
        {

            self::assertFalse(file_exists($this->db_drop_in));
            self::assertFalse(is_link($this->db_drop_in));

        }

        private function activatePluginA()
        {

            $this->pluginA->create();

        }

        private function activatePluginB()
        {

            $this->pluginB->create();

        }

        private function deactivatePluginA()
        {

            $this->pluginA->destroy();

        }

        private function deactivatePluginB()
        {

            $this->pluginB->destroy();

        }

        private function activeUnsupportedPlugin()
        {

            $success = symlink(__FILE__, $this->db_drop_in);

            self::assertTrue($success);

        }

        private function activateQueryMonitor()
        {

            update_option('active_plugins', ['query-monitor/query-monitor.php']);

        }

        private function createTestSymlinks()
        {

            if ( ! is_link(WP_CONTENT_DIR.DIRECTORY_SEPARATOR.'plugins'.DIRECTORY_SEPARATOR.'plugin-a')) {

                $pluginA_symlinked = symlink(
                    $this->stub_dir.DIRECTORY_SEPARATOR.'plugin-a',
                    WP_CONTENT_DIR.DIRECTORY_SEPARATOR.'plugins'.DIRECTORY_SEPARATOR.'plugin-a'

                );

                self::assertTrue($pluginA_symlinked);

            }

            if ( ! is_link(WP_CONTENT_DIR.DIRECTORY_SEPARATOR.'plugins'.DIRECTORY_SEPARATOR.'plugin-b')) {

                $pluginB_symlinked = symlink(
                    $this->stub_dir.DIRECTORY_SEPARATOR.'plugin-b',
                    WP_CONTENT_DIR.DIRECTORY_SEPARATOR.'plugins'.DIRECTORY_SEPARATOR.'plugin-b'

                );

                self::assertTrue($pluginB_symlinked);
            }


        }

        private function deleteTestSymlinks()
        {

            $unlinkedA = unlink(WP_CONTENT_DIR.DIRECTORY_SEPARATOR.'plugins'.DIRECTORY_SEPARATOR.'plugin-a');
            $unlinkedB = unlink(WP_CONTENT_DIR.DIRECTORY_SEPARATOR.'plugins'.DIRECTORY_SEPARATOR.'plugin-b');

            self::assertTrue($unlinkedA);
            self::assertTrue($unlinkedB);

        }


    }
