<?php



    $path = getenv('WP_ROOT_FOLDER') . '/wp-content/db.php';

    if (is_link($path)) {

       return;

    }

   $target = getenv('PACKAGE_ROOT') . '/src/ExtendsWpdb/BetterWpDb.php';

    $success = symlink($target, $path);





