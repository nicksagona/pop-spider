<?php

return [
    'routes' => [
        'help' => [
            'controller' => 'PopSpider\Controller\ConsoleController',
            'action'     => 'help',
            'help'       => "Show the help screen"
        ],
        'crawl [--dir=] [--tags=] [--save] <url>' => [
            'controller' => 'PopSpider\Controller\ConsoleController',
            'action'     => 'crawl',
            'help'       => "Crawl the provided URL"
        ]
    ]
];