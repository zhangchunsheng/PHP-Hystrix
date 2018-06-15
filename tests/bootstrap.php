<?php
/**
 * Created by PhpStorm.
 * User: peterzhang
 * Date: 14/06/2018
 * Time: 8:51 PM
 */
define("PS_ROOT", __DIR__);
define("PROJECT", "PS");

require 'init.inc.php';

spl_autoload_register(function ($class) {
    $class = str_replace("\\", "/", $class);
    $file = '';
    // model && controller && service
    $pre = strtok($class, '/');
    if ($pre === 'Luomor') {
        $file = PS_ROOT . "/../src/$class.php";
    } else if ($pre === 'Tests') {
        $file = PS_ROOT . "/../tests/$class.php";
    } else {
        $class = str_replace("_", "/", $class);
        $file = "/usr/share/pear/$class.php";
    }
    if ($file && is_file($file)) {
        require $file;
    }
});