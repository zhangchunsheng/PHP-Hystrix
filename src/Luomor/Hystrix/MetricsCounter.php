<?php
/**
 * Created by PhpStorm.
 * User: peterzhang
 * Date: 12/06/2018
 * Time: 6:15 PM
 */
namespace Luomor\Hystrix;

/**
 * Class MetricsCounter
 * Counts successful and failed requests for a given circuit/command group
 * @package Luomor\Hystrix
 */
class MetricsCounter {
    const
        SUCCESS             = 1,
        FAILURE             = 2,
        TIMEOUT             = 3,
        SHORT_CIRCUITED     = 4,
        FALLBACK_SUCCESS    = 5,
        FALLBACK_FAILURE    = 6,
        EXCEPTION_THROWN    = 8,
        RESPONSE_FROM_CACHE = 9;

    /**
     * @var string
     */
    private $commandKey;

    /**
     * @var StateStorageInterface
     */
    private $stateStorage;

    /**
     * Time span to track counters, in milliseconds
     *
     * @var integer
     */
    private $rollingStatisticalWindowInMilliseconds;

    /**
     * Number of buckets within the statistical window
     *
     * @var integer
     */
    private $rollingStatisticalWindowBuckets;

    /**
     * Size of a bucket in milliseconds
     *
     * @var float
     */
    private $bucketInMilliseconds;

    /**
     * MetricsCounter constructor.
     * @param string $commandKey
     * @param StateStorageInterface $stateStorage
     * @param integer $rollingStatisticalWindowInMilliseconds Time span in milliseconds
     * @param integer $rollingStatisticalWindowBuckets The number of buckets in the statistical window
     */
    public function __construct(
        $commandKey,
        StateStorageInterface $stateStorage,
        $rollingStatisticalWindowInMilliseconds,
        $rollingStatisticalWindowBuckets
    ) {
        $this->commandKey = $commandKey;
        $this->stateStorage = $stateStorage;
        $this->rollingStatisticalWindowInMilliseconds = $rollingStatisticalWindowInMilliseconds;
        $this->rollingStatisticalWindowBuckets = $rollingStatisticalWindowBuckets;
        $this->bucketInMilliseconds =
            $this->rollingStatisticalWindowInMilliseconds / $this->rollingStatisticalWindowBuckets;
    }

    /**
     * Increase counter for given metric type
     *
     * @param integer $type
     */
    public function add($type) {
        $this->stateStorage->incrementBucket($this->commandKey, $type, $this->getCurrentBucketIndex());
    }

    /**
     * Calculates sum for given metric type within the statistical window
     *
     * @param integer $type
     * @return integer
     */
    public function get($type) {
        $sum = 0;
        $now = $this->getTimeInMilliseconds();

        for($i = 0 ; $i < $this->rollingStatisticalWindowBuckets ; $i++) {
            $bucketIndex = $this->getBucketIndex($i, $now);
            $sum += $this->stateStorage->getBucket($this->commandKey, $type, $bucketIndex);
        }

        return $sum;
    }

    /**
     * Returns current time on the server in milliseconds
     *
     * @return float
     */
    private function getTimeInMilliseconds() {
        return floor(microtime(true) * 1000);
    }

    /**
     * Returns unique index for the current bucket
     *
     * @return mixed
     */
    private function getCurrentBucketIndex() {
        return $this->getBucketIndex(0, $this->getTimeInMilliseconds());
    }

    /**
     * Gets unique bucket index by current time and bucket sequential number in the statistical window
     *
     * @param integer $bucketNumber
     * @param integer $time Current time in milliseconds
     * @return float
     */
    private function getBucketIndex($bucketNumber, $time) {
        // Getting unique bucket index
        return floor(($time - $bucketNumber * $this->bucketInMilliseconds) / $this->bucketInMilliseconds);
    }

    /**
     * Resets buckets for all metrics, within the statistical window.
     *
     * This is needed for when the statistical window is larger than the  sleep window (for allowSingleTest).
     * In such case, if we did not reset the buckets, we could possibly have had statistics effective for the
     * following request, causing the circuit to open right after it was just closed.
     *
     * May cause short-circuited stats to be removed from reporting, see http://goo.gl/dtHN34
     * https://github.com/Netflix/Hystrix/blob/master/hystrix-core/src/main/java/com/netflix/hystrix/HystrixCircuitBreaker.java#L150
     */
    public function reset() {
        // For each type of metric, we attempt to set the counter to 0
        foreach(array(self::SUCCESS, self::FAILURE, self::TIMEOUT, self::FALLBACK_SUCCESS,
                    self::FALLBACK_FAILURE, self::EXCEPTION_THROWN,
                    self::RESPONSE_FROM_CACHE) as $type) {
            $now = $this->getTimeInMilliseconds();
            for($i = 0 ; $i < $this->rollingStatisticalWindowBuckets ; $i++) {
                $bucketIndex = $this->getBucketIndex($i, $now);
                $this->stateStorage->resetBucket($this->commandKey, $type, $bucketIndex);
            }
        }
    }
}