<?php namespace Mreschke\Keystone;

/**
 * Provides a contractual interface for Mreschke\Keystone implementations
 * @copyright 2014 Matthew Reschke
 * @license http://mreschke.com/license/mit
 * @author Matthew Reschke <mail@mreschke.com>
*/
interface KeystoneInterface
{

    /**
     * Get a keystone connection instance
     * @param  string $name connection name
     * @return \Mreschke\Keystone\Connection
     */
    public function connection($name = null);

    /**
     * Dynamically pass methods to the default connection.
     * @param  string  $method
     * @param  array   $parameters
     * @return mixed
     */
    public function __call($method, $parameters);
}
