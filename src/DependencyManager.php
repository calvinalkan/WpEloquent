<?php


	namespace WpEloquent;

	class DependencyManager {


		/**
		 * @var array
		 */
		private $dependents;
		/**
		 * @var CompatibilityManager
		 */
		private $compatibility_manager;

		public function __construct( CompatibilityManager $compatibility_manager = null ) {

			$this->dependents = $this->all();

			$this->compatibility_manager = $compatibility_manager ?? new CompatibilityManager( $this->buildPlugins() );

		}

		public function add( string $plugin_id ) {

			if ( $this->alreadyAdded( $plugin_id ) ) {

				return;

			}

			$plugin = new DependentPlugin( $plugin_id );

			$this->compatibility_manager->isCompatible( $plugin );

			$this->markInDatabase( $plugin );

			$this->symlink( $this->compatibility_manager->newOptimalVersion() );


		}

		public function all() {

			return get_option('better-wp-db-dependents', []);


		}

		public function remove( string $plugin_id ) {

			if ( is_int( $index = $this->index( $plugin_id ) ) ) {

				$this->removeDependent( $index )
				     ->replaceWith( $this->compatibility_manager->newOptimalVersion() )
				     ->refresh();

			}

		}

		private function index( $plugin_id ) {

			return array_search( $plugin_id, $this->dependents, true );

		}

		private function symlink( DependentPlugin $plugin ) {

			( new Symlink() )->createFor( $plugin );

		}

		private function markInDatabase( DependentPlugin $plugin ) {

			$this->dependents[] = $plugin->getId();

			update_option( 'better-wp-db-dependents', $this->dependents );

		}

		private function removeDependent( $index ) : DependencyManager {

			$plugin = new DependentPlugin( $this->dependents[ $index ] );

			unset( $this->dependents[ $index ] );

			( new Symlink() )->removeFor( $plugin );

			$this->compatibility_manager->forget( $plugin );

			return $this;

		}

		private function refresh() {

			$this->dependents = array_values( $this->dependents );

			if ( count( $this->dependents ) ) {

				update_option( 'better-wp-db-dependents', $this->dependents );

				return;
			}

			delete_option( 'better-wp-db-dependents' );


		}

		private function replaceWith( $plugin ) {

			if ( $plugin ) {

				( new Symlink() )->createFor( $plugin );

			}

			return $this;

		}

		private function buildPlugins() {

			$plugins = array_map( function ( $plugin_id ) {

				return new DependentPlugin( $plugin_id );

			}, $this->dependents );

			return $plugins;

		}

		/**
		 * @param $plugin_id
		 *
		 * @return bool
		 */
		private function alreadyAdded( $plugin_id ) : bool {

			return is_int( $this->index( $plugin_id ) );
		}

	}