<?php


    namespace WpEloquent;

    use Composer\Semver\Comparator;
    use Composer\Semver\Semver;
    use Exception;
    use Symfony\Component\Finder\Finder;

    class CompatibilityManager
    {

        /**
         * @var array
         */
        private $plugins;


        /**
         * @var array
         */
        private $versions;


        /**
         * CompatibilityManager constructor.
         *
         * Accepts an array very each element is
         * of the typeDependentPlugin.
         *
         *
         * @param  array  $plugins
         */
        public function __construct(array $plugins = [])
        {

            $this->plugins = $plugins;
            $this->versions = $this->parseVersions();
        }

        public function checkFor(DependentPlugin $plugin)
        {

            $composer = $this->getComposerFile($plugin);

            $version = $this->getRequiredVersion($composer);

            if ( ! count($this->versions)) {

                return true;

            }

            $this->compareMinimum($version);
            $this->compareMaximum($version);

        }

        private function getComposerFile(DependentPlugin $plugin)
        {

            $finder = new Finder();

            $root_dir = dirname($plugin->vendorDir(), 1);

            $finder
                ->followLinks()
                ->files()
                ->in($root_dir)
                ->depth('< 1')
                ->name('composer.lock');

            try {

                $composer_json_path = iterator_to_array($finder, false)[0];

                return $composer_json_path->getRealPath();

            }
            catch (\Throwable $e) {

                throw new ConfigurationException('Unable to find a composer.lock file inside the directory: '.$root_dir);

            }


        }

        private function getRequiredVersion($path)
        {

            try {

                $composer_config = json_decode(file_get_contents($path), true);

                $packages = collect($composer_config['packages']);

                $version = collect(
                    $packages->firstWhere('name', 'calvinalkan/wp-eloquent')
                )->only('version')->first();

                if ( ! $version) {

                    throw new \Exception;

                }

                return $version;

            }
            catch (Exception $e) {

                throw new ConfigurationException('A composer.lock file was found but the required version number could not be parsed.');

            }

        }

        private function parseVersions()
        {

            $versions = array_map(function (DependentPlugin $plugin) {

                return $this->getRequiredVersion($this->getComposerFile($plugin));

            }, $this->plugins);

            return Semver::sort($versions);

        }

        public function requiredVersions() : array
        {

            return $this->versions;

        }

        private function compareMinimum($version)
        {


            $minimum = $this->versions[0];

            $compatible = Comparator::greaterThanOrEqualTo($version, $minimum);

            if ( ! $compatible) {

                throw new CompatibilityException(

                    'Your Plugin relies on version: ' . $version .'. The minimum version required in this WP-Install is ' . $minimum

                );

            }


        }

        private function compareMaximum($version)
        {

            $highest_used = end($this->versions);

            $major = '^'. $highest_used;

            $compatible = Semver::satisfies($version, $major);

            if ( ! $compatible) {

                throw new CompatibilityException(

                    'Your Plugin relies on version: ' . $version .'. The maximum version compatible with this WP-Install is ' . $highest_used

                );

            }


        }

    }