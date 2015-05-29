<?php namespace Mreschke\Keystone\Http\Controllers;

use Parsedown;
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
		return Parsedown::instance()->text($this->keystone->readme());
	}

	public function namespaces()
	{
		return json_encode($this->keystone->namespaces());
	}

}