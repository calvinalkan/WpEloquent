<?php


    namespace WpEloquent;

    class DatabaseErrorHandler
    {

        /**
         * @var bool
         */

        private $wp_debug;

        /**
         * @var bool
         */
        private $wp_debug_display;


        public function __construct(bool $wp_debug = false, bool $wp_debug_display = false)
        {

            $this->wp_debug = $wp_debug;
            $this->wp_debug_display = $wp_debug_display;

        }

        public function handle(QueryException $exception)
        {

            if ($this->isDebugMode()) {

                throw $exception;

            }

            add_filter('wp_php_error_message', function ($message) {

                return 'Database Error: Something weird happened.';

            });


        }

        private function isDebugMode() : bool
        {

            return $this->wp_debug && $this->wp_debug_display;

        }

    }