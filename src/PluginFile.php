<?php


	namespace WpEloquent;

	use Symfony\Component\Finder\Finder;

	class PluginFile {

		private function getComposerFile( DependentPlugin $plugin ) {

			$finder = new Finder();

			$root_dir = dirname( $plugin->vendorDir(), 1 );

			$finder
				->followLinks()
				->files()
				->in( $root_dir )
				->depth( '< 1' )
				->name( 'composer.lock' );

			try {

				$composer_json_path = iterator_to_array( $finder, false )[0];

				return $composer_json_path->getRealPath();

			}
			catch ( \Throwable $e ) {

				throw new ConfigurationException( 'Unable to find a composer.lock file inside the directory: ' . $root_dir );

			}


		}

		public function getComposerPackages( DependentPlugin $plugin ) {

			return json_decode(
				file_get_contents( $this->getComposerFile( $plugin ) ),
				true
			);

		}

		public function dbDropInPath( $plugin_id ) {

			$folder = WP_CONTENT_DIR . DIRECTORY_SEPARATOR . 'plugins' . DIRECTORY_SEPARATOR . $plugin_id;

			$finder = new Finder();

			$finder->ignoreUnreadableDirs()
			       ->files()
			       ->followLinks()
			       ->in( $folder . '/calvinalkan/better-wpdb/src/*' )
			       ->exclude( 'Traits' )
			       ->name( DependentPlugin::drop_in_file_name );

			$drop_in_path = iterator_to_array( $finder, false )[0];

			return $drop_in_path->getRealPath();

		}


	}