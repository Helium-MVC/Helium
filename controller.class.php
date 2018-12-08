<?php
namespace prodigyview\helium;

/**
 * The controller is a class that handles the controllers functionality. All controller wille extend
 * the controller class.
 * 
 * @package prodigyview\helium
 */
Abstract class He2Controller extends \PVStaticInstance {

	/**
	 * The global registry
	 */
	protected $registry;
	
	/**
	 * Info about the view
	 */
	protected $_view = array();
	
	/**
	 * Info about the template
	 */
	protected $_template = array();
	
	/**
	 * The request object about the page
	 */
	protected $request = null;
	
	/**
	 *Extensions that can be autoloaded
	 */
	protected $_extensions = array();

	/**
	 * Instantiates that controller object and creates the default parameters for the layout and the template.
	 * 
	 * @param object $registry The global registry to be passed into the class
	 * @param array $configruation A configuration that can be used to initliziaing the controller
	 * 
	 * @return void
	 * @access public
	 */
	public function __construct($registry, $configruation = array()) {
		$this->registry = $registry;
		
		$this -> request = new \PVRequest();
		
		$default_view = array(
			'type' => 'html',
			'extension' =>'php',
			'disable' => false,
		);
		
		$default_template = array(
			'prefix' => 'default',
			'type' => 'html',
			'extension' =>'php',
			'disable' => false,
		);
		
		$this->_view = $default_view;
		$this->_template = $default_template;
		
		spl_autoload_register(array($this, 'controllerExtensionLoader'));
	}
	
	/**
	 * Sets extensions that can be called. The extensions are in the extensins/controllers/ folder.
	 * 
	 * @param string $index The key to called the extension
	 * @param object $value $the object stored and to be called
	 * 
	 * @return void
	 */
	public function __set($index, $value) {
		$this -> _extensions[$index] = $value;
	}

	/**
	 * The magic method for retrieving an extension object and return
	 * the instantiated object in the functionn being called in the controller.
	 * 
	 * @param string $index The key references the autoloaded object
	 * 
	 * @return object
	 */
	public function __get($index) {
		if (!isset($this -> _extensions[$index]) && class_exists($index) ) {
			$class = new $index();
			$this -> _extensions[$index] = $class;
		}

		return $this -> _extensions[$index];
	}
	
	/**
	 * Auto loads classes in the extensions folder. Extensions autoloaded through this function
	 * will only be available through controllers.
	 * 
	 * @param string $class Th name of the class name in extensions/controllers/
	 * 
	 * @return void
	 */
	public function controllerExtensionLoader($class) {
		
		if (self::_hasAdapter(get_called_class(), __FUNCTION__))
			return self::_callAdapter(get_called_class(), __FUNCTION__, $class );
		
		$filename = $class . '.php';
		$file = SITE_PATH . 'extensions' . DS . 'controllers' . DS . $filename;
		if (!file_exists($file)) {
			return false;
		}
		require_once $file;
	}
	
	/**
	 * Returns the information for current template configuration that will be used when display
	 * the view associated with this model.
	 * 
	 * @return array $template The information on the template in an array
	 * @access public
	 */
	public function getTemplate() {
			
		if (self::_hasAdapter(get_called_class(), __FUNCTION__))
			return self::_callAdapter(get_called_class(), __FUNCTION__, $class );
		
		return $this->_template;
	}
	
	/**
	 * Returns the data that will determine what view is going to rendered. The view can only be rendered
	 * by the controller or a child class that extends the controller. The default view is the child's controller's 
	 * method name followed by '.html.php'.
	 * 
	 * @return array $layout Returns the layout informationin on array
	 * @access public
	 */
	public function getView() {
		
		if (self::_hasAdapter(get_called_class(), __FUNCTION__))
			return self::_callAdapter(get_called_class(), __FUNCTION__, $class );
		
		return $this->_view;
	}
	
	/**
	 * Changes the view that will be used by the controller. The view can only be changed by the controller or a child class
	 * of the controller. The default view is the child's controller's method name followed by '.html.php'.
	 * 
	 * @param array $args Arguements that can be used to modify the view in key value format. The arguements can be:
	 * 				-  'prefix': The name of the file to load. The default is the action of the contoller
	 * 				- 'type': The second value, for example changing the default html type to json
					- 'extension' The extension of the file. Default is php but can be anything.
	 * 				- 'disabled' _boolean_: Default value is set to false, but if set to true, no view will be displayed
	 * 
	 * @return void
	 * @access public
	 */
	protected function _renderView(array $args = array()) {
		$args += $this -> _view;
		$this ->_view = $args;
	}
	
	/**
	 * Renders the parts of the template to use a template other than 'default.html.php' file. Templates
	 * need to reside in the define set by \PV_TEMPLATE.
	 * 
	 * @param array @args The parts of the template that can be altered to change 'default.html.php'
	 * 		'prefix' _string_: The first part of the template file before the first '.'
	 * 		'type' _string_: The middle part of the template. Generally is the format such as html or json
	 * 		'extension' _string_: The last part of the template file.
	 * 		'disabled' _boolean_: Default value is set to false, but if set to true, no template will be displayed
	 * 
	 * @return void
	 * @access public
	 */
	protected function _renderTemplate(array $args = array()) {
		$args += $this->_template;
		$this->_template = $args;
	}
	
	/**
	 * Use for the redirecting to a new location. The redirect will not render the view and is faster placing a redirect
	 * after the view has been rendered. Remember return this from the controller
	 * 
	 * @param string $url The url to be redirected too
	 * @param array options
	 * 
	 * @return objected Redirect returns the redirect as an object
	 * @access public
	 */
	public function redirect($url, $options = array()) {
		
		if (self::_hasAdapter(get_called_class(), __FUNCTION__))
			return self::_callAdapter(get_called_class(), __FUNCTION__, $class );
		
		$object = new Redirect($url, $options);
		
		return $object;
	}
	
	/**
	 * Implementing default behavior to execute when a page is not found. Can be overrided using inheritance
	 * 
	 * @return void
	 */
	public function error404() {
		if (self::_hasAdapter(get_called_class(), __FUNCTION__))
			return self::_callAdapter(get_called_class(), __FUNCTION__, $class );
		
		echo "Error 404 Page Not Found";
		
		exit();
	}
	
	/**
	 * After the controller class is no longer applicable, we can call a clean up to reduce
	 * reduce resource utilization created from the template.
	 * 
	 * @return void
	 */
	public function cleanup() {
		
		if (self::_hasAdapter(get_called_class(), __FUNCTION__))
			return self::_callAdapter(get_called_class(), __FUNCTION__);
		
		spl_autoload_unregister (array($this, 'controllerExtensionLoader'));
		unset($this -> _extensions);
	}
	
	
	/**
	 * The abstract function required by all classes that are extending the controller. At very least, the controller
	 * will require to perform some action using the index.
	 * 
	 * @return $mixed Returns whatever the child method returns
	 * @access public
	 */
	abstract function index();
	
	}

