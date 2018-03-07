<?php

class HeliumConsole extends He2App {

	protected static $_registry = null;

	protected static $_request = null;

	public static function init() {

		if (self::_hasAdapter(get_called_class(), __FUNCTION__))

			return self::_callAdapter(get_called_class(), __FUNCTION__);

		spl_autoload_register('He2App::loadModels');

		spl_autoload_register('He2App::loadComponents');

		spl_autoload_register('HeliumConsole::loadCommandLine');

		self::_initRegistry();

		self::_initRouter();

		self::_initTemplate();

		self::_notify(get_class() . '::' . __FUNCTION__);

		self::_notify(get_called_class() . '::' . __FUNCTION__);


		$args = PVCli::parse($argv = null);
		
		if($args[0] == 'controller') {
			$controller = $args['controller'].'Controller.php';
	
			$class = $args['controller'].'Controller';
			
			$action = (isset($args['action'])) ? $args['action'] : 'index';
			
			include(SITE_PATH . '/controllers'.DS. $controller);
			
			$object = new $class(array(), array());
			
			$object -> $action();
			
		} else {
			$class = array_shift($args);
			$object = new $class();
			
			if(isset($args[0])) {
				$function = array_shift($args);
				call_user_func_array(array($object,$function), $args);
			}
		}

	}

	public static function loadCommandLine($class) {

		$filename = $class . '.php';

		$file = SITE_PATH . 'cli' . DS . $filename;

		if (!file_exists($file)) {

			return false;

		}

		require_once $file;

		return true;

	}

}
