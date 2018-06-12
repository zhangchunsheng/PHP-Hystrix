<?php
/**
 * Created by PhpStorm.
 * User: peterzhang
 * Date: 11/06/2018
 * Time: 8:10 PM
 */
namespace Luomor\Hystrix;

/**
 * Interface StateStorageInterface
 * Interface all circuit breaker state storage classes must inherit
 *
 * @package Luomor\Hystrix
 */
interface StateStorageInterface {
    /**
     * Increments counter value for the given bucket
     *
     * @param $commandKey
     * @param $type
     * @param $index
     */
    public function incrementBucket($commandKey, $type, $index);

    /**
     * Returns counter value for the given bucket
     *
     * @param $commandKey
     * @param $type
     * @param $index
     * @return mixed
     */
    public function getBucket($commandKey, $type, $index);

    /**
     * Marks the given circuit as open
     *
     * @param string $commandKey
     * @param integer $sleepingWindowInMilliseconds In how much time we should allow a single test
     */
    public function openCircuit($commandKey, $sleepingWindowInMilliseconds);

    /**
     * Marks the given circuit as closed
     *
     * @param string $commandKey Circuit key
     */
    public function closeCircuit($commandKey);

    /**
     * Whether a single test is allowed
     *
     * @param string $commandKey Circuit breaker key
     * @param integer $sleepingWindowInMilliseconds In how much time we should allow the next single test
     * @return boolean
     */
    public function allowSingleTest($commandKey, $sleepingWindowInMilliseconds);

    /**
     * Whether a circuit is open
     *
     * @param string $commandKey Circuit breaker key
     * @return mixed
     */
    public function isCircuitOpen($commandKey);

    /**
     * If the given bucket is found, sets counter value to 0.
     *
     * @param string $commandKey Circuit breaker key
     * @param integer $type
     * @param integer $index
     */
    public function resetBucket($commandKey, $type, $index);
}