<?php


    use WpEloquent\SymlinkHandler;

    if ( ! function_exists('createDbSymlink')) {

        function createDbSymlink () {

                $handler = new SymlinkHandler();

                $handler->createSymlinks();

        }


    }
