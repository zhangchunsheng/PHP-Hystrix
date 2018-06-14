<?php
/**
 * Created by PhpStorm.
 * User: peterzhang
 * Date: 11/06/2018
 * Time: 6:03 PM
 */
namespace Luomor\Hystrix;

use Luomor\Config\Config;

/**
 * Class CommandMetricsFactory
 * Factory to keep track of and instantiate new command metrics objects when needed
 * @package Luomor\Hystrix
 */
class CommandMetricsFactory {
    /**
     * @var array
     */
    protected $commandMetricsByCommand = array();

    /**
     * @var StateStorageInterface
     */
    protected $stateStorage;

    /**
     * CommandMetricsFactory constructor.
     *
     * @param StateStorageInterface $stateStorage
     */
    public function __construct(StateStorageInterface $stateStorage) {
        $this->stateStorage = $stateStorage;
    }

    /**
     * Get command metrics instance by command key for given command config
     *
     * @param string $commandKey
     * @param Config $commandConfig
     * @return mixed
     */
    public function get($commandKey, Config $commandConfig) {
        if(!isset($this->commandMetricsByCommand[$commandKey])) {
            $metricsConfig = $commandConfig->get('metrics');
            $statisticalWindow = $metricsConfig->get('rollingStatisticalWindowInMilliseconds');
            $windowBuckets = $metricsConfig->get('rollingStatisticalWindowBuckets');
            $snapshotInterval = $metricsConfig->get('healthSnapshotIntervalInMilliseconds');

            $counter = new MetricsCounter($commandKey, $this->stateStorage, $statisticalWindow, $windowBuckets);
            $this->commandMetricsByCommand[$commandKey] = new CommandMetrics($counter, $snapshotInterval);
        }

        return $this->commandMetricsByCommand[$commandKey];
    }
}