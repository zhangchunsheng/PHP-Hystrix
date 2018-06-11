<?php
/**
 * Created by PhpStorm.
 * User: peterzhang
 * Date: 11/06/2018
 * Time: 5:36 PM
 */
namespace Luomor\Hystrix\Exception;

/**
 * General Hystrix exception
 * @package Luomor\Hystrix\Exception
 */
class RuntimeException extends \RuntimeException {
    /**
     * Exception while retrieving the fallback, if enabled
     *
     * @var \Exception
     */
    private $fallbackException;

    /**
     * Class name of the command
     *
     * @var string
     */
    private $commandClass;

    /**
     * RuntimeException constructor.
     * @param string $message
     * @param int $commandClass
     * @param \Exception|null $originalException (Optional) Original exception. May be null if short-circuited
     * @param \Exception|null $fallbackException (Optional) Exception thrown while retrieving fallback
     */
    public function __construct(
        $message,
        $commandClass,
        \Exception $originalException = null,
        \Exception $fallbackException = null
    ) {
        parent::__construct($message, 0, $originalException);
        $this->fallbackException = $fallbackException;
        $this->commandClass = $commandClass;
    }

    /**
     * Returns class name of the command the exception was throw from
     *
     * @return int|string
     */
    public function getCommandClass() {
        return $this->commandClass;
    }

    /**
     * Returns fallback exception if available
     *
     * @return \Exception|null
     */
    public function getFallbackException() {
        return $this->fallbackException;
    }
}