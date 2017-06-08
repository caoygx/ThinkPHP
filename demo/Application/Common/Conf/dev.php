<?php

return array(

    'DB_TYPE'=>'mysql',
    'DB_HOST'=>'localhost',
    'DB_PORT' => '3306',
    'DB_NAME'=>'tr',
    'DB_USER'=>'root',
    'DB_PWD'=>'',
    'DB_PREFIX'=>'',

    "log" =>  array(
        'DB_HOST' => "127.0.0.1",
        'DB_USER' => "root",
        'DB_PWD'  => "",
        'DB_NAME' => 'test',
        'DB_TYPE' => "mongo",
        'DB_CHARSET'=>'utf8',
        'DB_PREFIX' => 'think_',
        'DB_PARAMS' => array(PDO::ATTR_PERSISTENT => true,PDO::ATTR_TIMEOUT=>1),


    ),
);