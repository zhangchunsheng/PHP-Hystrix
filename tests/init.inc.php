<?php
/**
 * Created by PhpStorm.
 * User: peterzhang
 * Date: 15/06/2018
 * Time: 10:59 AM
 */
namespace Luomor\Hystrix {
    $globalUnitTestHystrixMicroTime = false;

    function microtime($get_as_float = null) {
        global $globalUnitTestHystrixMicroTime;
        if($globalUnitTestHystrixMicroTime === false) {
            return \microtime($get_as_float);
        } else {
            return $globalUnitTestHystrixMicroTime;
        }
    }
}