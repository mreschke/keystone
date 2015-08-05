<?php namespace Mreschke\Keystone;

use Mreschke\Api\Client;
use InvalidArgumentException;

/**
 * Keystone Manager
 * @copyright 2014 Matthew Reschke
 * @license http://mreschke.com/license/mit
 * @author Matthew Reschke <mail@mreschke.com>
*/
class Keystone implements KeystoneInterface
{

	protected $app;	

	/**
	 * Active connections
	 * @var array
	 */
	protected $connections = array();


	/**
	 * Creata a new keystone manager instance
	 */
	public function __construct($app)
	{
		$this->app = $app;
	}

	/**
	 * Get a keystone connection instance
	 * @param  string $name connection name
	 * @return \Mreschke\Keystone\Connection
	 */
	public function connection($name = null)
	{
		$name = $name ?: $this->getDefaultConnection();
		if (!isset($this->connections[$name])) {
			$this->connections[$name] = $this->makeConnection($name);
		}
		return $this->connections[$name];
	}

	protected function makeConnection($name)
	{
		$config = $this->getConfig($name);
		switch ($config['driver']) {
			case 'native':
				return new NativeConnection($config, new Metadata($config));
			case 'http':
				return new HttpConnection($config, new Client(
					$config['vendor'],
					$config['api_url'],
					$config['api_version'],
					$config['api_key'],
					$config['api_secret']
				));
		}
	}

	/**
	 * Get the configuration for a connection
	 * @param  string $name connection name
	 * @return array
	 * @throws \InvalidArgumentException
	 */
	protected function getConfig($name)
	{
		$connections = $this->app['config']['mreschke.keystone.connections'];
		if (is_null($config = array_get($connections, $name))) {
			throw new InvalidArgumentException("Connection [$name] not configured.");
		}
		return $config;
	}

	/**
	 * Get the default connection name
	 * @return string
	 */
	protected function getDefaultConnection()
	{
		return $this->app['config']['mreschke.keystone.default'];
	}

	/**
	 * Dynamically pass methods to the default connection.
	 * @param  string  $method
	 * @param  array   $parameters
	 * @return mixed
	 */
	public function __call($method, $parameters)
	{
		return call_user_func_array(array($this->connection(), $method), $parameters);
	}

}