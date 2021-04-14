<?php


    namespace WpEloquent;

    use Symfony\Component\Finder\Finder;

    class DependentPlugin
    {

        /**
         * @var string
         */
        private $id;



        /**
         * @var string
         */
        private $vendor_folder;

        public const drop_in_file_name = 'drop-in.php';

        public function __construct(string $id)
        {

            $this->id = $id;

            $this->vendor_folder = WP_CONTENT_DIR. DIRECTORY_SEPARATOR .'plugins'. DIRECTORY_SEPARATOR .$id;

        }

        public function vendorDropInPath()
        {

            $finder = new Finder();

            $finder->ignoreUnreadableDirs()
                   ->files()
                   ->followLinks()
                   ->in($this->vendor_folder.'/calvinalkan/wp-eloquent/src/*')
                   ->exclude('Traits')
                   ->name(self::drop_in_file_name);

            $drop_in_path = iterator_to_array($finder, false)[0];

            return $drop_in_path->getRealPath();

        }

        public function remove()
        {

            $dependent_plugins = get_option('better-wp-db-dependents');

            unset($dependent_plugins[$this->id]);

            update_option('better-wp-db-dependents', $dependent_plugins);

            return $dependent_plugins;

        }

        public function add() : void
        {

            $dependents = get_option('better-wp-db-dependents');
            $dependents[$this->id] = $this->vendorDropInPath();

            update_option('better-wp-db-dependents', $dependents);

        }

    }