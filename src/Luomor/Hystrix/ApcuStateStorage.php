<?php
/**
 * Created by PhpStorm.
 * User: peterzhang
 * Date: 12/06/2018
 * Time: 2:17 PM
 */
namespace Luomor\Hystrix;

/**
 * Class ApcStateStorage
 * APC cache driven storage for Circuit Breaker metrics statistics
 * @package Luomor\Hystrix
 */
class ApcuStateStorage implements StateStorageInterface {
    const BUCKET_EXPIRE_SECONDS = 120;

    const CACHE_PREFIX = 'hystrix_cb_';

    const OPENED_NAME = 'opened';

    const SINGLE_TEST_BLOCKED = 'single_test_blocked';

    /**
     * ApcStateStorage constructor.
     */
    public function __construct() {
        if(!extension_loaded('apcu')) {
            throw new Exception\ApcNotLoadedException('"apcu" PHP extension is required for Hystrix to work');
        }
    }

    /**
     * Prepends cache prefix and filters out invalid characters
     *
     * @param $name
     * @return string
     */
    protected function prefix($name) {
        return self::CACHE_PREFIX . $name;
    }

    /**
     * Returns counter value for the given bucket
     *
     * @param string $commandKey
     * @param string $type
     * @param integer $index
     * @return mixed
     */
    public function getBucket($commandKey, $type, $index) {
        $bucketName = $this->prefix($commandKey . '_' . $type . '_' . $index);
        return apcu_fetch($bucketName);
    }

    /**
     * Increments counter value for the given bucket
     * @param $commandKey
     * @param $type
     * @param $index
     */
    public function incrementBucket($commandKey, $type, $index) {
        $bucketName = $this->prefix($commandKey . '_' . $type . '_' . $index);
        if(!apcu_add($bucketName, 1, self::BUCKET_EXPIRE_SECONDS)) {
            apcu_inc($bucketName);
        }
    }

    /**
     * If the given bucket is found, sets counter value to 0.
     *
     * @param string $commandKey Circuit breaker key
     * @param int $type
     * @param int $index
     */
    public function resetBucket($commandKey, $type, $index) {
        $bucketName = $this->prefix($commandKey . '_' . $type . '_' . $index);
        if(apcu_exists($bucketName)) {
            apcu_store($bucketName, 0, self::BUCKET_EXPIRE_SECONDS);
        }
    }

    /**
     * Marks the given circuit as open
     *
     * @param string $commandKey Circuit key
     * @param int $sleepingWindowInMilliseconds In how much time we should allow a single test
     */
    public function openCircuit($commandKey, $sleepingWindowInMilliseconds) {
        $openedKey = $this->prefix($commandKey . self::OPENED_NAME);
        $singleTestFlagKey = $this->prefix($commandKey . self::SINGLE_TEST_BLOCKED);

        apcu_store($openedKey, true);
        // the single test blocked flag will expire automatically in $sleepingWindowInMilliseconds
        // thus allowing us a single test. Notice, APC doesn't allow us to use
        // expire time less than a second.
        $sleepingWindowInSeconds = ceil($sleepingWindowInMilliseconds / 1000);
        apcu_add($singleTestFlagKey, true, $sleepingWindowInSeconds);
    }

    /**
     * Whether a single test is allowed
     * @param string $commandKey Circuit breaker key
     * @param int $sleepingWindowInMilliseconds In how much time we should allow the next single test
     * @return bool
     */
    public function allowSingleTest($commandKey, $sleepingWindowInMilliseconds) {
        $singleTestFlagKey = $this->prefix($commandKey . self::SINGLE_TEST_BLOCKED);
        // using 'add' enforces thread safety.
        $sleepingWindowInSeconds = ceil($sleepingWindowInMilliseconds / 1000);
        // anther APC limitation is that within the current request variables will never expire.
        return (boolean) apcu_add($singleTestFlagKey, true, $sleepingWindowInSeconds);
    }

    /**
     * Whether a circuit is open
     *
     * @param string $commandKey Circuit breaker key
     * @return bool
     */
    public function isCircuitOpen($commandKey) {
        $openedKey = $this->prefix($commandKey . self::OPENED_NAME);
        return (boolean) apcu_fetch($openedKey);
    }

    /**
     * Marks the given circuit as closed
     *
     * @param string $commandKey Circuit key
     */
    public function closeCircuit($commandKey) {
        $openedKey = $this->prefix($commandKey . self::OPENED_NAME);
        apcu_store($openedKey, false);
    }
}