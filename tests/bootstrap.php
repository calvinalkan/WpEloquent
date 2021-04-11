<?php


    $path = getenv('WP_ROOT_FOLDER') . '/wp-content/plugins/plugin-stub';

    if (is_link($path)) {

       return;

    }

    $test_path = getenv('PLUGIN_STUB_PATH');

    $success = @symlink( $test_path, $path);




