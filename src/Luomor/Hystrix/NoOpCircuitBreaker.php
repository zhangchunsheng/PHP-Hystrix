<?php
/**
 * Created by PhpStorm.
 * User: peterzhang
 * Date: 12/06/2018
 * Time: 4:15 PM
 */
namespace Luomor\Hystrix;

/**
 * Class NoOpCircuitBreaker
 * A not operational circuit breaker, will always like as a closed circuit
 *
 * @package Luomor\Hystrix
 */
class NoOpCircuitBreaker implements  CircuitBreakerInterface {
    /**
     * Single test will always be allowed
     *
     * @return bool
     */
    public function allowSingleTest() {
        return true;
    }

    /**
     * Request will always be allowed
     *
     * @return bool
     */
    public function allowRequest() {
        return true;
    }

    /**
     * Circuit is never closed
     *
     * @return bool
     */
    public function isOpen() {
        return false;
    }

    /**
     * Does nothing (enforced by Circuit Breaker interface)
     */
    public function markSuccess() {

    }
}