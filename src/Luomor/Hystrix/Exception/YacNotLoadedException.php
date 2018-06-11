<?php
/**
 * Created by PhpStorm.
 * User: peterzhang
 * Date: 11/06/2018
 * Time: 8:22 PM
 */
namespace Luomor\Hystrix\Exception;

/**
 * Class YacNotLoadedException
 * Throw when Yac extension is not loaded. Yac is required for Hystrix to work.
 * @package Luomor\Hystrix
 */
class YacNotLoadedException extends \Exception {

}