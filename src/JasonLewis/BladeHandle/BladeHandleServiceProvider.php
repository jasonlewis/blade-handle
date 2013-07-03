<?php namespace JasonLewis\BladeHandle;

use Illuminate\Support\ServiceProvider;
use Illuminate\View\Engines\CompilerEngine;

class BladeHandleServiceProvider extends ServiceProvider {

	/**
	 * Indicates if loading of the provider is deferred.
	 *
	 * @var bool
	 */
	protected $defer = false;

	/**
	 * Register the service provider.
	 *
	 * @return void
	 */
	public function register()
	{
		$app = $this->app;

		$this->app['view.engine.resolver']->register('blade', function() use ($app)
		{
			$cache = $app['path.storage'].'/views';

			$compiler = new BladeCompiler($app['files'], $cache);

			return new CompilerEngine($compiler, $app['files']);
		});
	}

	/**
	 * Get the services provided by the provider.
	 *
	 * @return array
	 */
	public function provides()
	{
		return array();
	}

}