<?php
/**
 * Created by PhpStorm.
 * User: peterzhang
 * Date: 11/06/2018
 * Time: 7:13 PM
 */
namespace Luomor\Hystrix;

use Luomor\Config\Config;

/**
 * Class CircuitBreaker
 * Circuit-breaker logic that is hooked into AbstractCommand execution and will stop allowing executions
 * if failures have gone past the defined threshold.
 *
 * It will then allow single retries after a defined sleepWindow until the execution succeeds
 * at which point it will again close the circuit and allow executions again.
 * @package Luomor\Hystrix
 */
class CircuitBreaker implements CircuitBreakerInterface {

}