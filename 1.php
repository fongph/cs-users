<?php

include 'vendor/autoload.php';

$config = array(
    'db' => array(
        'host' => 'localhost',
        'username' => 'root',
        'password' => 'root',
        'dbname' => 'main',
        'options' => array(
            PDO::MYSQL_ATTR_INIT_COMMAND => 'set names utf8;',
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        )
    )
);

$pdo = new \PDO("mysql:host={$config['db']['host']};dbname={$config['db']['dbname']}", $config['db']['username'], $config['db']['password'], $config['db']['options']);

$a = new CS\Users\UsersManager($pdo);
