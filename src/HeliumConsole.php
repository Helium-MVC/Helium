<?php
namespace prodigyview\helium;

use prodigyview\helium\He2App;
use prodigyview\util\Cli;

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
class HeliumConsole extends He2App {

	/**
	 * The global registry
	 */
	protected static $_registry = null;

	/**
	 * The request from the cli
	 */
	protected static $_request = null;

	/**
	 * Initializes the console and looks for commands to run.
	 */
	public static function init($options = array()) {

		$defaults = array(
			'load_routing' => true,
			'load_registry' => true,
			'load_templating' => true,
		);

		$options += $defaults;

		if (self::_hasAdapter(static::class, __FUNCTION__))
			return self::_callAdapter(static::class, __FUNCTION__);

		spl_autoload_register('prodigyview\helium\He2App::loadNamespacedComponents');
		spl_autoload_register('prodigyview\helium\He2App::loadNormalComponents');

		spl_autoload_register('prodigyview\helium\HeliumConsole::loadCommandLine');

		if($options['load_registry']) {
			self::_initRegistry();
		}

		if($options['load_routing']) {
			self::_initRouter();
		}

		if($options['load_templating']) {
			self::_initTemplate();
		}

		self::_notify(self::class . '::' . __FUNCTION__);

		self::_notify(static::class . '::' . __FUNCTION__);

		$args = Cli::parse($argv = null);

		if (isset($args[0]) && $args[0] == 'controller') {
			
			$controller = $args['controller'] . 'Controller.php';

			$class = $args['controller'] . 'Controller';

			$action = (isset($args['action'])) ? $args['action'] : 'index';

			include (SITE_PATH . '/controllers' . DS . $controller);

			$object = new $class( array(), array());

			$object->$action();

		} else if($args) {
			
			$class = array_shift($args);

			if(class_exists($class)) {
				$object = new $class();

				if (isset($args[0])) {
					$function = array_shift($args);
					call_user_func_array(array(
						$object,
						$function
					), $args);
				}
			}
		}

	}

	/**
	 * The autoload for finding a class in the cli folder
	 * and executing it based on the arguements passed.
	 *
	 * @param string $class The name of the class
	 *
	 * @return void
	 */
	public static function loadCommandLine($class) {
			
		if (self::_hasAdapter(static::class, __FUNCTION__))
			return self::_callAdapter(static::class, __FUNCTION__, $class);

		$filename = $class . '.php';

		$file = SITE_PATH . 'cli' . DS . $filename;

		if (!file_exists($file)) {

			return false;

		}

		require_once $file;

		return true;

	}

}
