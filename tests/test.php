<?php
/**
 * Created by PhpStorm.
 * User: peterzhang
 * Date: 19/06/2018
 * Time: 5:09 PM
 */

require('bootstrap.php');

use Luomor\Hystrix\AbstractCommand;
use Luomor\Config\Config;
use Luomor\Hystrix\YacStateStorage;
use Luomor\Hystrix\ApcuStateStorage;
use Luomor\Hystrix\CircuitBreakerFactory;
use Luomor\Hystrix\CommandMetricsFactory;
use Luomor\Hystrix\RequestCache;
use Luomor\Hystrix\RequestLog;

class HttpRequestTest extends AbstractCommand {
    public $url = "http://base.lan-tc.yongche.org/api/dict/getDictData";
    public $params = array(
        "dict_category_id" => 1
    );

    protected function run() {
        $url = $this->url . '?' . http_build_query($this->params);

        $ch = curl_init();

        try {
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 5);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3);

            $result = curl_exec($ch);

            if(curl_error($ch)) {
                $errMsg = curl_error($ch);
                throw new \Exception($errMsg);
            }

            curl_close($ch);
        } catch(\Exception $e) {
            curl_close($ch);
            throw $e;
        }

        if(empty($result)) {
            throw new \Exception('not result');
        }

        $arrResult = json_decode($result, true);
        $jsonError = json_last_error();

        if($jsonError == JSON_ERROR_NONE) {
            return $arrResult;
        } else {
            throw new \Exception('json decode error');
        }
    }

    protected function getFallback() {
        return "fallback";
    }

    protected function getCacheKey() {
        $url = $this->url . '?' . http_build_query($this->params);
        return md5($url);
    }

    protected function processExecutionEvent($eventName) {
        echo "eventName:$eventName";
    }
}

$command = new HttpRequestTest();

$command->url = "http://base.lan-tc.yongche.org/api/dict/getDictData";
$apcStateStorage = new ApcuStateStorage();
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