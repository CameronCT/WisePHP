<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once(__DIR__ . '/src/Wise.php');

$dir_templates  = __DIR__ . '/views';
$dir_cache      = __DIR__ . '/cache';

try {
    $Wise = new WisePHP($dir_templates, $dir_cache, false);
    $Wise->display('index'); 

} catch (Exception $e) {
    echo $e->getMessage();
}