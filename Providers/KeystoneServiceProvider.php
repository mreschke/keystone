<?php namespace Mreschke\Keystone\Providers;

use Mreschke\Keystone\Keystone;
use GuzzleHttp\Client as Guzzle;
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
		// Define publishing rules
		$this->definePublishing();

		$isServer = $this->app['config']['mreschke.keystone.server'];

		if ($isServer) {
			$app = $this->app;
			$app->group(['namespace' => 'Mreschke\Keystone\Http\Controllers'], function($app) {
				require __DIR__.'/../Http/routes-server.php';
			});
		}

		// Load Views
		$this->loadViewsFrom(__DIR__.'/../Views', 'keystone');
		
	}

	/**
	 * Register the service provider.
	 *
	 * @return void
	 */
	public function register()
	{
		// Bind
		$this->app->singleton('Mreschke\Keystone\Keystone', function() {
			return new Keystone($this->app);
		});

		// Bind aliases
		$this->app->alias('Mreschke\Keystone\Keystone', 'Mreschke\Keystone');
		$this->app->alias('Mreschke\Keystone\Keystone', 'Mreschke\Keystone\KeystoneInterface');

		// Merge config
		$this->mergeConfigFrom(__DIR__.'/../Config/keystone.php', 'mreschke.keystone');

		// Register our Artisan Commands
		$this->commands('Mreschke\Keystone\Console\Commands\KeystoneCommand');		
	}

	/**
	 * Define publishing rules
	 * 
	 * @return void
	 */
	private function definePublishing()
	{
		# App base path
		$path = realpath(__DIR__.'/../');

		// Config publishing rules
		// ./artisan vendor:publish --tag="mreschke.keystone.configs"
		$this->publishes([
			"$path/Config" => base_path('/config/mreschke'),
		], 'mreschke.keystone.configs');

		// Seed publishing rules
		// ./artisan vendor:publish --tag="mrcore.wiki.seeds"
		/*$this->publishes([
			"$path/Database/Seeds" => base_path('/database/seeds'),
		], 'mrcore.wiki.seeds');*/

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
