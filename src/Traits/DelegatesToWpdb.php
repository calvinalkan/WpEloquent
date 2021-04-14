<?php


    namespace WpEloquent\Traits;

    /**
     * Trait DelegatesToWpdb
     *
     * @property \wpdb $wpdb;
     *
     */
    trait DelegatesToWpdb
    {


        public function __get($name)
        {

            return $this->wpdb->{$name};

        }

        public function __set($name, $value)
        {

            $this->wpdb->{$name} = $value;

        }

        public function __isset($name)
        {

            return isset($this->wpdb->{$name});
        }

        public function __unset($name)
        {

            unset($this->wpdb->{$name});
        }


        public function __call($method, $arguments)
        {

            try {

                return $this->wpdb->{$method}(...$arguments);

            }

            catch (\Exception $e) {


                // wpdb does not use exception handling by default.
                // Instead it handles everything using mysqli->errno.
                // For BetterWpDB queries we have our own error handler but since we set
                // mysqli_report() we need "translate exceptions that might occur within
                // core/plugin wpdb calls to the dedicated wpdb error mechanism.
                // If we dont do this Wordpress with blatantly spit out mysql errors.
                $this->wpdb->print_error();


            }


        }


    }