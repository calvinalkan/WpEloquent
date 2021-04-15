<?php


	namespace WpEloquent;

	class Symlink {

		public const wp_content_drop_in_path = WP_CONTENT_DIR . DIRECTORY_SEPARATOR . '/db.php';


		private function exists() : bool {

			return file_exists( self::wp_content_drop_in_path ) && is_link( self::wp_content_drop_in_path );
		}

		public function createFor( DependentPlugin $plugin ) {


			if ( $this->exists() && $this->isForPlugin( $plugin ) ) {
				return;
			}

			if ( $this->exists() && ! $this->isForPlugin($plugin)) {

				$this->remove();

			}

			symlink( $plugin->dbDropInPath(), self::wp_content_drop_in_path );

		}

		public function removeFor( DependentPlugin $plugin ) {

			$this->remove();

		}

		private function remove () {

			unlink( self::wp_content_drop_in_path );

		}

		private function isForPlugin( DependentPlugin $plugin ) {

			return readlink( self::wp_content_drop_in_path ) === $plugin->dbDropInPath();


		}


	}