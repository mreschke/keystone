<?php namespace Mreschke\Keystone;

use Predis\Client as Predis;

/**
 * Keystone Native Metadata
 * @copyright 2014 Matthew Reschke
 * @license http://mreschke.com/license/mit
 * @author Matthew Reschke <mail@mreschke.com>
*/
class Metadata
{
    protected $redis;
    protected $prefix;
    protected $ns;

    public function __construct($config)
    {
        $this->redis = new Predis([
            'scheme' => 'tcp',
            'host' => $config['host'],
            'password' => $config['password'],
            'port' => $config['port'],
            'database' => $config['database']
        ]);
        $this->prefix = $config['prefix'];
        $this->ns = $config['metadata_namespace'];
    }

    /**
     * Put a key in meta (will not add duplicates because uses redis sets)
     * @param  string $key keystone key
     * @param  boolean $inRedis true of in redis, false if in file backend
     * @return void
     */
    public function put($key, $inRedis = true)
    {
        $metaKey = $this->prefix.$this->ns.'::keys';
        $ns = $this->nsFromKey($key);

        // Add to list of all keys
        $this->redis->sadd("${metaKey}:all", $key);

        // Add namespace to list of all namespaces ever used
        $this->redis->sadd($this->prefix.$this->ns."::namespaces", $ns);

        // Add to list of keys by namespace
        $this->redis->sadd("${metaKey}:ns:$ns", $key);

        if ($inRedis) {
            // Add to list of keys in redis backend
            $this->redis->sadd("${metaKey}:redis", $key);
        } else {
            // Add to list of keys in file backend
            $this->redis->sadd("${metaKey}:file", $key);
        }
    }

    /**
     * Remove a key from meta
     * @param  string $key keystone key
     */
    public function forget($key)
    {
        $metaKey = $this->prefix.$this->ns.'::keys';
        $ns = $this->nsFromKey($key);

        // Forget a key in meta from list of all keys
        $this->redis->srem("${metaKey}:all", $key);

        // Forget a key in meta from list of all keys by namespace
        $this->redis->srem("${metaKey}:ns:$ns", $key);

        // Forget a key in meta from the redis backend
        $this->redis->srem("${metaKey}:redis", $key);

        // Forget a key in meta from the file backend
        $this->redis->srem("${metaKey}:file", $key);
    }

    /**
     * Parse the namespace from a key
     * @param  string $key
     * @return string
     */
    public function nsFromKey($key)
    {
        $ns = substr($key, strlen($this->prefix));
        $ns = substr($ns, 0, strpos($ns, "::"));
        return $ns;
    }
}
