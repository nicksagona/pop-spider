<?php

set_time_limit(0);

require_once __DIR__  . '/../vendor/autoload.php';

$app = new PopSpider\Application(
    include __DIR__ . '/../app/config/app.console.php'
);

$app->run();
