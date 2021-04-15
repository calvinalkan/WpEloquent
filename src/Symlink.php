<?php


    namespace WpEloquent;

    class Symlink
    {

        public const wp_content_drop_in_path = WP_CONTENT_DIR . DIRECTORY_SEPARATOR . '/db.php';




        private function exists() : bool
        {
            return file_exists(self::wp_content_drop_in_path) && is_link(self::wp_content_drop_in_path);
        }

        public function createFor (DependentPlugin $plugin ) {


            if ( $this->exists() ) return;

            symlink( $plugin->vendorDropInPath() , self::wp_content_drop_in_path );

        }

        public function removeFor(DependentPlugin $plugin)
        {

            unlink(self::wp_content_drop_in_path);

        }

        public function swap(DependentPlugin $plugin)
        {

            $this->removeFor($plugin);

            return $this;

        }

        public function for(DependentPlugin $plugin) {

            $this->createFor($plugin);

        }

    }