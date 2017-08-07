<?php namespace Mreschke\Keystone;

/**
 * Provides a contractual interface for Keystone Connection implementations
 * @copyright 2014 Matthew Reschke
 * @license http://mreschke.com/license/mit
 * @author Matthew Reschke <mail@mreschke.com>
*/
interface ConnectionInterface
{

    /**
     * Get unserialized value from keystone
     * If $index, get value by index (assoc, object...)
     * @param  string $key
     * @param  mixed $index = null optionally pluck by subkey
     * @return mixed
     */
    #public function get($key, $index = null);

    /**
     * Get file information if key is in filesystem
     * @param  string $key
     * @return mixed
     */
    #public function fileInfo($key);

    /**
     * Get the first value from a list
     * @param  $key
     * @return mixed
     */
    #public function first($key);

    /**
     * Get the last value from a list
     * @param  $key
     * @return mixed
     */
    #public function last($key);

    /**
     * Pluck single/multi value from an associative array, single array
     * Works with serialized values too
     * @param  $key
     * @param  mixed $index
     * @return mixed
     */
    #public function pluck($key, $index);

    /**
     * Remove and get the first element in a list (LPOP)
     * @param  $key
     * @return mixed
     */
    #public function shift($key);

    /**
     * Remove and get the last element in a list (RPOP)
     * @param  $key
     * @return mixed
     */
    #public function pop($key);

    /**
     * Get a range from an array
     * @param  $key
     * @param  $start starts on 0, can be negative
     * @param  $end can use negative numbers
     * @return mixed
     */
    #public function range($key, $start = 0, $end = -1);

    /**
     * Put a value in keystone (automatic redis or file backend based on type and size)
     * Lists and hashes are always stored in redis regardless of size since speed is paramount
     * Strings will be sent to the filesystem of over a designated size limit
     * @param string $key
     * @param mixed $value
     * @param boolean $serialize = false
     */
    #public function put($key, $value, $serialize = false);

    /**
     * Add value to keystone only if key does not already exists
     * @param string $key
     * @param mixed $value
     * @param mixed $serialize = false
     * @return boolean
     */
    #public function add($key, $value, $serialize = false);

    /**
     * Put a serialized value in keystone
     * @param  string $key
     * @param  mixed $value
     * @return void
     */
    #public function serialize($key, $value);

    /**
     * Append to array or string
     * If string was serialized object or array, append then re-serialize
     * @param  $key
     * @param  string|assoc $value
     */
    #public function push($key, $value);

    /**
     * Increment an integer by a number
     * @param  $key
     * @param  integer $increment = 1
     * @return integer new value
     */
    #public function increment($key, $increment = 1);

    /**
     * Remove an entire key, or items in a key from keystone
     * @param  string $key
     */
    #public function forget($key, $index = null);

    /**
     * Check if an entire key, or a key index (hash, object) exists
     * @param  string $key
     * @param  string $index = null
     * @return boolean
     */
    #public function exists($key, $index = null);

    /**
     * Get stored object type
     * @param  string $key
     * @return string
     */
    #public function type($key);

    /**
     * Get all keys in the current ns
     * @param  string $filter
     * @return array
     */
    #public function keys($filter = '*');

    /**
     * Get all values of all keys in the current namespace
     * @param  string  $filter
     * @param  string $index = null
     * @param  string $value = null return value if index result matches value
     * @return mixed
     */
    #public function where($filter = '*', $index = null, $value = null);
    #public function values($filter = '*', $index = null, $value = null);


    /**
     * Get all keystone namespaces
     * @return array
     */
    #public function namespaces();

    /**
     * Show the readme files
     * @return string
     */
    #public function readme();

    /**
     * Set the keystone namespace
     * @param  string $ns
     * @return self
     */
    #public function ns($ns);

    /**
     * Return this instance
     * @return self
     */
    #public function getInstance();
}
