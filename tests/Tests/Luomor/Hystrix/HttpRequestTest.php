<?php
/**
 * Created by PhpStorm.
 * User: peterzhang
 * Date: 19/06/2018
 * Time: 10:47 AM
 */
namespace Tests\Luomor\Hystrix;

use Luomor\Config\Config;
use Luomor\Hystrix\YacStateStorage;
use Luomor\Hystrix\ApcuStateStorage;
use Luomor\Hystrix\CircuitBreakerFactory;
use Luomor\Hystrix\CommandMetricsFactory;
use Luomor\Hystrix\RequestCache;
use Luomor\Hystrix\RequestLog;

class HttpRequestTest extends \PHPUnit_Framework_TestCase {
    public function testHttpRequest() {
        $command = new HttpRequest();

        $command->url = "http://base.luomor.com/api/dict/getDictData";
        $apcStateStorage = new YacStateStorage();
        $commandMetricsFactory = new CommandMetricsFactory($apcStateStorage);
        $command->setCommandMetricsFactory($commandMetricsFactory);
        $circuitBreakerFactory = new CircuitBreakerFactory($apcStateStorage);
        $command->setCircuitBreakerFactory($circuitBreakerFactory);
        $command->setRequestCache(new RequestCache());
        $requestLog = new RequestLog();
        $command->setRequestLog($requestLog);
        $command->setConfig(new Config(array(
            'fallback' => array(
                'enabled' => true,
            ),
            'requestCache' => array(
                'enabled' => true,
            ),
            'requestLog' => array(
                'enabled' => true,
            ),
            'circuitBreaker' => array(
                'enabled' => true,
                'sleepWindowInMilliseconds' => 3000,
                'errorThresholdPercentage' => 10
            ),
            'metrics' => array(
                'rollingStatisticalWindowInMilliseconds' => 300000,
                'rollingStatisticalWindowBuckets' => 1,
                'healthSnapshotIntervalInMilliseconds' => 3000,
            ),
        ), true));

        $result = $command->execute();
        print_r($result);
    }
}