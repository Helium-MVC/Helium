<?php

class Router extends PVStaticInstance {
	
	private $registry;

	private $path;

	private $args = array();

	public $file;

	public $controller;

	public $action;

	public function __construct($registry) {
		$this -> registry = $registry;
	}

	public function setPath($path) {

		if (is_dir($path) == false) {
			throw new Exception('Invalid controller path: `' . $path . '`');
		}
		
		$this -> path = $path;
	}

	/**
	 * Calls a controller based on the route and then passes the variables retrieved from the controller
	 * to a view.
	 * 
	 * @return void
	 * @access public
	 */
	public function loader() {
		
		$this -> getController();

		if (is_readable($this -> file) == false) {
			$this -> file = $this -> path . '/error404.php';
			$this -> controller = 'error404';
		}

		include $this -> file;

		$class = $this -> controller . 'Controller';
		$controller = new $class($this -> registry);

		if (is_callable(array($controller, $this -> action)) == false) {
			$action = 'index';
		} else {
			$action = $this -> action;
		}
		$vars = $controller -> $action();
		
		$this -> parseControllerVars($vars);
		
		$template = $controller -> getTemplate();
		$layout = $controller -> getView();
		
		$template_defaults = array('view' => $this->controller, 'prefix' => $this->action);
		$template += $template_defaults;

		$this -> registry -> template -> show($template, $layout);
	}
	
	/**
	 * Parse the variables retrieved from  the controllers and adds them to the registry.
	 * The registray will pass the bariables to an array.
	 * 
	 * @param array $vars The variables retrieved from the controller and assed to the template registry
	 * 
	 * @return void
	 * @access prviate
	 */
	private function parseControllerVars($vars) {
		
		if(is_array($vars)) {
			foreach($vars as $key => $value) {
				$this -> registry -> template -> $key = $value;
			}
		}
	}

	/**
	 * Gets the controller based on the route and also sets the action retrieved from the route.
	 * 
	 * @return void
	 * @access private
	 */
	private function getController() {

		PVRouter::setRoute();
		$route = PVRouter::getRoute();
		$this -> controller = (empty($route['controller'])) ? PVRouter::getRouteVariable('controller') : $route['controller'];
		$this -> action = (empty($route['action'])) ? PVRouter::getRouteVariable('action') : $route['action'];

		if (empty($this -> controller)) {
			$this -> controller = 'index';
		}
		
		if (empty($this -> action)) {
			$this -> action = 'index';
		}
		
		$this -> file = $this -> path . '/' . $this -> controller . 'Controller.php';
	}

}
?>
