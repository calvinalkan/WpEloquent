<?php


    if ( ! class_exists(\wpdb::class)) {
        return;
    }

    $dir = dirname(__FILE__, 3);

    $autoloader = $dir .'/vendor/autoload.php';

    require_once $autoloader;

    $wpdb = \WpEloquent\ExtendsWpdb\DbFactory::make(new \wpdb(DB_USER, DB_PASSWORD, DB_NAME, DB_HOST));





    throw new Exception('foo message');


