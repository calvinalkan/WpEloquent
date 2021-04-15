<?php


    namespace WpEloquent;


    class Symlinker
    {

        public const drop_in_file_name = 'drop-in.php';

        private $ds = DIRECTORY_SEPARATOR;

        /**
         * @var string
         */
        private $db_drop_in_path;

        /**
         * @var DependentPlugin
         */
        private $dependent;


        public function __construct( string $plugin_vendor_id )
        {

            $this->dependent = new DependentPlugin($plugin_vendor_id);

            $this->db_drop_in_path = WP_CONTENT_DIR.$this->ds.'db.php';

        }


        public function create()
        {


            if ( $this->isInstalledCorrectly() ) {

                $this->dependent->add();;

                return;

            }


            if ($this->symlinkFromIncompatiblePluginSet()) {

                throw new \Exception('The database drop-in is already symlinked to the file: ' .readlink($this->db_drop_in_path));

            }

            if ( ! $this->symlinkExists() ) {

                symlink( $this->dependent->vendorDropInPath() , $this->db_drop_in_path);

            }

            $this->markAsInstalled();

            $this->dependent->add();

        }

        public function destroy () {

            $dependent_plugins = $this->dependent->remove();

            unlink( $this->db_drop_in_path );

            if ( ! empty($dependent_plugins)) {

                symlink(array_values($dependent_plugins)[0], $this->db_drop_in_path);

                return;

            }

            $this->markAsUninstalled();

        }

        public function dependent() : DependentPlugin
        {

            return $this->dependent;
        }

        private function queryMonitorActive() : bool
        {


            return is_plugin_active('query-monitor/query-monitor.php');

        }

        private function isInstalledCorrectly() : bool
        {

            return get_option('better-wp-db-symlink-created') === true;

        }

        private function markAsInstalled() : void
        {

            update_option('better-wp-db-symlink-created', true);
        }

        private function symlinkFromIncompatiblePluginSet() : bool
        {

            return $this->symlinkExists() && ! $this->queryMonitorActive();
        }

        private function symlinkExists() : bool
        {

            return file_exists($this->db_drop_in_path) && is_link($this->db_drop_in_path);
        }

        private function markAsUninstalled() : void
        {

            update_option('better-wp-db-symlink-created', false);
        }



    }