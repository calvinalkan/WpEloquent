<?php


    namespace WpEloquent;

    use Composer\Semver\Semver;


    class CompatibilityManager
    {

        /**
         * @var array
         */
        private $versions;

	    /**
	     * @var array
	     */
	    private $stored_plugins;


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

            $this->versions       = $this->parseVersions($existing_plugins);
            $this->stored_plugins = $this->parseStoredPlugins($existing_plugins);

        }

	    /**
	     * @param  DependentPlugin  $plugin
	     *
	     * @return bool
	     * @throws CompatibilityException
	     * @throws ConfigurationException
	     */
	    public function isCompatible( DependentPlugin $plugin ) : bool {

            $version = $plugin->requiredVersion();

            if ( ! count($this->stored_plugins)) {

                return true;

            }

		    [$compatible, $minimum_version] = $this->compare($version);

            if ( ! $compatible ) {

            	throw new CompatibilityException( $version, $minimum_version );

            }

            return true;


        }

	    public function optimalVersionWith( DependentPlugin $plugin = null  ) : ?DependentPlugin {

	    	$merge = ($plugin) ?  [ $plugin->requiredVersion() =>  $plugin] : [];

	    	$versions = array_merge( $this->stored_plugins, $merge );

	    	if ( count($versions)) {

			    $high_to_low = Semver::rsort(array_keys($versions));

			    return $versions[$high_to_low[0]];

		    }

	    	return $plugin;

	    }

	    public function forget ( DependentPlugin $plugin ) {

	    	if ( $key = array_search($plugin, $this->stored_plugins)) {

	    		unset($this->stored_plugins[$key]);

		    }

	    }

        private function parseVersions( array $plugins )
        {

            $versions = array_map(function (DependentPlugin $plugin) {

                return $plugin->requiredVersion();

            }, $plugins);

            return $this->sortLowToHigh($versions);

        }

	    private function parseStoredPlugins(array $plugins ) : array {

        	$storage = collect($plugins)->flatMap(function (DependentPlugin $plugin) {

        		return [ $plugin->requiredVersion() =>  $plugin];

	        });

        	return $storage->toArray();

	    }

	    private function sortLowToHigh (array $versions ) {

		    return Semver::sort($versions);

	    }

	    private function compare (string $version) : array {

		    $minimum_version = '^' . $this->versions[0];

		    $compatible = Semver::satisfies($version, $minimum_version);

		    return [$compatible, $minimum_version];

	    }


    }