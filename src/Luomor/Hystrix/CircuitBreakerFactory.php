<?php
/**
 * Created by PhpStorm.
 * User: peterzhang
 * Date: 11/06/2018
 * Time: 5:57 PM
 */
namespace Luomor\Hystrix;

use Luomor\Config\Config;

/**
 * Class CircuitBreakerFactory
 * Factory to keep track of and instantiate new circuit breakers when needed
 *
 * @package Luomor\Hystrix
 */
class CircuitBreakerFactory {
    /**
     * @var array
     */
    protected $circuitBreakersByCommand = array();

    /**
     * @var StateStorageInterface
     */
    protected $stateStorage;

    /**
     * CircuitBreakerFactory constructor.
     * @param StateStorageInterface $stateStorage
     */
    public function __construct(StateStorageInterface $stateStorage) {
        $this->stateStorage = $stateStorage;
    }

    /**
     * Get circuit breaker instance by command key for given command config
     *
     * @param string $commandKey
     * @param Config $commandConfig
     * @param CommandMetrics $metrics
     * @return CircuitBreakerInterface
     */
    public function get($commandKey, Config $commandConfig, CommandMetrics $metrics) {
        if(!isset($this->circuitBreakersByCommand[$commandKey])) {
            $circuitBreakerConfig = $commandConfig->get('circuitBreaker');
            if($circuitBreakerConfig->get('enabled')) {
                $this->circuitBreakersByCommand[$commandKey] =
                    new CircuitBreaker($commandKey, $metrics, $commandConfig, $this->stateStorage);
            } else {
                $this->circuitBreakersByCommand[$commandKey] = new NoOpCircuitBreaker();
            }
        }

        return $this->circuitBreakersByCommand[$commandKey];
    }
}