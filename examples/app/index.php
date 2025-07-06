<?php

require_once __DIR__ . '/../../vendor/autoload.php';

$app = new Express\Core\Application();
$app->get('/', function ($req, $res) {

    $res->html('Hello, World!');
});

$app->run();
