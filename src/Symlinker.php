<?php


    namespace WpEloquent;

    use WpEloquent\ExtendsWpDb\BetterWpDb;
    use WpEloquent\ExtendsWpDb\BetterWpDbQM;



    class Symlinker
    {

        public static function create(string $plugin)
        {


            if (self::isInstalledCorrectly()) {

                self::addDependent($plugin);

                return;

            }

            $db = WP_CONTENT_DIR.'/db.php';

            if (self::symlinkFromIncompatiblePluginSet($db)) {

                throw new \Exception('The database drop-in is already symlinked to the file: '.readlink($db));

            }

            if ( self::symlinkSet($db) && self::queryMonitorActive()) {

                self::replaceQmSymlink($db);

            }

            if ( ! self::symlinkSet($db) ) {

                $reflector = new \ReflectionClass(BetterWpDb::class);
                symlink($reflector->getFileName(), $db);

            }


            self::markAsInstalled();

            self::addDependent($plugin);

        }

        public static function destroy(string $plugin)
        {

            $dependent_plugins = self::removeDependent($plugin);

            if (empty($dependent_plugins)) {

                unlink(WP_CONTENT_DIR.'/db.php');

                self::markAsUninstalled();

            }


        }

        private static function queryMonitorActive() : bool
        {


            return is_plugin_active('query-monitor/query-monitor.php');

        }

        private static function replaceQmSymlink($db)
        {

            $unlinked = unlink($db);

            $reflector = new \ReflectionClass(BetterWpDbQM::class);
            $is_symlink_to = $reflector->getFileName();

            $success = symlink($is_symlink_to, $db);

        }

        private static function isInstalledCorrectly() : bool
        {

            return get_option('better-wp-db-symlink-created') === true;

        }

        private static function addDependent(string $plugin) : void
        {

            $dependents = get_option('better-wp-db-dependents');
            $dependents[$plugin] = $plugin;

            update_option('better-wp-db-dependents', $dependents);

        }

        private static function removeDependent(string $plugin)
        {

            $dependent_plugins = get_option('better-wp-db-dependents');

            unset($dependent_plugins[$plugin]);

            update_option('better-wp-db-dependents', $dependent_plugins);

            return $dependent_plugins;

        }

        private static function markAsInstalled() : void
        {

            update_option('better-wp-db-symlink-created', true);
        }

        private static function symlinkFromIncompatiblePluginSet(string $db) : bool
        {

            return file_exists($db) && is_link($db) && ! self::queryMonitorActive();
        }

        private static function symlinkSet(string $db) : bool
        {

            return file_exists($db) && is_link($db);
        }

        private static function markAsUninstalled() : void
        {

            update_option('better-wp-db-symlink-created', false);
        }


    }