<?php
/**
 * Created by PhpStorm.
 * User: peterzhang
 * Date: 19/06/2018
 * Time: 10:47 AM
 */
namespace Tests\Luomor\Hystrix;

use Luomor\Config\Config;
use Luomor\Hystrix\ApcStateStorage;
use Luomor\Hystrix\CommandMetricsFactory;

class HttpRequestTest extends \PHPUnit_Framework_TestCase {
    public function testHttpRequest() {
        $command = new HttpRequest();

        $apcStateStorage = new ApcStateStorage();
        $commandMetricsFactory = new CommandMetricsFactory($apcStateStorage);
        $command->setCommandMetricsFactory($commandMetricsFactory);
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
        ), true));

        $command->execute();
    }
}