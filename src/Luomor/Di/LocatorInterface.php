<?php
/**
 * Created by PhpStorm.
 * User: peterzhang
 * Date: 01/06/2018
 * Time: 6:24 PM
 */
namespace Luomor\Di;

interface LocatorInterface {
    /**
     * Retrieve a class instance
     *
     * @param string $name Class name or service name
     * @param null|array $params Parameters to be used when instantiating a new instance of $name
     * @return object|null
     */
    public function get($name, array $params = array());
}