<?php
/**
 * Created by PhpStorm.
 * User: peterzhang
 * Date: 14/06/2018
 * Time: 4:23 PM
 */
namespace Luomor\Hystrix;

/**
 * Class HealthCountsSnapshot
 * Represent a snapshot of current statistical metrics for a Circuit Breaker
 * @package Luomor\Hystrix
 */
class HealthCountsSnapshot {
    /**
     * @var integer
     */
    private $successful;

    /**
     * @var integer
     */
    private $failure;

    /**
     * Time the snapshot was made, in milliseconds
     * @var integer
     */
    private $time;

    /**
     * HealthCountsSnapshot constructor.
     * @param integer $time
     * @param integer $successful
     * @param integer $failure
     */
    public function __construct($time, $successful, $failure) {
        $this->time = $time;
        $this->failure = $failure;
        $this->successful = $successful;
    }

    /**
     * Returns the time the snapshot was taken
     *
     * @return int
     */
    public function getTime() {
        return $this->time;
    }

    /**
     * Returns the number of failures
     *
     * @return int
     */
    public function getFailure() {
        return $this->failure;
    }

    /**
     * Returns the number of failures
     *
     * @return int
     */
    public function getSuccessful() {
        return $this->successful;
    }

    /**
     * Returns the total sum of request made
     *
     * @return int
     */
    public function getTotal() {
        return $this->successful + $this->failure;
    }

    /**
     * Returns error percentage
     *
     * @return float
     */
    public function getErrorPercentage() {
        $total = $this->getTotal();
        if(!$total) {
            return 0;
        } else {
            return $this->getFailure() / $total * 100;
        }
    }
}