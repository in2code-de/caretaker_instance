<?php
$EM_CONF[$_EXTKEY] = [
    'title' => 'Caretaker Instance',
    'description' => 'Client for caretaker observation system',
    'category' => 'misc',
    'author' => 'Martin Ficzel, Thomas Hempel, Christopher Hlubek, Tobias Liebig, Jan Haffner',
    'author_email' => 'ficzel@work.de,hempel@work.de,hlubek@networkteam.com,typo3@etobi.de',
    'state' => 'stable',
    'author_company' => '',
    'version' => '3.0.3',
    'constraints' =>
        [
            'depends' =>
                [
                    'typo3' => '12.4.0-12.4.99',
                ],
            'conflicts' =>
                [
                ],
            'suggests' =>
                [
                ],
        ],
    'autoload' =>
        [
            'classmap' =>
                [
                    0 => 'services',
                    1 => 'classes',
                ],
        ],
    '_md5_values_when_last_written' => '',
];
