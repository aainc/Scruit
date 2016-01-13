<?php
$path = dirname(dirname(dirname(dirname(__DIR__)))) . '/autoload.php';
if (!is_file($path)) $path = dirname(dirname(__DIR__)) . '/vendor/autoload.php';
require $path;
