<?php
/**
 * Created by PhpStorm.
 * User: peterzhang
 * Date: 15/06/2018
 * Time: 11:06 AM
 */
namespace Tests\Luomor\Hystrix;

use Luomor\Hystrix\AbstractCommand;
use Luomor\Hystrix\Exception\RuntimeException;
use Luomor\Hystrix\RequestCache;
use Luomor\Hystrix\RequestLog;
use Luomor\Config\Config;

class CommandTest extends \PHPUnit_Framework_TestCase {
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $circuitBreakerFactory;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $circuitBreaker;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $commandMetricsFactory;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $commandMetrics;

    /**
     * @var CommandMock
     */
    protected $command;

    /**
     * @var RequestLog
     */
    protected $requestLog;

    protected function setUp() {
        $this->circuitBreakerFactory = $this->getMock('Luomor\Hystrix\CircuitBreakerFactory', array(), array(), '', false);
        $this->circuitBreaker = $this->getMock('Luomor\Hystrix\CircuitBreakerInterface', array(), array(), '', false);
        $this->commandMetricsFActory = $this->getMock('Luomor\Hystrix\CircuitMetricsFactory', array(), array(), '', false);
        $this->commandMetrics = $this->getMock('Luomor\Hystrix\CommandMetrics', array(), array(), '', false);

        $this->command = new CommandMock();
        $this->command->setCommandMetricsFactory($this->commandMetricsFactory);
        $this->command->setRequestCache(new RequestCache());
        $this->requestLog = new RequestLog();
        $this->command->setRequestLog($this->requestLog);
        $this->command->setCircuitBreakerFactory($this->circuitBreakerFactory);
        $this->command->setConfig(new Config(array(
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
    }

    /**
     * Something many tests need
     *
     * @param bool $allowRequest (Optional) Whether CB should allow the request
     */
    protected function setUpCommonExpectations($allowRequest = true) {
        $this->circuitBreakerFactory->expects($this->once())
            ->method('get')
            ->with('Tests\Luomor\Hystrix\CommandMock')
            ->will($this->returnValue($this->circuitBreaker));
        $this->circuitBreaker->expects($this->once())
            ->method('allowRequest')
            ->will($this->returnValue($allowRequest));
        $this->commandMetricsFactory->expects($this->atLeastOnce())
            ->method('get')
            ->with('Tests\Luomor\Hystrix\CommandMock')
            ->will($this->returnValue($this->commandMetrics));
    }

    protected function setUpExecutionDelayExpectations() {
        // testing execution time was recorded correctly
        global $globalUnitTestHystrixMicroTime;
        $globalUnitTestHystrixMicroTime = 1529036351.1266;
        $this->command->simulateDelay = true;
    }

    public function testSetTestMergesConfig() {
        $command = new CommandMock();
        $command->setConfig(new Config(array('a' => 1), true));
        $command->setConfig(new Config(array('b' => 2), true));
        $this->assertAttributeEquals(new Config(array('a' => 1, 'b' => 2), true), 'config', $command);
        $command->setConfig(new Config(array('c' => 3), true), false);// false to skip merge
        $this->assertAttributeEquals(new Config(array('c' => 3), true), 'config', $command);
    }

    public function testExecuteDefaultCommandKey() {
        // default command key is the class name
        $this->setUpCommonExpectations();
        $this->command->execute();
    }

    public function testExecutePresetCommandKey() {
        $this->commandMetricsFactory->expects($this->atLeastOnce())
            ->method('get')
            ->with('PresetTestCommandKey')
            ->will($this->returnValue($this->commandMetrics));
        $reflection = new \ReflectionClass('Tests\Luomor\Hystrix\CommandMock');
        $property = $reflection->getProperty('commandKey');
        $property->setAccessible(true);
        $property->setValue($this->command, 'PresetTestCommandKey');
        $this->commandMetricsFactory->expects($this->atLeastOnce())
            ->method('get')
            ->with('PresetTestCommandKey')
            ->will($this->returnValue($this->commandMetrics));

        $this->circuitBreakerFactory->expects($this->once())
            ->method('get')
            ->with('PresetTestCommandKey')
            ->will($this->returnValue($this->circuitBreaker));
        $this->circuitBreaker->expects($this->once())
            ->method('allowRequest')
            ->will($this->returnValue(true));

        $this->command->execute();
    }

    public function testExecute() {
        $this->setUpCommonExpectations();
        $this->setUpExecutionDelayExpectations();
        $this->circuitBreaker->expects($this->once())
            ->method('markSuccess');
        $this->commandMetrics->expects($this->once())
            ->method('markSuccess');
        $this->assertEmpty($this->requestLog->getExecutedCommands());
        $this->assertEquals(null, $this->command->getExecutionTimeInMilliseconds());
        $this->assertEquals('run result', $this->command->execute());
        $this->assertEquals(array(AbstractCommand::EVENT_SUCCESS), $this->command->getExecuteEvents());
        $this->assertEquals(array($this->command), $this->requestLog->getExecutedCommands());
        $this->assertEquals(555, $this->command->getExecutionTimeInMilliseconds());
    }

    public function testRequestLogOff() {
        $this->setUpCommonExpectations();
        $this->command->setConfig(new Config(array(
            'requestLog' => array(
                'enabled' => false,
            )
        )));
        $this->assertEmpty($this->requestLog->getExecutedCommands());
        $this->assertEquals('run result', $this->command->execute());
        $this->assertEmpty($this->requestLog->getExecutedCommands());
    }

    public function testRequestLogNotInjected($logEnabled) {
        // Duplicate some of the class setup in order to bypass requestLog generation
        $command = new CommandMock();
        $command->setCommandMetricsFactory($this->commandMetricsFactory);
        $command->setCircuitBreakerFactory($this->circuitBreakerFactory);
        $command->setRequestCache(new RequestCache());

        $command->setConfig(new Config(array(
            'requestLog' => array(
                'enabled' => $logEnabled,
            ),
        ), true));

        $this->setUpCommonExpectations();

        $this->assertEquals('run result', $this->command->execute());
    }

    /**
     * Ensure command does not break when configured to cache, though cache hasn't been injected
     *
     * @dataProvider configBoolProvider
     *
     * @param bool $cacheEnabled whether config is set to use request log
     */
    public function testRequestCacheNotInjected($cacheEnabled) {
        // Duplicate some of the class setup in order to bypass requestLog generation
        $command = new CommandMock();
        $command->setCommandMetricsFactory($this->commandMetricsFactory);
        $command->setCircuitBreakerFactory($this->circuitBreakerFactory);
        $command->setRequestLog($this->requestLog);

        $command->setConfig(new Config(array(
            'requestCache' => array(
                'enabled' => $cacheEnabled,
            )
        ), true));

        $this->setUpCommonExpectations();

        $this->assertEquals('run result', $this->command->execute());
    }

    /**
     * @return array
     */
    public function configBoolProvider() {
        return array(
            'config enabled' => array(true),
            'config disabled' => array(false),
        );
    }

    public function testExecuteRequestNotAllowed() {
        $this->setUpCommonExpectations(false);

        $this->commandMetrics->expects($this->once())
            ->method('markShortCircuited');
        $this->circuitBreaker->expects($this->never())
            ->method('markSuccess');
        $this->commandMetrics->expects($this->never())
            ->method('markSuccess');
        $this->commandMetrics->expects($this->never())
            ->method('markFailure');

        $this->assertEquals('fallback result', $this->command->execute());
        $this->assertEquals(
            array(AbstractCommand::EVENT_SHORT_CIRCUITED, AbstractCommand::EVENT_FALLBACK_SUCCESS),
            $this->command->getExecuteEvents()
        );
        // execution time is not recorded
        $this->assertEquals(null, $this->command->getExecutionTimeInMilliseconds());
    }

    public function testExecuteRunException() {
        $this->setUpCommonExpectations();
        $this->setUpExecutionDelayExpectations();
        $this->commandMetrics->expects($this->never()) // because fallback is enabled and working
        ->method('markExceptionThrown');
        $this->commandMetrics->expects($this->once())
            ->method('markFallbackSuccess');
        $this->commandMetrics->expects($this->once())
            ->method('markFailure');
        $this->command->throwException = true;
        $this->assertEquals('fallback result', $this->command->execute());
        $this->assertEquals(
            array(AbstractCommand::EVENT_FAILURE, AbstractCommand::EVENT_FALLBACK_SUCCESS),
            $this->command->getExecuteEvents()
        );
        $this->assertEquals(555, $this->command->getExecutionTimeInMilliseconds());
        $this->assertEquals(new \DomainException('could not run'), $this->command->getExecutionException());
    }

    public function testExecuteFallbackFailed() {
        $this->setUpCommonExpectations();
        $this->commandMetrics->expects($this->never())
            ->method('markFallbackSuccess');
        $this->commandMetrics->expects($this->once())
            ->method('markFallbackFailure');
        $this->commandMetrics->expects($this->once())
            ->method('markExceptionThrown');
        $this->commandMetrics->expects($this->once())
            ->method('markFailure');
        $this->command->throwException = true;
        $this->command->throwExceptionInFallback = true;
        try {
            $this->command->execute();
            $this->fail('Exception expected');
        } catch(\Exception $exception) {

        }
        $this->assertInstanceOf('Luomor\Hystrix\Exception\RuntimeException', $exception);
        $this->assertEquals('could not run and failed retrieving fallback', $exception->getMessage());
        $this->assertEquals(
            array(
                AbstractCommand::EVENT_FAILURE,
                AbstractCommand::EVENT_FALLBACK_FAILURE,
                AbstractCommand::EVENT_EXCEPTION_THROWN
            ),
            $this->command->getExecuteEvents()
        );
        $this->assertEquals($exception->getPrevious(), $this->command->getExecutionException());
    }

    public function testRuntimeExceptionPopulated() {
        $this->setUpCommonExpectations();
        $this->command->throwException = true;
        $this->command->throwExceptionInFallback = true;
        try {
            $this->command->execute();
            $this->fail('Luomor\Hystrix\Exception\RuntimeException was expected');
        } catch(RuntimeException $exception) {
            $this->assertInstanceOf('Luomor\Hystrix\Exception\RuntimeException', $exception);
            $this->assertInstanceOf('DomainException', $exception->getPrevious());
            $this->assertInstanceOf('DomainException', $exception->getFallbackException());
            $this->assertEquals('Tests\Luomor\Hystrix\CommandMock', $exception->getCommandClass());
            $this->assertEquals('could not run and failed retrieving fallback', $exception->getMessage());
            $this->assertEquals('error falling back', $exception->getFallbackException()->getMessage());
        }
    }

    public function testBadRequestExceptionTracksNoMetrics() {
        $this->setUpCommonExpectations();
        $this->commandMetrics->expects($this->never())
            ->method('markFallbackSuccess');
        $this->commandMetrics->expects($this->never())
            ->method('markFallbackFailure');
        $this->commandMetrics->expects($this->never())
            ->method('markExceptionThrown');
        $this->commandMetrics->expects($this->never())
            ->method('markFailure');
        $this->command->throwBadRequestException = true;
        $this->setExpectedException('Luomor\Hystrix\Exception\BadRequestException', 'special treatment');
        $this->command->execute();
        // no events logged in the case
        $this->assertEquals(array(), $this->command->getExecuteEvents());
        // execution time is not recorded
        $this->assertEquals(null, $this->command->getExecutionTimeInMilliseconds());
    }

    public function testShortCircuitedExceptionMessage() {
        $this->setUpCommonExpectations(false);
        $this->command->throwException = true;
        $this->command->throwExceptionInFallback = true;
        try {
            $this->command->execute();
            $this->fail('\Luomor\Hystrix\Exception\RuntimeException was expected');
        } catch(RuntimeException $exception) {
            $this->assertInstanceOf('Luomor\Hystrix\Exception\RuntimeException', $exception);
            $this->assertEquals('Short-circuited and failed retrieving fallback', $exception->getMessage());
        }
    }

    public function testExecuteFallbackDisabled() {
        $this->setUpCommonExpectations();
        $this->command->setconfig(new Config(array('fallback' => array('enabled' => false))));
        $this->commandMetrics->expects($this->never()) // because fallback is disabled
        ->method('markFallbackSuccess');
        $this->commandMetrics->expects($this->never()) // because fallback is disabled
        ->method('markFallbackFailure');
        $this->commandMetrics->expects($this->once())
            ->method('markExceptionThrown');
        $this->commandMetrics->expects($this->once())
            ->method('markFailure');
        $this->command->throwException = true;
        try {
            $this->command->execute();
            $this->fail('Exception expected');
        } catch(\Exception $exception) {

        }
        $this->assertInstanceOf('Luomor\Hystrix\Exception\RuntimeException', $exception);
        $this->assertEquals('could not run and fallback disabled', $exception->getMessage());
        $this->assertEquals(
            array(
                AbstractCommand::EVENT_FAILURE,
                AbstractCommand::EVENT_EXCEPTION_THROWN
            ),
            $this->command->getExecuteEvents()
        );
    }

    public function testRequestCacheHit() {
        $this->commandMetricsFactory->expects($this->atLeastOnce())
            ->method('get')
            ->with('Tests\Luomor\Hystrix\CommandMock')
            ->will($this->returnValue($this->commandMetrics));
        /** @var RequestCache|\PHPUnit_Framework_MockObject_MockObject $requestCache */
        $requestCache = $this->getMock('Luomor\Hystrix\RequestCahce');
        $requestCache->expects($this->once())
            ->method('exists')
            ->with('Tests\Luomor\Hystrix\CommandMock', 'test-cache-key')
            ->will($this->returnValue(true));
        $requestCache->expects($this->once())
            ->method('get')
            ->with('Tests\Luomor\Hystrix\CommandMock', 'test-cache-key')
            ->will($this->returnValue('result from cache'));
        $this->command->cacheKey = 'test-cache-key';
        $this->command->setRequestCache($requestCache);
        $this->circuitBreakerFactory->expects($this->never())->method('get');
        $this->commandMetrics->expects($this->once())->method('markResponseFromCache');
        $this->assertEquals('result from cache', $this->command->execute());
        $this->assertEquals(array(AbstractCommand::EVENT_RESPONSE_FROM_CACHE), $this->command->getExecuteEvents());
    }

    /**
     * Test case for cache miss scenario
     */
    public function testRequestCacheMiss() {
        $this->setUpCommonExpectations();
        $requestCache = $this->getMock('Luomor\Hystrix\RequestCache');
        $requestCache->expects($this->once())
            ->method('exists')
            ->with('Tests\Luomor\Hystrix\CommandMock', 'test-cache-key')
            ->will($this->returnValue(false));
        $requestCache->expects($this->never())
            ->method('get');
        $requestCache->expects($this->once())
            ->method('put')
            ->with('Tests\Luomor\Hystrix\CommandMock', 'test-cache-key', 'run result');
        $this->command->cacheKey = 'test-cache-key';
        $this->command->setRequestCache($requestCache);
        $this->commandMetrics->expects($this->never())->method('markResponseFromCache');
        $this->assertEquals('run result', $this->command->execute());
        $this->assertEquals(array('SUCCESS'), $this->command->getExecuteEvents());
    }

    public function testSavesRequestToCache() {
        $this->setUpCommonExpectations();
        /** @var RequestCache|\PHPUnit_Framework_MockObject_MockObject $requestCache */
        $requestCache = $this->getMock('Luomor\Hystrix\RequestCache');
        $requestCache->expects($this->once())
            ->method('put')
            ->with('Tests\Luomor\Hystrix\CommandMock', 'test-cache-key', 'run result');
        $this->command->cacheKey = 'test-cache-key';
        $this->command->setRequestCache($requestCache);
        $this->assertEquals('run result', $this->command->execute());
    }

    public function testRequestCacheDisabled() {
        $this->setUpCommonExpectations();
        $this->command->setConfig(new Config(array('requestCache' => array('enabled' => false))));
        /** @var RequestCache|\PHPUnit_Framework_MockObject_MockObject $requestCache */
        $requestCache = $this->getMock('Luomor\Hystrix\RequestCache');
        $requestCache->expects($this->never())
            ->method('get');
        $requestCache->expects($this->never())
            ->method('put');
        $this->command->cacheKey = 'test-cache-key';
        $this->command->setRequestCache($requestCache);
        $this->assertEquals('run result', $this->command->execute());
    }

    public function testRequestCacheGetCacheKeyNotImplemented() {
        $this->setUpCommonExpectations();
        /** @var RequestCache|\PHPUnit_Framework_MockObject_MockObject $requestCache */
        $requestCache = $this->getMock('Luomor\Hystrix\RequestCache');
        $requestCache->expects($this->never())
            ->method('get');
        $requestCache->expects($this->never())
            ->method('put');
        $this->command->setRequestCache($requestCache);
        $this->assertEquals('run result', $this->command->execute());
    }
}