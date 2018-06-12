<?php
/**
 * Created by PhpStorm.
 * User: peterzhang
 * Date: 12/06/2018
 * Time: 2:25 PM
 */
namespace Luomor\Hystrix\Exception;

/**
 * Class ApcNotLoadedException
 * Throw when APC extension is not loaded. APC is required for Hystrix to work
 * @package Luomor\Hystrix
 */
class ApcNotLoadedException extends \Exception {

}