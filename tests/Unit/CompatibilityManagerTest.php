<?php


    namespace Tests\Unit;

    use Codeception\Test\Unit as CodeceptUnit;
    use Illuminate\Support\Arr;
    use Illuminate\Support\Str;
    use WpEloquent\CompatibilityException;
    use WpEloquent\CompatibilityManager;
    use WpEloquent\ConfigurationException;
    use WpEloquent\DependentPlugin;

    class CompatibilityManagerTest extends CodeceptUnit
    {

        private $plugin_a = 'plugin-a/vendor';
        private $plugin_b = 'plugin-b/vendor';
        private $plugin_c = 'plugin-c/vendor';
        private $plugin_major = 'plugin-major-version/vendor';

        private $plugins;

        private $plugin_without_composer = 'plugin-without-composer/includes';

        private $plugin_without_specified_dependency = 'plugin-without-specified-dependency/vendor';

        private $stub_dir;

        private $wp_content_dir;

        protected function setUp() : void
        {

            parent::setUp();

            $this->stub_dir = getenv('PACKAGE_ROOT').'/tests/Stubs';
            $this->wp_content_dir = getenv('WP_ROOT_FOLDER').'/wp-content';

            if ( ! defined('WP_CONTENT_DIR')) {

                define('WP_CONTENT_DIR', $this->wp_content_dir);

            }

            $this->plugins = [
                $this->plugin_a,
                $this->plugin_b,
                $this->plugin_c,
                $this->plugin_without_composer,
                $this->plugin_without_specified_dependency,
                $this->plugin_major
            ];

            $this->createTestPluginDirectory();


        }

        /** @test */
        public function an_exception_gets_thrown_when_the_composer_lock_file_cant_be_parsed()
        {

            try {

                new CompatibilityManager([new DependentPlugin($this->plugin_without_composer)]);

                $this->fail('Exception not thrown but was expected');

            }
            catch (ConfigurationException $e) {

                $this->assertStringContainsString('Unable to find a composer.lock file inside the directory:', $e->getMessage());

            }


        }

        /** @test */
        public function an_exception_gets_thrown_when_the_composer_lock_file_is_found_but_doesnt_contain_a_version_number()
        {


            try {

                new CompatibilityManager([new DependentPlugin($this->plugin_without_specified_dependency)]);

                $this->fail('Exception not thrown but was expected');

            }
            catch (ConfigurationException $e) {

                $this->assertStringContainsString('A composer.lock file was found but the required version number could not be parsed.', $e->getMessage());

            }

        }

        /** @test */
        public function versions_get_parsed_correctly()
        {

            $manager = $this->newCompatibilityManager([$this->plugin_a, $this->plugin_b]);

            $this->assertSame(['0.2.0', '0.2.4'], $manager->requiredVersions());


        }

        /** @test */
        public function a_new_instance_can_be_created_without_any_dependents()
        {

            try {

                new CompatibilityManager();

                $this->assertTrue(true);

            }

            catch (\Throwable $e) {

                $this->fail($e->getMessage());

            }

        }

        /** @test */
        public function a_higher_version_can_always_be_added_if_it_doesnt_break_sem_versioning()
        {

            $manager = $this->newCompatibilityManager([$this->plugin_c, $this->plugin_a]);

            try {

                $compatible = $manager->isCompatible(new DependentPlugin($this->plugin_b));

                $this->assertTrue($compatible);

            }catch (\Exception $e) {

                $this->fail($e->getMessage());

            }


        }

        /** @test */
        public function a_lower_version_number_cant_be_added () {


            try {

                $manager = $this->newCompatibilityManager([$this->plugin_b, $this->plugin_c]);

                $manager->isCompatible(new DependentPlugin($this->plugin_a));

                $this->fail('Lower version number was added.');

            } catch (CompatibilityException $e) {

                $this->assertStringContainsString(
                    'Your Plugin relies on version: 0.2.0. The minimum version required in this WP-Install is 0.2.2',
                    $e->getMessage()
                );

            }



        }

        /** @test */
        public function a_new_major_version_cant_be_added()
        {

            try {

                $manager = $this->newCompatibilityManager([$this->plugin_a, $this->plugin_b, $this->plugin_c]);

                $manager->isCompatible(new DependentPlugin($this->plugin_major));

                $this->fail('Major version number was added.');

            } catch (CompatibilityException $e) {

                $this->assertStringContainsString(
                    'Your Plugin relies on version: 1.2.2. The maximum version compatible with this WP-Install is 0.2.4',
                    $e->getMessage()
                );

            }

        }



        private function createTestPluginDirectory()
        {


            array_walk($this->plugins, function ($plugin) {

                $dir = Str::before($plugin, '/');

                if ( ! is_link($this->wp_content_dir.DIRECTORY_SEPARATOR.'plugins'.DIRECTORY_SEPARATOR. $dir)) {

                    $symlinked = symlink(
                        $this->stub_dir.DIRECTORY_SEPARATOR.$dir,
                        $this->wp_content_dir.DIRECTORY_SEPARATOR.'plugins'.DIRECTORY_SEPARATOR.$dir

                    );

                   $this->assertTrue($symlinked, 'Plugin:' . $dir . ' was not symlinked');

                }


            });


        }

        private function newCompatibilityManager($plugins = []) : CompatibilityManager {

            $plugins = Arr::wrap($plugins);

            $dependents = array_map(function ($plugin) {

                return new DependentPlugin($plugin);

            }, $plugins);

            return new CompatibilityManager($dependents);

        }

    }
