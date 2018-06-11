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

        $this->_yac = new Yac();
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

    public function incrementBucket($commandKey, $type, $index) {
        // lock
        $bucketName = $this->prefix($commandKey . '_' . $type . '_' . $index);
        $number = $this->_yac->get($bucketName);
        if(empty($number)) {
            $number = 1;
        } else {
            $number++;
        }
        $this->_yac->set($bucketName, $number, self::BUCKET_EXPIRE_SECONDS);
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
        return $this->_yac->get($bucketName);
    }

    public function openCircuit($commandKey, $sleepingWindowInMilliseconds) {

    }

    public function closeCircuit($commandKey) {

    }

    public function allowSingleTest($commandKey, $sleepingWindowInMilliseconds) {

    }

    public function isCircuitOpen($commandKey) {

    }

    public function resetBucket($commandKey, $type, $index) {

    }
}