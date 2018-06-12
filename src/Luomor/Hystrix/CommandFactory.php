<?php
/**
 * Created by PhpStorm.
 * User: peterzhang
 * Date: 12/06/2018
 * Time: 4:55 PM
 */
namespace Luomor\Hystrix;

use ReflectionClass;
use Luomor\Config\Config;
use Luomor\Di\LocatorInterface;

/**
 * Class CommandFactory
 * All commands must be created through this factory.
 * It injects all dependencies required for Circuit Breaker logic etc.
 * @package Luomor\Hystrix
 */
class CommandFactory {
    /**
     * @var Config
     */
    protected $config;

    /**
     * @var LocatorInterface
     */
    protected $serviceLocator;

    /**
     * @var CircuitBreakerFactory
     */
    protected $circuitBreakerFactory;

    /**
     * @var CommandMetricsFactory
     */
    protected $commandMetricsFactory;

    /**
     * @var RequestCache
     */
    protected $requestCache;

    /**
     * @var RequestLog
     */
    protected $requestLog;

    /**
     * CommandFactory constructor.
     * @param Config $config
     * @param LocatorInterface $serviceLocator
     * @param CircuitBreakerFactory $circuitBreakerFactory
     * @param CommandMetricsFactory $commandMetricsFactory
     * @param RequestCache|null $requestCache
     * @param RequestLog|null $requestLog
     */
    public function __construct(
        Config $config,
        LocatorInterface $serviceLocator,
        CircuitBreakerFactory $circuitBreakerFactory,
        CommandMetricsFactory $commandMetricsFactory,
        RequestCache $requestCache = null,
        RequestLog $requestLog = null
    ) {
        $this->serviceLocator = $serviceLocator;
        $this->config = $config;
        $this->circuitBreakerFactory = $circuitBreakerFactory;
        $this->commandMetricsFactory = $commandMetricsFactory;
        $this->requestCache = $requestCache;
        $this->requestLog = $requestLog;
    }

    public function getCommand($class) {
        $parameters = func_get_args();
        array_shift($parameters);

        $reflection = new ReflectionClass($class);
        /** @var AbstractCommand $command */
        $command = empty($parameters) ?
            $reflection->newInstance() :
            $reflection->newInstanceArgs($parameters);

        $command->setCircuitBreakerFactory($this->circuitBreakerFactory);
        $command->setCommandMetricsFactory($this->commandMetricsFactory);
        $command->setServiceLocator($this->serviceLocator);
        $command->initializeConfig($this->config);

        if($this->requestCache) {
            $command->setRequestCache($this->requestCache);
        }

        if($this->requestLog) {
            $command->setRequestLog($this->requestLog);
        }

        return $command;
    }
}