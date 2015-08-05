<?php namespace Mreschke\Keystone\Http\Controllers;

use Mreschke\Keystone\KeystoneInterface;
use Mreschke\Keystone\Http\Controllers\Controller;

class KeystoneController extends Controller {

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
		return view('keystone::index');
	}

}