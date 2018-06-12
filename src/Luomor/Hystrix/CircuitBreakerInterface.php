<?php
/**
 * Created by PhpStorm.
 * User: peterzhang
 * Date: 11/06/2018
 * Time: 7:14 PM
 */
namespace Luomor\Hystrix;

/**
 * Interface CircuitBreakerInterface
 * @package Luomor\Hystrix
 */
interface CircuitBreakerInterface {
    /**
     * Whether the circuit is open
     *
     * @return mixed
     */
    public function isOpen();

    /**
     * Whether the request is allowed
     *
     * @return mixed
     */
    public function allowRequest();

    /**
     * Whether a single test is allowed now
     *
     * @return mixed
     */
    public function allowSingleTest();

    /**
     * Marks a successful request
     *
     * @return mixed
     */
    public function markSuccess();
}