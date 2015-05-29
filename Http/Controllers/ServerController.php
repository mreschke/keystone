<?php namespace Mreschke\Keystone\Http\Controllers;

use Parsedown;
use Mreschke\Helpers\Guest;
use Mreschke\Keystone\KeystoneInterface;
use Mreschke\Keystone\Http\Controllers\Controller;

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
		if ($isCurl) {
			return $content;
		} else {
			return "
			<html>
				<head>
					<link href='https://bootswatch.com/simplex/bootstrap.min.css' rel='stylesheet'>
				</head>
				<body style='margin: 50px 15px 15px 15px'>
					<div class='container'>
						<div class='panel panel-default'>
							<div class='panel-heading'>README.md</div>
							<div class='panel-body'>
								".Parsedown::instance()->text($content)."	
							</div>
						</div>
					</div>
				</body>
			</html>
			";

		}
	}

	public function namespaces()
	{
		return response()->json($this->keystone->namespaces());
	}

	public function keys()
	{
		return response()->json($this->keystone->ns('dynatron/vfi')->keys());
	}

	public function get()
	{
		return response()->json(
			$this->keystone->ns('dynatron/vfi')->get('client:5975:attributes')
		);
	}

}