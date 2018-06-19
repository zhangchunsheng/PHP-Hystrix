<?php
/**
 * Created by PhpStorm.
 * User: peterzhang
 * Date: 11/06/2018
 * Time: 8:09 PM
 */
namespace Luomor\Hystrix;

class YacStateStorage implements StateStorageInterface {
    const BUCKET_EXPIRE_SECONDS = 120;

    const CACHE_PREFIX = 'hystrix_cb_';

    const OPENED_NAME = 'opened';

    const SINGLE_TEST_BLOCKED = 'single_test_blocked';

    private $_yac = null;

    /**
     * YacStateStorage constructor.
     * @throws Exception\YacNotLoadedException
     */
    public function __construct() {
        if(!extension_loaded('yac')) {
            throw new Exception\YacNotLoadedException('"yac" PHP extension is required for Hystrix to work');
        }

        $this->_yac = new \Yac();
    }

    /**
     * Prepends cache prefix and filters out invalid characters
     *
     * @param string $name
     * @return string
     */
    protected function prefix($name) {
        return self::CACHE_PREFIX . $name;
    }

    /**
     * Increments counter value for the given bucket
     *
     * @param string $commandKey
     * @param string $type
     * @param integer $index
     */
    public function incrementBucket($commandKey, $type, $index) {
        // lock
        $bucketName = $this->prefix($commandKey . '_' . $type . '_' . $index);
        if(strlen($bucketName) >= 40) {
            $key = md5($bucketName);
        } else {
            $key = $bucketName;
        }

        $number = $this->_yac->get($key);
        if(empty($number)) {
            $number = 1;
        } else {
            $number++;
        }
        $this->_yac->set($key, $number, self::BUCKET_EXPIRE_SECONDS);
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
        if(strlen($bucketName) >= 40) {
            $key = md5($bucketName);
        } else {
            $key = $bucketName;
        }
        return $this->_yac->get($key);
    }

    public function openCircuit($commandKey, $sleepingWindowInMilliseconds) {
        $openedKey = $this->prefix($commandKey . self::OPENED_NAME);
        $singleTestFlagKey = $this->prefix($commandKey . self::SINGLE_TEST_BLOCKED);
        if(strlen($openedKey) >= 40) {
            $openedKey = md5($openedKey);
        }
        if(strlen($singleTestFlagKey) >= 40) {
            $singleTestFlagKey = md5($singleTestFlagKey);
        }

        $this->_yac->set($openedKey, true);

        // the single test blocked flag will expire automatically in $sleepingWindowInMilliseconds
        // thus allowing us a single test. Notice, yac doesn't allow us to use
        // expire time less than a second.
        $sleepingWindowInSeconds = ceil($sleepingWindowInMilliseconds / 1000);
        $this->_yac->set($singleTestFlagKey, true, $sleepingWindowInSeconds);

    }

    /**
     * Marks the given circuit as closed
     *
     * @param string $commandKey Circuit key
     */
    public function closeCircuit($commandKey) {
        $openedKey = $this->prefix($commandKey . self::OPENED_NAME);
        if(strlen($openedKey) >= 40) {
            $openedKey = md5($openedKey);
        }

        $this->_yac->set($openedKey, false);
    }

    /**
     * Whether a single test is allowed
     *
     * @param string $commandKey Circuit breaker key
     * @param int $sleepingWindowInMilliseconds In how much time we should allow the next single test
     * @return bool
     */
    public function allowSingleTest($commandKey, $sleepingWindowInMilliseconds) {
        $singleTestFlagKey = $this->prefix($commandKey . self::SINGLE_TEST_BLOCKED);
        if(strlen($singleTestFlagKey) >= 40) {
            $singleTestFlagKey = md5($singleTestFlagKey);
        }

        // using 'add' enforces thread safety.
        $sleepingWindowInSeconds = ceil($sleepingWindowInMilliseconds / 1000);
        // another yac limitation is that within the current request variables will never expire.
        return (boolean) $this->_yac->set($singleTestFlagKey, true, $sleepingWindowInSeconds);
    }

    /**
     * Whether a circuit is open
     *
     * @param string $commandKey Circuit breaker key
     * @return bool
     */
    public function isCircuitOpen($commandKey) {
        $openedKey = $this->prefix($commandKey . self::OPENED_NAME);
        if(strlen($openedKey) >= 40) {
            $openedKey = md5($openedKey);
        }

        return (boolean) $this->_yac->get($openedKey);
    }

    /**
     * If the given bucket is found, sets counter value to 0.
     *
     * @param string $commandKey
     * @param integer $type
     * @param integer $index
     */
    public function resetBucket($commandKey, $type, $index) {
        $bucketName = $this->prefix($commandKey . '_' . $type . '_' . $index);
        if(strlen($bucketName) >= 40) {
            $key = md5($bucketName);
        } else {
            $key = $bucketName;
        }

        $this->_yac->set($key, 0, self::BUCKET_EXPIRE_SECONDS);
    }
}