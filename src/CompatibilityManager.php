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


        /**
         * CompatibilityManager constructor.
         *
         * Accepts an array very each element is
         * of the typeDependentPlugin.
         *
         *
         * @param  array  $existing_plugins
         */
        public function __construct( array $existing_plugins = [] )
        {

            $this->existing_plugins = $existing_plugins;
            $this->versions = $this->parseVersions();
            $this->version_storage = $this->versionStorage();

        }

	    /**
	     * @param  DependentPlugin  $plugin
	     *
	     * @return bool
	     * @throws CompatibilityException
	     * @throws ConfigurationException
	     */
	    public function isCompatible( DependentPlugin $plugin) : bool {

            $version = $plugin->requiredVersion();

            if ( ! count($this->versions)) {

	            $this->version_storage[$version] = $plugin;
                return true;

            }

            $this->compareMinimum($version);
            $this->compareMaximum($version);

            $this->version_storage[$version] = $plugin;

            return true;


        }

	    public function requiredVersions() : array
	    {

		    return $this->versions;

	    }

	    public function newOptimalVersion() {

	    	if ( count($this->version_storage)) {

			    $high_to_low = Semver::rsort(array_keys($this->version_storage));

			    return $this->version_storage[$high_to_low[0]];

		    }

	    }

	    public function forget ( DependentPlugin $plugin ) {

	    	if ( $key = array_search($plugin, $this->version_storage)) {

	    		unset($this->version_storage[$key]);

		    }

	    }

        private function parseVersions()
        {

            $versions = array_map(function (DependentPlugin $plugin) {

                return $plugin->requiredVersion();

            }, $this->existing_plugins);

            return $this->sortLowToHigh($versions);

        }

        private function sortLowToHigh (array $versions ) {

	        return Semver::sort($versions);

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

	    private function versionStorage() : array {

        	$storage = collect($this->existing_plugins)->flatMap(function (DependentPlugin $plugin) {

        		return [ $plugin->requiredVersion() =>  $plugin];

	        });

        	return $storage->toArray();

	    }



    }