<?php namespace Mreschke\Keystone\Http\Controllers;

use Parsedown;
use Mreschke\Helpers\Guest;
use Mreschke\Keystone\KeystoneInterface;
use Laravel\Lumen\Routing\Controller as Controller;

class ServerController extends Controller {

	protected $keystone;

	public function __construct(KeystoneInterface $keystone)
	{
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

	public function namespaces()
	{
		list($apiKey, $signature) = explode(':', \Request::header('Authorization'));

		$usersSecret = '$2y$10$5/iofPrVi0g/y0NWRwKme.vjUiySz8W6gKTfkq/xJn4Wjr0YK.WO2';

		$url = 'http://keystone.xendev1.dynatronsoftware.com';
		$uri = 'namespaces';

		$data = ''; # get payload


		$stringToSign = "$url/$uri/".md5($uri.$data).md5(gmdate("Ymd"));
		$rebuild = base64_encode(hash_hmac('sha1', $stringToSign, $usersSecret));
		#$rebuild = $stringToSign;


		if ($signature == $rebuild) {
			// API Access Granted for user of $apiKey

			#return response()->json($this->keystone->namespaces());
			#test
			$x = $this->keystone->namespaces();
			$x[] = 'added by rest serve';
			$x[] = $apiKey;
			$x[] = $signature;
			$x[] = $rebuild;

			return response()->json($x);

		}


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