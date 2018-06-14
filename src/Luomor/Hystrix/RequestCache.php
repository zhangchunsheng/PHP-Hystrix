<?php
/**
 * Created by PhpStorm.
 * User: peterzhang
 * Date: 11/06/2018
 * Time: 6:09 PM
 */
namespace Luomor\Hystrix;

/**
 * Class RequestCache
 * Object for request caching, one instance shared between all commands
 *
 * @package Luomor\Hystrix
 */
class RequestCache {
    /**
     * Associative array of results per command key per cache key
     * @var array
     */
    protected $cachedResults = array();

    /**
     * Clears the cache for a given commandKey only
     *
     * @param string $commandKey
     */
    public function clearAll($commandKey) {
        if(isset($this->cachedResults[$commandKey])) {
            unset($this->cachedResults[$commandKey]);
        }
    }

    /**
     * Clears the cache for a given cacheKey, for a given commandKey
     *
     * @param string $commandKey
     * @param string $cacheKey
     */
    public function clear($commandKey, $cacheKey) {
        if($this->exists($commandKey, $cacheKey)) {
            unset($this->cachedResults[$commandKey][$cacheKey]);
        }
    }

    /**
     * Attempts to obtain cached result for a given command type
     *
     * @param string $commandKey
     * @param string $cacheKey
     * @return mixed|null
     */
    public function get($commandKey, $cacheKey) {
        if($this->exists($commandKey, $cacheKey)) {
            return $this->cachedResults[$commandKey][$cacheKey];
        }

        return null;
    }

    /**
     * Puts request result into cache for a given command type
     *
     * @param string $commandKey
     * @param string $cacheKey
     * @param mixed $result
     */
    public function put($commandKey, $cacheKey, $result) {
        $this->cachedResults[$commandKey][$cacheKey] = $result;
    }

    /**
     * Returns true, if specified cache key exists
     *
     * @param string $commandKey
     * @param string $cacheKey
     * @return bool
     */
    public function exists($commandKey, $cacheKey) {
        return array_key_exists($commandKey, $this->cachedResults)
            && array_key_exists($cacheKey, $this->cachedResults[$commandKey]);
    }
}