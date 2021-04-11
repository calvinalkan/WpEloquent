<?php


    namespace WpEloquent;

    use WpEloquent\ExtendsWpDb\BetterWpDb;
    use WpEloquent\ExtendsWpDb\BetterWpDbQM;

    class Symlinker
    {

        public static function create()
        {

            $db = WP_CONTENT_DIR . '/db.php';


            if ( self::symlinkExists($db) && ! self::queryMonitorActive()) {

                update_option('better-wp-db-symlink-created', 0 );

                throw new \Exception('The database drop-in is already symlinked to the file: ' . readlink($db));

            }

            if ( ! self::symlinkExists($db)  ) {

                $reflector = new \ReflectionClass(BetterWpDb::class );
                $is_symlink_to = $reflector->getFileName();

                $success = @symlink( $is_symlink_to , $db);

            }

            if ( self::symlinkExists($db) && self::queryMonitorActive() ) {


                self::replaceQmSymlink($db);


            }


            update_option('better-wp-db-symlink-created', 1 );


        }


        public static function symlinkExists($file) : bool
        {

            return  file_exists( $file ) &&  is_link($file);

        }

        public static function queryMonitorActive() : bool
        {


            return is_plugin_active('query-monitor/query-monitor.php');

        }

        private static function replaceQmSymlink($db)
        {

            $unlinked = unlink($db);

            $reflector = new \ReflectionClass(BetterWpDbQM::class );
            $is_symlink_to = $reflector->getFileName();

            $success = symlink( $is_symlink_to , $db);

        }


    }