<?php
ini_set('default_charset', 'utf-8');

define('__INCDIR__'  , __DIR__ . '/inc');
define('__CACHEDIR__', __DIR__ . '/cache');

$g_aApiPrefixes =   array(
    'EU'    => 'http://eu.battle.net',
    'US'    => 'http://us.battle.net'
);
if (isset($_SERVER['HTTP_HOST'])){
    $g_sURLBase     =   $_SERVER['HTTP_HOST'] . '/guildhub';
} else {
    $g_sURLBase     =   $_SERVER['SERVER_NAME'] . '/guildhub';
}

include_once __CACHEDIR__   .   '/cache.globals.php';

include_once __INCDIR__     .   '/inc.utils.php';
include_once __INCDIR__     .   '/inc.guildManager.php';
include_once __INCDIR__     .   '/inc.characterManager.php';
include_once __INCDIR__     .   '/inc.itemManager.php';



