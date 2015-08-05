<?php namespace Mreschke\Keystone\Http\Controllers;

use Request;
use Parsedown;
use Mreschke\Helpers\Guest;
use Mreschke\Api\Server;
use Mreschke\Keystone\KeystoneInterface;
use Laravel\Lumen\Routing\Controller as Controller;

class ServerController extends Controller {

	protected $api;
	protected $keystone;


	public function __construct(Server $api, KeystoneInterface $keystone)
	{
		$this->api = $api;
		$this->keystone = $keystone;
	}

	/**
	 * Show the readme
	 * @return Response
	 */
	public function index()
	{
		$browser = Guest::getBrowser();
		$isCurl = preg_match("/curl/i", $browser);

		$content = $this->keystone->readme();
		return $isCurl ? $content : view('keystone::server.index', compact('content'));
	}

	protected function authorized()
	{
		return $this->api->verify(
			Request::header('Authorization'),
			Request::method(),
			Request::url()
		);
	}

	public function namespaces()
	{
		if ($client = $this->authorized()) {


			#return response()->json($this->keystone->namespaces());
			#test
			$x = $this->keystone->namespaces();
			$x[] = 'added by rest serve';

			$response = [
				'data' => $x,
				'links' => ['link one', 'link two'],
				'client' => $client
			];

			

		} else {
			$response = [
				'error' => 'blah'

			];
		}

		return response()->json($response);



	}


	public function keys()
	{
		return response()->json($this->keystone->ns('dynatron/vfi')->keys());
	}

	public function get($key)
	{
		if (!str_contains($key, '::')) {
			// Convert / path into :: path
			$tmp = explode('/', $key);
			$ns = $tmp[0].'/'.$tmp[1];
			array_shift($tmp); array_shift($tmp);
			$path = implode(':', $tmp);
			$key = "$ns::$path";
		}

		//ex: http://keystone.xendev1.dynatronsoftware.com/dynatron/vfi::client:5975:attributes
		//ex: http://keystone.xendev1.dynatronsoftware.com/dynatron/metric::ebis-lbr-gp-perc:info
		return response()->json(
			$this->keystone->get($key)
		);
	}

}