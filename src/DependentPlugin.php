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

        /**
         * @var PluginFile
         */
        private $plugin_file;

        public function __construct(string $id, PluginFile $plugin_file = null )
        {

            $this->id = $id;
            $this->vendor_folder = WP_CONTENT_DIR.DIRECTORY_SEPARATOR.'plugins'.DIRECTORY_SEPARATOR.$id;
            $this->plugin_file = $plugin_file ?? new PluginFile();

        }

        public function dbDropInPath()
        {

           return $this->plugin_file->dbDropInPath($this->id);

        }

        public function vendorDir () : string
        {

            return $this->vendor_folder;

        }

        public function getId() : string
        {

            return $this->id;
        }


    }