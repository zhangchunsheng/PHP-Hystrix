<?php
/**
 * Created by PhpStorm.
 * User: peterzhang
 * Date: 11/06/2018
 * Time: 7:13 PM
 */
namespace Luomor\Hystrix;

use Luomor\Config\Config;

/**
 * Class CircuitBreaker
 * Circuit-breaker logic that is hooked into AbstractCommand execution and will stop allowing executions
 * if failures have gone past the defined threshold.
 *
 * It will then allow single retries after a defined sleepWindow until the execution succeeds
 * at which point it will again close the circuit and allow executions again.
 * @package Luomor\Hystrix
 */
class CircuitBreaker implements CircuitBreakerInterface {
    /**
     * @var CommandMetrics
     */
    private $metrics;

    /**
     * Hystrix config
     * @var Config
     */
    private $config;

    /**
     * @var StateStorageInterface
     */
    private $stateStorage;

    /**
     * String identifier of the group of commands this circuit breaker is responsible for
     *
     * @var string
     */
    private $commandKey;

    /**
     * CircuitBreaker constructor.
     * @param $commandKey
     * @param CommandMetrics $metrics
     * @param Config $commandConfig
     * @param StateStorageInterface $stateStorage
     */
    public function __construct(
        $commandKey,
        CommandMetrics $metrics,
        Config $commandConfig,
        StateStorageInterface $stateStorage
    ) {
        $this->commandKey = $commandKey;
        $this->metrics = $metrics;
        $this->config = $commandConfig;
        $this->stateStorage = $stateStorage;
    }

    /**
     * Whether the circuit is open
     *
     * @return boolean
     */
    public function isOpen() {
        if($this->stateStorage->isCircuitOpen($this->commandKey)) {
            // if we're open we immediately return true and don't bother attempting to 'close' ourself
            // as that is left to allowSingleTest and a subsequent successful test to close
            return true;
        }

        $healthCounts = $this->metrics->getHealthCounts();
        echo "getTotal:" . $healthCounts->getTotal();
        if($healthCounts->getTotal() < $this->config->get('circuitBreaker')->get('requestVolumeThreshold')) {
            // we are not past the minimum volume threshold for the statistical window
            // so we'll return false immediately and not calculate anything
            return false;
        }

        $allowedErrorPercentage = $this->config->get('circuitBreaker')->get('errorThresholdPercentage');
        echo "getErrorPercentage:" . $healthCounts->getErrorPercentage();
        if($healthCounts->getErrorPercentage() < $allowedErrorPercentage) {
            return false;
        } else {
            $this->stateStorage->openCircuit(
                $this->commandKey,
                $this->config->get('circuitBreaker')->get('sleepWindowInMilliseconds')
            );
            return true;
        }
    }

    /**
     * Whether a single test is allowed now
     *
     * @return bool
     */
    public function allowSingleTest() {
        return $this->stateStorage->allowSingleTest(
            $this->commandKey,
            $this->config->get('circuitBreaker')->get('sleepWindowInMilliseconds')
        );
    }

    /**
     * Whether the request is allowed
     *
     * @return boolean
     */
    public function allowRequest() {
        if($this->config->get('circuitBreaker')->get('forceOpen')) {
            return false;
        }
        if($this->config->get('circuitBreaker')->get('forceClose')) {
            return true;
        }

        return !$this->isOpen() || $this->allowSingleTest();
    }

    public function markSuccess() {
        if($this->stateStorage->isCircuitOpen($this->commandKey)) {
            $this->stateStorage->closeCircuit($this->commandKey);
            // may cause some stats to be removed from reporting, see http://goo.gl/dtHN34
            $this->metrics->resetCounter();
        }
    }
}