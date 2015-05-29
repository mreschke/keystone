<?php namespace Mreschke\Keystone\Providers;

use Module;
use Illuminate\Support\ServiceProvider;

/**
 * Provides Keystone services
 * @copyright 2014 Matthew Reschke
 * @license http://mreschke.com/license/mit
 * @author Matthew Reschke <mail@mreschke.com>
*/
class KeystoneServiceProvider extends ServiceProvider {

	/**
	 * Indicates if loading of the provider is deferred.
	 *
	 * @var bool
	 */
	protected $defer = true;

	/**
	 * Bootstrap the application events.
	 *
	 * @return void
	 */
	public function boot()
	{
		$app = $this->app;
		$app->group(['namespace' => 'Mreschke\Keystone\Http\Controllers'], function($app) {
			require __DIR__.'/../Http/routes-server.php';
		});
		
	}

	/**
	 * Register the service provider.
	 *
	 * @return void
	 */
	public function register()
	{
		// Bind Aliases
		$this->app->alias('Mreschke\Keystone\Keystone', 'Mreschke\Keystone');
		$this->app->alias('Mreschke\Keystone\Keystone', 'Mreschke\Keystone\KeystoneInterface');

		// Register our Artisan Commands
		$this->commands('Mreschke\Keystone\Console\Commands\KeystoneCommand');		
	}

	/**
	 * Get the services provided by the provider.
	 *
	 * @return array
	 */
	public function provides()
	{
		// All deferred providers must include this provides() array
		return array('Mreschke\Keystone\Keystone');
	}

}
