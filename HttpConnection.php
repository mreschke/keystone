<?php namespace Mreschke\Keystone;

use GuzzleHttp\ClientInterface as Guzzle;

/**
 * Http Keystone Connection
 * @copyright 2014 Matthew Reschke
 * @license http://mreschke.com/license/mit
 * @author Matthew Reschke <mail@mreschke.com>
*/
class HttpConnection implements ConnectionInterface
{

	protected $client;

	public function __construct($config, Guzzle $client)
	{
		$this->client = $client;
	}

	public function namespaces()
	{
		$data = $this->client->get('namespaces');
		if ($data->getStatusCode() == 200) {
			
			$x = json_decode($data->getBody());

			//tmp
			$x[] = 'added by http client';
			
			return $x;
		}
	}

}
