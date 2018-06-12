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
}