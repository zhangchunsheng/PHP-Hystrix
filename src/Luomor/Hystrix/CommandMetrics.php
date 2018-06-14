<?php
/**
 * Created by PhpStorm.
 * User: peterzhang
 * Date: 11/06/2018
 * Time: 6:00 PM
 */
namespace Luomor\Hystrix;

/**
 * Class CommandMetrics
 * This class tracks different metrics for a command.
 *
 * Main purpose is to provide health statistics to the command's circuit breaker.
 * Some metrics are tracked for informational purposes, e.g. how effective usage of the cache has been.
 * Tracking only happens within the rolling statistical window.
 * @package Luomor\Hystrix
 */
class CommandMetrics {
    /**
     * @var MetricsCounter
     */
    private $counter;

    /**
     * @var int
     */
    private $healthSnapshotIntervalInMilliseconds = 1000;

    /**
     * @var HealthCountsSnapshot
     */
    private $lastSnapshot;

    /**
     * CommandMetrics constructor.
     * @param MetricsCounter $counter
     * @param integer $snapshotInterval Snapshot interval time in milliseconds
     */
    public function __construct(MetricsCounter $counter, $snapshotInterval) {
        $this->counter = $counter;
        $this->healthSnapshotIntervalInMilliseconds = $snapshotInterval;
    }

    /**
     * Increments success counter
     */
    public function markSuccess() {
        $this->counter->add(MetricsCounter::SUCCESS);
    }

    /**
     * Increments from cache counter
     */
    public function markResponseFromCache() {
        $this->counter->add(MetricsCounter::RESPONSE_FROM_CACHE);
    }

    /**
     * Increments failure counter
     */
    public function markFailure() {
        $this->counter->add(MetricsCounter::FAILURE);
    }

    /**
     * Increments fallback success counter
     */
    public function markFallbackSuccess() {
        $this->counter->add(MetricsCounter::FALLBACK_SUCCESS);
    }

    /**
     * Increments fallback failure counter
     */
    public function markFallbackFailure() {
        $this->counter->add(MetricsCounter::FALLBACK_FAILURE);
    }

    /**
     * Increments exception thrown counter
     */
    public function markExceptionThrown() {
        $this->counter->add(MetricsCounter::EXCEPTION_THROWN);
    }

    /**
     * Increments short circuited counter
     */
    public function markShortCircuited() {
        $this->counter->add(MetricsCounter::SHORT_CIRCUITED);
    }

    /**
     * Resets counters for all metrics
     * may cause some stats to be removed from reporting, see http://goo.gl/dtHN34
     */
    public function resetCounter() {
        $this->counter->reset();
    }

    /**
     * Returns rolling count for a given metrics type
     *
     * @param integer $type E.g. MetricsCounter::SUCCESS
     * @return int
     */
    public function getRollingCount($type) {
        return $this->counter->get($type);
    }

    /**
     * Returns (and creates when needed) the current health metrics snapshot
     *
     * @return HealthCountsSnapshot
     */
    public function getHealthCounts() {
        // current time in milliseconds
        $now = microtime(true) * 1000;
        // we should make a new snapshot in case there isn't one yet or when the snapshot interval time has passed
        if(!$this->lastSnapshot
            || $now - $this->lastSnapshot->getTime() >= $this->healthSnapshotIntervalInMilliseconds) {
            $this->lastSnapshot = new HealthCountsSnapshot(
                $now,
                $this->getRollingCount(MetricsCounter::SUCCESS),
                $this->getRollingCount(MetricsCounter::FAILURE)
            );
        }

        return $this->lastSnapshot;
    }
}