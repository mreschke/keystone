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

	protected $config;
	protected $client;

	public function __construct($config, Guzzle $client)
	{
		$this->config = $config;
		$this->client = $client;
	}

	public function namespaces()
	{
		#$publicKey = dd(uniqid());


		#dd(\Hash::make(uniqid(rand(), true))); #60 chars
		$secret = '$2y$10$5/iofPrVi0g/y0NWRwKme.vjUiySz8W6gKTfkq/xJn4Wjr0YK.WO2';


		#c3b2c3edb9a731abb0ef7b421e346528eeb34ecf #40 chars
		#dd(sha2(uniqid(rand(), true)));

		#dd(\Mreschke\Helpers\String::getGuid());
		$apiKey = '1f79bbdf-8c8d-c0de-5bc1-fcc36ef5c5cf';



		#dd($this->config);


		$url = $this->config['url'];
		$uri = 'namespaces';
		$data = '';

		// Hmac Signature
		// http://docs.aws.amazon.com/AmazonS3/latest/dev/RESTAuthentication.html
		$stringToSign = "$url/$uri/".md5($uri.$data).md5(gmdate("Ymd"));
		$signature = base64_encode(hash_hmac('sha1', $stringToSign, $secret));
		#$signature = base64_encode($stringToSign);

		$data = $this->client->get($uri, [
			'headers' => [
				'Authorization' => "$apiKey:$signature",
				'Accept' => 'application/json',
			]
		]);

		if ($data->getStatusCode() == 200) {
			
			$x = json_decode($data->getBody());

			//tmp
			$x[] = 'added by http client';
			$x[] = $stringToSign;
			
			return $x;
		}
	}

}
