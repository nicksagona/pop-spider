<?php

return [
    'routes' => [
        'help' => [
            'controller' => 'PopSpider\Controller\ConsoleController',
            'action'     => 'help',
            'default'    => true
        ],
        'crawl <url> [--dir=] [--tags=]' => [
            'controller' => 'PopSpider\Controller\ConsoleController',
            'action'     => 'crawl'
        ]
    ]
];