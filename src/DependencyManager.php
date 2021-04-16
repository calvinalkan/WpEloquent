<?php


	namespace WpEloquent;

	class DependencyManager {

		public const option_name = 'better-wp-db-dependents';

		/**
		 * @var array
		 */
		private $dependents;

		/**
		 * @var CompatibilityManager
		 */
		private $compatibility_manager;

		public function __construct( CompatibilityManager $compatibility_manager = null ) {

			$this->dependents = $this->getFreshFromDatabase();

			$this->compatibility_manager = $compatibility_manager ?? new CompatibilityManager(

				array_values($this->dependents)

				);

		}

		public function add( string $plugin_id ) {

			if ( $this->alreadyAdded( $plugin_id ) ) {

				return;

			}

			$plugin = new DependentPlugin( $plugin_id );

			$this->compatibility_manager->isCompatible( $plugin );

			$this->markInDatabase( $plugin );
			
			$this->symlink( $this->compatibility_manager->optimalVersionWith($plugin) );
			
		}

		public function getFreshFromDatabase() : array {

			$dependent_ids =  collect(get_option( 'better-wp-db-dependents', [] ));

			return $dependent_ids->flatMap(function ($dependent_id) {

				return [$dependent_id => new DependentPlugin($dependent_id)];

			})->toArray();


		}

		public function remove( string $plugin_id ) {

			if ( isset( $this->dependents[ $plugin_id ] ) ) {

				$plugin = $this->dependents[ $plugin_id ];

				$this->removeDependent( $plugin )
				     ->replaceWith( $this->compatibility_manager->optimalVersionWith(null) )
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

			$this->dependents[$plugin->getId()] = $plugin->getId();

			// Only store the keys, not the in memory objects.
			update_option( 'better-wp-db-dependents', array_keys($this->dependents) );

		}

		private function removeDependent( DependentPlugin $plugin ) : DependencyManager {


			unset( $this->dependents[ $plugin->getId() ] );

			( new Symlink() )->removeFor( $plugin );

			$this->compatibility_manager->forget($plugin);

			return $this;

		}

		private function refresh() {


			if ( count( $this->dependents ) ) {

				update_option( 'better-wp-db-dependents', array_keys($this->dependents));

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


		/**
		 * @param $plugin_id
		 *
		 * @return bool
		 */
		private function alreadyAdded( $plugin_id ) : bool {

			return is_int( $this->index( $plugin_id ) );
		}

	}