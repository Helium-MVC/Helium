<?php

namespace prodigyview\helium;

use prodigyview\util\Collection;
use prodigyview\design\StaticInstance;

/**
 * The main application for instantiaing the He2MVC Framework and bringing
 * together the parts required for the system to work.
 *
 * The application is what is called with Helium is first initiliaed in the frontend controller. It
 * will autoload the components, set the registry and then send the application into the router. The boostrap 
 * of the framework should be called sometime during this point.
 *
 * @package prodigyview\helium
 */
class He2App {

	use StaticInstance;
	
	/**
	 * The global registry
	 */
	protected static $_registry = null;

	/**
	 * The request and headers for the current url
	 */
	protected static $_request = null;

	/**
	 * Initializes the application to start Helium
	 */
	public static function init() {

		if (self::_hasAdapter(static::class, __FUNCTION__))
			return self::_callAdapter(static::class, __FUNCTION__);

		spl_autoload_register('prodigyview\helium\He2App::loadNamespacedComponents');
		spl_autoload_register('prodigyview\helium\He2App::loadNormalComponents');

		self::_initRegistry();
		self::_initRouter();
		self::_initTemplate();

		self::$_registry->router->loader();

		self::_notify(self::class . '::' . __FUNCTION__);
		self::_notify(static::class . '::' . __FUNCTION__);
	}

	/**
	 * Will create a system wide registry that is passed to all other components
	 */
	protected static function _initRegistry() {

		if (self::_hasAdapter(static::class, __FUNCTION__))
			return self::_callAdapter(static::class, __FUNCTION__);

		/*** a new registry object ***/
		self::$_registry = new He2Registry;

		self::$_request = new Collection($_REQUEST);

		if (isset($_POST)) {
			self::$_registry->post = $_POST;
		}

		if (isset($_REQUEST)) {
			self::$_registry->request = $_REQUEST;
		}

		if (isset($_GET)) {
			self::$_registry->get = $_GET;
		}

		if (isset($_FILES)) {
			self::$_registry->files = $_FILES;
		}

		self::_notify(self::class . '::' . __FUNCTION__);
		self::_notify(static::class . '::' . __FUNCTION__);
	}

	/**
	 * Will low the router for incoming requests
	 */
	protected static function _initRouter() {

		if (self::_hasAdapter(static::class, __FUNCTION__))
			return self::_callAdapter(static::class, __FUNCTION__);

		/*** load the router ***/
		self::$_registry->router = new He2Router(self::$_registry);

		/*** set the controller path ***/
		self::$_registry->router->setPath(SITE_PATH . '/controllers');

		self::_notify(self::class . '::' . __FUNCTION__);
		self::_notify(static::class . '::' . __FUNCTION__);

	}

	/**
	 * Will load the templating engine
	 */
	protected static function _initTemplate() {

		if (self::_hasAdapter(static::class, __FUNCTION__))
			return self::_callAdapter(static::class, __FUNCTION__);

		/*** load up the template ***/
		self::$_registry->template = new He2Template(self::$_registry, self::$_request);

		self::_notify(self::class . '::' . __FUNCTION__);
		self::_notify(static::class . '::' . __FUNCTION__);
	}

	/**
	 * With autoload the components that are namespaced
	 *
	 * @param string $class The name of the class to be loaded
	 *
	 * @return void
	 */
	public static function loadNamespacedComponents($class) {
		
		$class = str_replace('\\', '/', $class);
		$filename = $class . '.php';
		$file = PV_ROOT . DS . $filename;

		if (!file_exists($file)) {
			return false;
		}
		
		require_once $file;
		
		return true;
	}

	/**
	 * Will autoload the components that do not have a namespace
	 *
	 * @param string $class The name of the class to be loaded
	 *
	 * @return void
	 */
	public static function loadNormalComponents($class) {
		
		$filename = $class . '.php';
		$file = SITE_PATH . 'extensions' . DS . 'components' . DS . $filename;

		if (!file_exists($file)) {
			return false;
		}
		
		require_once $file;
		
		return true;
	}

}
