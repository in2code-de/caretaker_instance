<?php

$GLOBALS['TYPO3_CONF_VARS']['DB']['Connections']['Default'] = [
    'charset' => 'utf8',
    'driver' => 'mysqli',
    'dbname' => getenv('MYSQL_DATABASE'),
    'host' => 'mysql',
    'user' => getenv('MYSQL_USER'),
    'password' => getenv('MYSQL_PASSWORD'),
];

$GLOBALS['TYPO3_CONF_VARS']['SYS']['sitename'] = 'LOKAL: ' . $GLOBALS['TYPO3_CONF_VARS']['SYS']['sitename'];

$GLOBALS['TYPO3_CONF_VARS']['GFX']['colorspace'] = 'sRGB';
$GLOBALS['TYPO3_CONF_VARS']['GFX']['processor_enabled'] = true;
$GLOBALS['TYPO3_CONF_VARS']['GFX']['processor'] = 'ImageMagick';
$GLOBALS['TYPO3_CONF_VARS']['GFX']['processor_path'] = '/usr/bin/';
$GLOBALS['TYPO3_CONF_VARS']['GFX']['processor_path_lzw'] = '/usr/bin/';

// If argon2i is not supported you can use this hash instead. Both are the password "joh316".
//$GLOBALS['TYPO3_CONF_VARS']['BE']['installToolPassword'] = '$pbkdf2-sha256$25000$1giiaIbTy850yoVvkFQUrQ$EVI1K4RWFyPImJQUd3ZFn/zGQGpqCu5QWWvsvkMNt2k';
$GLOBALS['TYPO3_CONF_VARS']['BE']['installToolPassword'] = '$argon2i$v=19$m=65536,t=16,p=1$UnNyU1FXYmtzdEY3d2ZiOQ$7SkZw9NRaVvJC6gyTJj70pBUrnpFWtkVB3u0dz63uoE';

$GLOBALS['TYPO3_CONF_VARS']['BE']['lockSSL'] = '0';
$GLOBALS['TYPO3_CONF_VARS']['BE']['compressionLevel'] = '0';
$GLOBALS['TYPO3_CONF_VARS']['BE']['debug'] = true;

// Debug lokal aktivieren - OS übergreifend
$GLOBALS['TYPO3_CONF_VARS']['SYS']['displayErrors'] = 1;
$GLOBALS['TYPO3_CONF_VARS']['SYS']['enableDeprecationLog'] = 'file';
$GLOBALS['TYPO3_CONF_VARS']['SYS']['systemLogLevel'] = 0;
$GLOBALS['TYPO3_CONF_VARS']['SYS']['devIPmask'] = '*';
$GLOBALS['TYPO3_CONF_VARS']['SYS']['clearCacheSystem'] = 1;
$GLOBALS['TYPO3_CONF_VARS']['SYS']['curlUse'] = 1;
$GLOBALS['TYPO3_CONF_VARS']['SYS']['exceptionalErrors'] = '28674';

// Mail Settings
$GLOBALS['TYPO3_CONF_VARS']['MAIL']['transport_smtp_server'] = 'mail:1025';
$GLOBALS['TYPO3_CONF_VARS']['MAIL']['transport_smtp_encrypt'] = '';
$GLOBALS['TYPO3_CONF_VARS']['MAIL']['transport_smtp_password'] = '';
$GLOBALS['TYPO3_CONF_VARS']['MAIL']['transport_smtp_username'] = '';
$GLOBALS['TYPO3_CONF_VARS']['MAIL']['transport'] = 'smtp';
$GLOBALS['TYPO3_CONF_VARS']['MAIL']['defaultMailFromAddress'] = 'docker@localhost';
$GLOBALS['TYPO3_CONF_VARS']['MAIL']['defaultMailFromName'] = 'local - Docker';

// Used for in-container requests
$GLOBALS['TYPO3_CONF_VARS']['HTTP']['verify'] = '0';
