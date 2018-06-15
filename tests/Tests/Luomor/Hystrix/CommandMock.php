<?php
/**
 * Created by PhpStorm.
 * User: peterzhang
 * Date: 15/06/2018
 * Time: 10:41 AM
 */
namespace Tests\Luomor\Hystrix;

use Luomor\Hystrix\AbstractCommand;
use Luomor\Hystrix\Exception\BadRequestException;

class CommandMock extends AbstractCommand {
    public $throwBadRequestException = false;

    public $throwException = false;

    public $throwExceptionInFallback = false;

    public $cacheKey = null;

    public $simulateDelay = false;

    protected function run() {
        if($this->simulateDelay) {
            // simulates that command execution tool 555 milliseconds
            global $globalUnitTestHystrixMicroTime;
            $globalUnitTestHystrixMicroTime += 0.555;
        }
        if($this->throwBadRequestException) {
            throw new BadRequestException('special treatment');
        } elseif($this->throwException) {
            throw new \DomainException('could not run');
        } else {
            return 'run result';
        }
    }

    protected function getFallback(\Exception $e = null) {
        if($this->throwExceptionInFallback) {
            throw new \DomainException('error falling back');
        } else {
            return 'fallback result';
        }
    }

    protected function getCacheKey() {
        return $this->cacheKey;
    }
}