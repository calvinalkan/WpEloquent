<?php


    namespace Tests\Integration;

    use Codeception\TestCase\WPTestCase;
    use WpEloquent\DependencyManager;
    use WpEloquent\DependentPlugin;

    class DependencyManagerTest extends WPTestCase
    {

        private $db_drop_in;

        private $stub_dir;

        private $plugin_a = 'plugin-a/vendor';

        private $plugin_b = 'plugin-b/vendor';

        private $plugin_c =  'plugin-c/vendor';

        private $dependency_manager;

        protected function setUp() : void
        {

            parent::setUp();

            $this->db_drop_in = getenv('WP_ROOT_FOLDER').'/wp-content/db.php';

            if (is_link($this->db_drop_in)) {

                $success = unlink($this->db_drop_in);

                self::assertTrue($success);

            }

            $this->stub_dir = getenv('PACKAGE_ROOT').'/tests/Stubs';

            $this->createTestPluginDirectory();

            $this->dependency_manager = new DependencyManager();

        }

        protected function tearDown() : void
        {

            parent::tearDown();

            if (is_link($this->db_drop_in)) {

                $this->unlink($this->db_drop_in);

            }

            self::assertFalse(is_link($this->db_drop_in));
            self::assertFalse(file_exists($this->db_drop_in));

            $this->deleteTestPluginDirectory();


        }


        /** @test */
        public function a_dependent_plugins_symlink_is_always_created_if_its_the_first_dependent_plugin()
        {


            $this->assertSymlinkNotSet();

            $this->dependency_manager->add($this->plugin_a);

            $this->assertSymlinkFor($this->plugin_a);

        }

        /** @test */
        public function if_a_dependent_plugin_is_added_its_marked_in_the_database()
        {

            $this->assertNotMarkedInstalled($this->plugin_a);

            $this->dependency_manager->add($this->plugin_a);

            $this->assertMarkedInstalled($this->plugin_a);


        }

        /** @test */
        public function symlink_and_db_entry_get_deleted_if_a_plugin_is_removed()
        {

            $this->dependency_manager->add($this->plugin_a);

            $this->dependency_manager->remove($this->plugin_a);

            $this->assertSymlinkNotSet();

            $this->assertNotMarkedInstalled($this->plugin_a);

        }

        /** @test */
        public function a_symlink_wont_be_created_again()
        {

            $this->dependency_manager->add($this->plugin_a);

            try {

                $this->dependency_manager->add($this->plugin_a);
                $this->assertTrue(true);

            }

            catch (\Exception $exception) {

                $this->fail($exception->getMessage());

            }


        }

        /** @test */
        public function a_db_entry_wont_be_duplicated()
        {

            $this->dependency_manager->add($this->plugin_a);

            $before = $this->dependency_manager->all();

            $this->dependency_manager->add($this->plugin_a);

            $after = $this->dependency_manager->all();

            $this->assertEquals($before, $after);

        }

        /** @test */
        public function when_several_compatible_dependents_are_present_and_one_gets_removed_the_symlink_is_swapped()
        {

            $this->dependency_manager->add($this->plugin_a);
            $this->dependency_manager->add($this->plugin_b);

            $this->assertMarkedInstalled($this->plugin_a);
            $this->assertMarkedInstalled($this->plugin_b);

            $this->assertSymlinkFor($this->plugin_b);

            $this->dependency_manager->remove($this->plugin_a);

            $this->assertSymlinkFor($this->plugin_b);

        }

        /** @test */
        public function if_no_dependent_is_active_anymore_symlink_and_db_settings_are_removed()
        {

            $this->dependency_manager->add($this->plugin_a);
            $this->dependency_manager->add($this->plugin_b);

            $this->dependency_manager->remove($this->plugin_a);
            $this->dependency_manager->remove($this->plugin_b);

            $this->assertSymlinkNotSet();
            $this->assertFalse(get_option('better-wp-db-dependents'));

        }

		/** @test */
	    public function when_a_compatible_plugin_with_a_higher_version_number_is_activated_the_symlink_gets_swapped( ) {


		    $this->dependency_manager->add($this->plugin_a);
		    $this->dependency_manager->add($this->plugin_b);

		    $this->assertMarkedInstalled($this->plugin_a);
		    $this->assertMarkedInstalled($this->plugin_b);

		    $this->assertSymlinkFor($this->plugin_b);


		}

		/** @test */
	    public function the_symlink_stays_the_same_when_a_compatible_plugin_with_lower_version_is_added() {


		    $this->dependency_manager->add($this->plugin_b);
		    $this->dependency_manager->add($this->plugin_c);
		    $this->dependency_manager->add($this->plugin_a);

		    $this->assertMarkedInstalled($this->plugin_a);
		    $this->assertMarkedInstalled($this->plugin_b);
		    $this->assertMarkedInstalled($this->plugin_c);

		    $this->assertSymlinkFor($this->plugin_b);


	    }

	    /** @test */
	    public function the_next_highest_symlink_gets_created_when_the_highest_dependent_gets_removed() {

		    $this->dependency_manager->add($this->plugin_b);
		    $this->dependency_manager->add($this->plugin_a);
		    $this->dependency_manager->add($this->plugin_c);

		    $this->assertMarkedInstalled($this->plugin_a);
		    $this->assertMarkedInstalled($this->plugin_b);
		    $this->assertMarkedInstalled($this->plugin_c);

		    $this->dependency_manager->remove($this->plugin_b);

		    $this->assertSymlinkFor($this->plugin_c);

		}




        private function assertSymlinkFor($plugin_id)
        {

            self::assertTrue(file_exists($this->db_drop_in),
                'The file: '.$this->db_drop_in.' doesnt exists.');
            self::assertTrue(is_link($this->db_drop_in),
                'The file: '.$this->db_drop_in.' is not a symlink.');

            self::assertSame((new DependentPlugin($plugin_id))->dbDropInPath(), readlink($this->db_drop_in));


        }

        private function assertSymlinkNotSet()
        {

            self::assertFalse(file_exists($this->db_drop_in));
            self::assertFalse(is_link($this->db_drop_in));

        }

        private function createTestPluginDirectory()
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

        private function deleteTestPluginDirectory()
        {

            $unlinkedA = unlink(WP_CONTENT_DIR.DIRECTORY_SEPARATOR.'plugins'.DIRECTORY_SEPARATOR.'plugin-a');
            $unlinkedB = unlink(WP_CONTENT_DIR.DIRECTORY_SEPARATOR.'plugins'.DIRECTORY_SEPARATOR.'plugin-b');

            self::assertTrue($unlinkedA);
            self::assertTrue($unlinkedB);

        }

        private function assertMarkedInstalled($plugin_id)
        {

            $this->assertContains($plugin_id, $this->dependency_manager->all());

        }

        private function assertNotMarkedInstalled($plugin_id)
        {

            $this->assertNotContains($plugin_id, $this->dependency_manager->all());

        }


    }
