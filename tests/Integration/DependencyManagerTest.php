<?php


	namespace Tests\Integration;

	use Codeception\TestCase\WPTestCase;
	use Exception;
	use Illuminate\Support\Arr;
	use Illuminate\Support\Str;
	use WpEloquent\DependencyManager;
	use WpEloquent\DependentPlugin;
	use WpEloquent\Symlink;

	class DependencyManagerTest extends WPTestCase {

		// Calvin s as

		private $db_drop_in;

		private $stub_dir;

		private $plugins;

		private $plugin_0_2_0 = 'plugin_0_2_0/vendor';

		private $plugin_0_2_2 = 'plugin_0_2_2/vendor';

		private $plugin_0_2_4 = 'plugin_0_2_4/vendor';


		protected function setUp() : void {

			parent::setUp();

			$this->plugins = [ $this->plugin_0_2_0, $this->plugin_0_2_4, $this->plugin_0_2_2 ];

			$this->db_drop_in = getenv( 'WP_ROOT_FOLDER' ) . '/wp-content/db.php';

			if ( is_link( $this->db_drop_in ) ) {

				$success = unlink( $this->db_drop_in );

				self::assertTrue( $success );

			}

			$this->stub_dir = getenv( 'PACKAGE_ROOT' ) . '/tests/Stubs';

			$this->createTestPluginDirectory();


		}


		/** @test */
		public function a_dependent_plugins_symlink_can_be_created_if_its_the_first_dependent_plugin() {


			$this->assertSymlinkNotSet();

			$this->newManager()->add( $this->plugin_0_2_0 );

			$this->assertSymlinkFor( $this->plugin_0_2_0 );


		}

		/** @test */
		public function if_a_dependent_plugin_is_added_its_marked_in_the_database() {

			$this->assertNotMarkedInstalled( $this->plugin_0_2_0 );

			$this->newManager()->add( $this->plugin_0_2_0 );

			$this->assertMarkedInstalled( $this->plugin_0_2_0 );


		}

		/** @test */
		public function symlink_and_db_entry_get_deleted_if_a_plugin_is_removed() {

			$manager = $this->newManagerWithPreinstalled( $this->plugin_0_2_0 );

			$this->assertMarkedInstalled($this->plugin_0_2_0);
			$this->assertSymlinkFor($this->plugin_0_2_0);

			$manager->remove( $this->plugin_0_2_0 );

			$this->assertSymlinkNotSet();

			$this->assertNotMarkedInstalled( $this->plugin_0_2_0 );

		}

		/** @test */
		public function a_symlink_wont_be_created_again() {

			$manager = $this->newManagerWithPreinstalled( $this->plugin_0_2_0 );

			try {

				$manager->add( $this->plugin_0_2_0 );
				$this->assertTrue( true );

			}

			catch ( Exception $exception ) {

				$this->fail( $exception->getMessage() );

			}


		}

		/** @test */
		public function a_db_entry_wont_be_duplicated() {

			$manager = $this->newManagerWithPreinstalled( $this->plugin_0_2_0 );

			$before = get_option( DependencyManager::option_name );

			$manager->add( $this->plugin_0_2_0 );

			$after = get_option( DependencyManager::option_name );

			$this->assertEquals( $before, $after );

		}

		/** @test */
		public function if_no_dependent_is_active_anymore_symlink_and_db_settings_are_removed() {

			$manager = $this->newManagerWithPreinstalled( [
				$this->plugin_0_2_4,
				$this->plugin_0_2_2,
				$this->plugin_0_2_0,
			] );

			$this->assertSymlinkFor($this->plugin_0_2_4);
			$this->assertMarkedInstalled($this->plugin_0_2_4);
			$this->assertMarkedInstalled($this->plugin_0_2_2);
			$this->assertMarkedInstalled($this->plugin_0_2_0);

			$manager->remove( $this->plugin_0_2_4 );
			$manager->remove( $this->plugin_0_2_2 );
			$manager->remove( $this->plugin_0_2_0 );

			$this->assertSymlinkNotSet();
			$this->assertFalse( get_option( 'better-wp-db-dependents' ) );

		}

		/** @test */
		public function when_a_compatible_plugin_with_a_higher_version_number_is_activated_the_symlink_gets_swapped() {

			$manager = $this->newManagerWithPreinstalled( [
				$this->plugin_0_2_2,
				$this->plugin_0_2_0,
			] );

			$manager->add( $this->plugin_0_2_4 );

			$this->assertMarkedInstalled( $this->plugin_0_2_0 );
			$this->assertMarkedInstalled( $this->plugin_0_2_4 );
			$this->assertMarkedInstalled( $this->plugin_0_2_2 );

			$this->assertSymlinkFor( $this->plugin_0_2_4 );


		}

		/** @test */
		public function the_symlink_stays_the_same_when_a_compatible_plugin_with_lower_version_is_added() {


			$manager = $this->newManagerWithPreinstalled( [
				$this->plugin_0_2_4,
				$this->plugin_0_2_0,
			] );

			$this->assertSymlinkFor( $this->plugin_0_2_4 );

			$manager->add( $this->plugin_0_2_2 );

			$this->assertSymlinkFor( $this->plugin_0_2_4 );

			$this->assertMarkedInstalled( $this->plugin_0_2_0 );
			$this->assertMarkedInstalled( $this->plugin_0_2_4 );
			$this->assertMarkedInstalled( $this->plugin_0_2_2 );

		}

		/** @test */
		public function the_next_highest_symlink_gets_created_when_the_highest_dependent_gets_removed() {

			$manager = $this->newManagerWithPreinstalled( [
				$this->plugin_0_2_4,
				$this->plugin_0_2_2,
				$this->plugin_0_2_0,
			] );

			$manager->remove( $this->plugin_0_2_4 );

			$this->assertSymlinkFor( $this->plugin_0_2_2 );

		}

		/** @test */
		public function a_dependent_can_be_added_if_there_are_already_some_present_in_the_database() {

			$manager = $this->newManagerWithPreinstalled( [
				$this->plugin_0_2_4,
				$this->plugin_0_2_0,
			] );

			$this->assertSymlinkFor( $this->plugin_0_2_4 );

			$manager->add( $this->plugin_0_2_2 );

			$this->assertSymlinkFor( $this->plugin_0_2_4 );

		}

		/** @test */
		public function the_dependent_plugin_object_is_never_stored_in_the_database_on_addition() {

			$manager = $this->newManagerWithPreinstalled( [
				$this->plugin_0_2_0,
				$this->plugin_0_2_2,
			] );


			$manager->add( $this->plugin_0_2_4 );

			$this->assertSame( [
				$this->plugin_0_2_0,
				$this->plugin_0_2_2,
				$this->plugin_0_2_4,
			], get_option( DependencyManager::option_name ) );


		}

		/** @test */
		public function the_dependent_plugin_object_is_never_stored_in_the_database_on_removal() {

			$manager = $this->newManagerWithPreinstalled( [
				$this->plugin_0_2_0,
				$this->plugin_0_2_2,
				$this->plugin_0_2_4,
			] );


			$manager->remove( $this->plugin_0_2_4 );

			$this->assertSame( [
				$this->plugin_0_2_0,
				$this->plugin_0_2_2,
			], get_option( DependencyManager::option_name ) );


		}


		private function assertSymlinkFor( $plugin_id ) {

			self::assertTrue( file_exists( $this->db_drop_in ),
				'The file: ' . $this->db_drop_in . ' doesnt exists.' );
			self::assertTrue( is_link( $this->db_drop_in ),
				'The file: ' . $this->db_drop_in . ' is not a symlink.' );

			self::assertSame( ( new DependentPlugin( $plugin_id ) )->dbDropInPath(), readlink( $this->db_drop_in ) );


		}

		private function assertSymlinkNotSet() {

			self::assertFalse( file_exists( $this->db_drop_in ) );
			self::assertFalse( is_link( $this->db_drop_in ) );

		}

		private function createTestPluginDirectory() {

			array_walk( $this->plugins, function ( $plugin ) {

				$dir = Str::before( $plugin, '/' );

				if ( ! is_link( WP_CONTENT_DIR . DIRECTORY_SEPARATOR . 'plugins' . DIRECTORY_SEPARATOR . $dir ) ) {

					$symlinked = symlink(
						$this->stub_dir . DIRECTORY_SEPARATOR . $dir,
						WP_CONTENT_DIR . DIRECTORY_SEPARATOR . 'plugins' . DIRECTORY_SEPARATOR . $dir

					);

					$this->assertTrue( $symlinked, 'Plugin:' . $dir . ' was not symlinked' );

				}


			} );

		}

		private function assertMarkedInstalled( $plugin_id ) {

			$this->assertContains( $plugin_id, get_option( DependencyManager::option_name, [] ) );

		}

		private function assertNotMarkedInstalled( $plugin_id ) {

			$this->assertNotContains( $plugin_id, get_option( DependencyManager::option_name, [] ) );

		}

		private function newManagerWithPreinstalled( $plugins ) : DependencyManager {

			$plugins = Arr::wrap( $plugins );

			update_option( DependencyManager::option_name, $plugins );

			( ( new Symlink() ) )->createFor( new DependentPlugin( $plugins[0] ) );

			return new DependencyManager();

		}

		private function newManager () : DependencyManager {

			return new DependencyManager();

		}

	}
