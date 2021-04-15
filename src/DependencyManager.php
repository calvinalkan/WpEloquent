<?php


    namespace WpEloquent;

    class DependencyManager
    {


        /**
         * @var array
         */
        private $dependents;

        public function __construct()
        {

            $this->dependents = $this->getAll();

        }

        public function add($plugin_id)
        {


            if (is_int($this->index($plugin_id))) {

                return;

            }

            $this->markInDatabase($plugin = new DependentPlugin($plugin_id));

            $this->symlink($plugin);


        }

        public function getAll()
        {

            return get_option('better-wp-db-dependents', []);


        }

        public function remove($plugin_id)
        {

            if (is_int($key = $this->index($plugin_id))) {


                $this->removeDependent($key)
                     ->replaceWith($this->getNext())
                     ->refresh();

            }


        }

        private function getNext() : ?DependentPlugin
        {

            if (count($this->dependents)) {

                return new DependentPlugin(array_values($this->dependents)[0]);

            }

            return null;

        }

        private function index($plugin_id)
        {

            return array_search($plugin_id, $this->dependents, true);

        }

        private function symlink(DependentPlugin $plugin)
        {

            (new Symlink())->createFor($plugin);

        }

        private function markInDatabase(DependentPlugin $plugin)
        {

            $this->dependents[] = $plugin->getId();

            update_option('better-wp-db-dependents', $this->dependents);

        }

        private function removeDependent($index)
        {

            $plugin = new DependentPlugin($this->dependents[$index]);

            unset($this->dependents[$index]);

            (new Symlink())->removeFor($plugin);

            return $this;

        }

        private function refresh()
        {

            $this->dependents = array_values($this->dependents);

            if ( count ( $this->dependents )) {

                update_option('better-wp-db-dependents', $this->dependents);

                return;
            }

            delete_option('better-wp-db-dependents');


        }

        private function replaceWith($plugin)
        {

            if ($plugin) {

                (new Symlink())->createFor($plugin);

            }

            return $this;

        }

    }