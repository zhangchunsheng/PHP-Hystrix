<?php
/**
 * Created by PhpStorm.
 * User: peterzhang
 * Date: 19/06/2018
 * Time: 10:47 AM
 */
namespace Tests\Luomor\Hystrix;

class HttpRequestTest extends \PHPUnit_Framework_TestCase {
    public function testHttpRequest() {
        $command = new HttpRequest();

        $command->execute();
    }
}