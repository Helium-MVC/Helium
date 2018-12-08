<?php
/**
 * 
 * The router class is where the applications primary execution occurs. The class will take information
 * from the router, call the correct controller and also call the correct view.
 * 
 * @package prodigyview\helium
 */
namespace prodigyview\helium;

class He2Router extends \PVStaticInstance {
	
	private $registry;

	private $path;

	private $args = array();

	public $file;

	public $controller;

	public $action;

	/**
	 * Called with the Router is initalized.
	 * 
	 * @param object $registry Passes in the global registry
	 * 
	 * @return void
	 */
	public function __construct($registry) {
		$this -> registry = $registry;
	}
	
	/**
	 * Sets the path to controller folder to tell where the controllers
	 * should be found when being instantiated.
	 * 
	 * @param string $path The local path to the controllers folder
	 * 
	 * @return void
	 */
	public function setPath($path) {

		if (is_dir($path) === false) {
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
		
		if (self::_hasAdapter(get_called_class(), __FUNCTION__))
			return self::_callAdapter(get_called_class(), __FUNCTION__);
		
		$class = null;
		
		$controller = null;
		
		$this -> getController();

		if (!file_exists($this -> file) || is_readable($this -> file) === false) {
			$this -> file = $this -> path . '/errorController.php';
			@include $this -> file;
			
			if(class_exists('errorController')) {
				$controller = new \errorController($this -> registry);
			} else {
				throw new \Exception("No Error Controller exist. Please create errorController.");  
			}
		} else {
			
			include $this -> file;

			$class = $this -> controller . 'Controller';
			$controller = new $class($this -> registry);
		}
		
		if (method_exists ( $controller , $this -> action) === false) {
			$action = 'error404';
		} else {
			$action = $this -> action;
		}
		
		$vars = $this -> executeControllerAction($controller, $action);
		
		if($vars instanceof Redirect) {
			$vars -> executeRedirect();
		} else {
			$this -> renderTemplate($controller, $vars);
		}
		
		self::_notify(get_called_class() . '::' . __FUNCTION__, $this, $class, $controller, $vars);
	}

	/**
	 * Based on the parameters from the route, this function will execute the action method
	 * in the controller
	 * 
	 * @param object $controller an instance of the controller object
	 * @param string $action The action to call
	 * 
	 * @return mixed Either should return an array of elements from the controller, Redirect function or void
	 */
	public function executeControllerAction($controller, $action) {
			
		if (self::_hasAdapter(get_called_class(), __FUNCTION__))
			return self::_callAdapter(get_called_class(), __FUNCTION__, $controller, $action);
		
		$filtered = self::_applyFilter(get_class(), __FUNCTION__, array('controller' => $controller, 'action' => $action), array('event' => 'args'));
		$controller = $filtered['controller'];
		$action = $filtered['action'];
			
		return $controller -> $action();
			
		self::_notify(get_called_class() . '::' . __FUNCTION__, $this, $controller, $action);
	}
	
	/**
	 * Passses the variable from the controller into the view and then renders the
	 * view.
	 * 
	 * @param object $controller Instantiated object of the current controller
	 * @param array $vars Variables to be passed to the template
	 * 
	 * @return void
	 */
	public function renderTemplate($controller, $vars = array()) {
		
		if (self::_hasAdapter(get_called_class(), __FUNCTION__))
			return self::_callAdapter(get_called_class(), __FUNCTION__, $vars);
		
		$vars = self::_applyFilter(get_class(), __FUNCTION__, $vars , array('event' => 'args'));
		
		$this -> parseControllerVars($vars);
			
		$template = $controller -> getTemplate();
		$view = $controller -> getView();
			
		$view_defaults = array('view' => $this->controller, 'prefix' => $this->action);
		$controller -> cleanup();
			
		$view  += $view_defaults;
			
		$this -> registry -> template -> show($view, $template);
		$this -> registry -> template -> cleanup();
		
		self::_notify(get_called_class() . '::' . __FUNCTION__, $this, $vars);
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
		
		if (self::_hasAdapter(get_called_class(), __FUNCTION__))
			return self::_callAdapter(get_called_class(), __FUNCTION__, $vars);
		
		$vars = self::_applyFilter(get_class(), __FUNCTION__, $vars , array('event' => 'args'));
		
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
		
		if (self::_hasAdapter(get_called_class(), __FUNCTION__))
			return self::_callAdapter(get_called_class(), __FUNCTION__);

		$rt = (isset($_GET['rt'])) ? '/'.$_GET['rt'] : null;
		
		\PVRouter::setRoute($rt);
		$this -> registry -> route = \PVRouter::getRouteVariables();
		
		$route = \PVRouter::getRoute();
		$this -> controller = (empty($route['controller'])) ? \PVRouter::getRouteVariable('controller') : $route['controller'];
		$this -> action = (empty($route['action'])) ? \PVRouter::getRouteVariable('action') : $route['action'];

		if (empty($this -> controller)) {
			$this -> controller = 'index';
		}
		
		if (empty($this -> action)) {
			$this -> action = 'index';
		}
		
		
		$this -> file = $this -> path . '/' . $this -> controller . 'Controller.php';
		
		
		self::_notify(get_called_class() . '::' . __FUNCTION__, $this, $route, $rt);
	}

}

/**
 * A specialized class for exectuing the redirects of a user.
 * 
 * @package prodigyview\helium
 */
class Redirect extends \PVStaticInstance {
	
	private $url = '';
	
	/**
	 * Constructor for the redirect object
	 * 
	 * @param string $url The url to be redirected too
	 * @param array $options An array of options for the redirection
	 * 
	 * @return void
	 * @access public
	 */
	public function __construct($url, $options = array()) {
		$this -> url = $url;
	}
	
	/**
	 * Gets the current url that has been set in the redirect
	 * 
	 * @return string $url The set url
	 * @access public
	 */
	public function getUrl() {
		return $url;
	}
	
	/**
	 * Executes the redirection request to be redirected to the appropiate url
	 * 
	 * @param int A Response code to execute
	 * 
	 * @return void
	 * @access public
	 */
	public function executeRedirect($response = 302) {
		header('Location: '.$this -> url);
		echo \PVResponse::createResponse($response);
	}
}
