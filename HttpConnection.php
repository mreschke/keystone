<?php namespace Mreschke\Keystone;

use Mreschke\Api\Client;

/**
 * Http Keystone Connection
 * @copyright 2014 Matthew Reschke
 * @license http://mreschke.com/license/mit
 * @author Matthew Reschke <mail@mreschke.com>
*/
class HttpConnection implements ConnectionInterface
{
	protected $config;
	protected $api;

	public function __construct($config, Client $api)
	{
		$this->config = $config;
		$this->api = $api;
	}

	public function namespaces()
	{
		$response = json_decode($this->api->get('/namespaces'));

		#dd($response);
		
		if (isset($response->data)) {
			return $response->data;
		}
	}

}
