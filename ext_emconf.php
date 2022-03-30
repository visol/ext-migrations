<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'Doctrine Migrations',
    'description' => 'Enable SQL migration powered by Doctrine',
    'category' => 'service',
    'version' => '1.0.1',
    'state' => 'stable',
    'author' => 'Kay Strobach, Fabien Udriot',
    'author_email' => 'typo3@kay-strobach.de, fabien.udriot@visol.ch',
    'author_company' => '',
    'constraints' => [
        'depends' => [
            'typo3' => '10.4.00-11.5.99'
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
];
