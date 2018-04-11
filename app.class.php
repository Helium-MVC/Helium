<?php

class He2App extends PVStaticInstance {

	protected static $_registry = null;

	protected static $_request = null;

	public static function init() {
		
		if (self::_hasAdapter(get_called_class(), __FUNCTION__))
			return self::_callAdapter(get_called_class(), __FUNCTION__);
		
		spl_autoload_register('He2App::loadModels');
		spl_autoload_register('He2App::loadComponents');
		spl_autoload_register('He2App::loadTraits');
		spl_autoload_register('He2App::loadServices');

		self::_initRegistry();
		self::_initRouter();
		self::_initTemplate();
		
		self::$_registry -> router -> loader();
		
		self::_notify(get_class() . '::' . __FUNCTION__);
		self::_notify(get_called_class() . '::' . __FUNCTION__);
	}

	protected static function _initRegistry() {
		
		if (self::_hasAdapter(get_called_class(), __FUNCTION__))
			return self::_callAdapter(get_called_class(), __FUNCTION__);

		/*** a new registry object ***/
		self::$_registry = new He2Registry;

		self::$_request = new PVCollection($_REQUEST);

		if (isset($_POST)) {
			self::$_registry -> post = $_POST;
		}

		if (isset($_REQUEST)) {
			self::$_registry -> request = $_REQUEST;
		}

		if (isset($_GET)) {
			self::$_registry -> get = $_GET;
		}

		if (isset($_FILES)) {
			self::$_registry -> files = $_FILES;
		}

		self::_notify(get_class() . '::' . __FUNCTION__);
		self::_notify(get_called_class() . '::' . __FUNCTION__);
	}

	protected static function _initRouter() {
		
		if (self::_hasAdapter(get_called_class(), __FUNCTION__))
			return self::_callAdapter(get_called_class(), __FUNCTION__);

		/*** load the router ***/
		self::$_registry -> router = new He2Router(self::$_registry);

		/*** set the controller path ***/
		self::$_registry -> router -> setPath(SITE_PATH . '/controllers');
		
		self::_notify(get_class() . '::' . __FUNCTION__);
		self::_notify(get_called_class() . '::' . __FUNCTION__);

	}

	protected static function _initTemplate() {
		
		if (self::_hasAdapter(get_called_class(), __FUNCTION__))
			return self::_callAdapter(get_called_class(), __FUNCTION__);

		/*** load up the template ***/
		self::$_registry -> template = new He2Template(self::$_registry, self::$_request);
		
		self::_notify(get_class() . '::' . __FUNCTION__);
		self::_notify(get_called_class() . '::' . __FUNCTION__);
	}

	public static function loadModels($class) {
		$filename = $class . '.php';
		$file = SITE_PATH . 'models' . DS . $filename;

		if (!file_exists($file)) {
			return false;
		}
		require_once $file;
		return true;
	}

	public static function loadComponents($class) {
		$filename = $class . '.php';
		$file = SITE_PATH . 'extensions' . DS . 'components' . DS . $filename;

		if (!file_exists($file)) {
			return false;
		}
		require_once $file;
		return true;
	}

	public static function loadTraits($class) {
		$filename = $class . '.php';
		$file = SITE_PATH . 'extensions' . DS . 'traits' . DS . $filename;

		if (!file_exists($file)) {
			return false;
		}
		require_once $file;
		return true;
	}
	
	public static function loadServices($class) {
		$filename = $class . '.php';
		$file = SITE_PATH . 'services' . DS . $filename;

		if (!file_exists($file)) {
			return false;
		}
		require_once $file;
		return true;
	}

}
