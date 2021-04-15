<?php


    namespace WpEloquent;

    use Composer\Semver\Comparator;
    use Composer\Semver\Semver;


    class CompatibilityManager
    {

        /**
         * @var array
         */
        private $existing_plugins;

        /**
         * @var array
         */
        private $versions;


        /** @var PluginFile */
        private $plugin_file;


        /**
         * CompatibilityManager constructor.
         *
         * Accepts an array very each element is
         * of the typeDependentPlugin.
         *
         *
         * @param  array  $existing_plugins
         */
        public function __construct(array $existing_plugins = [], PluginFile $plugin_file = null)
        {

            $this->existing_plugins = $existing_plugins;
            $this->plugin_file = $plugin_file ?? new PluginFile();
            $this->versions = $this->parseVersions();
        }

        public function checkFor(DependentPlugin $plugin)
        {

            $version = $this->getRequiredVersion($plugin);

            if ( ! count($this->versions)) {

                return true;

            }

            $this->compareMinimum($version);
            $this->compareMaximum($version);

        }

        private function getRequiredVersion($plugin)
        {


            $composer_config = $this->plugin_file->getComposerPackages($plugin);

            $packages = collect($composer_config['packages']);

            $version = collect(
                $packages->firstWhere('name', 'calvinalkan/wp-eloquent')
            )->only('version')->first();

            if ( ! $version) {

                throw new ConfigurationException(
                    'A composer.lock file was found but the required version number could not be parsed.'
                );

            }

            return $version;


        }

        private function parseVersions()
        {

            $versions = array_map(function (DependentPlugin $plugin) {

                return $this->getRequiredVersion($plugin);

            }, $this->existing_plugins);

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

                    'Your Plugin relies on version: '.$version.'. The minimum version required in this WP-Install is '.$minimum

                );

            }


        }

        private function compareMaximum($version)
        {

            $highest_used = end($this->versions);

            $major = '^'.$highest_used;

            $compatible = Semver::satisfies($version, $major);

            if ( ! $compatible) {

                throw new CompatibilityException(

                    'Your Plugin relies on version: '.$version.'. The maximum version compatible with this WP-Install is '.$highest_used

                );

            }


        }

    }