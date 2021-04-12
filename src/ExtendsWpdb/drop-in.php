<?php


    use WpEloquent\ExtendsWpdb\DbFactory;

    if ( ! class_exists( \wpdb::class ) ) return;

    $wpdb = new \wpdb(DB_USER, DB_PASSWORD, DB_NAME, DB_HOST);

    // $wpdb = DbFactory::make(new \wpdb(DB_USER, DB_PASSWORD, DB_NAME, DB_HOST));