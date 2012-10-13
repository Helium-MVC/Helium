<?php

Abstract class Controller extends PVStaticInstance {

	protected $registry;
	
	protected $_view = array();
	
	protected $_template = array();
	
	protected $request = null;
	
	protected $_extensions = array();

	/**
	 * Instantiates that controller object and creates the default parametets for the layout and the template
	 * 
	 * @param registry
	 * 
	 * @return void
	 * @access public
	 */
	public function __construct($registry, $configurtion = array()) {
		$this->registry = $registry;
		
		$this -> request = new PVRequest();
		
		$default_view = array(
			'type' => 'html',
			'extension' =>'php',
			'disable' => false,
		);
		
		$this->_view = $default_view;
		
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
	
	public function __set($index, $value) {
		$this -> _extensions[$index] = $value;
	}

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

	protected function getModel($model_name){
		include(__SITE_PATH . 'model/'.$model_name.'.php');
		return new $model_name($this->registry);
	}
	
	/**
	 * Returns the information for current template configuration that will be used when display
	 * the view associated with this model.
	 * 
	 * @return array $template The information on the template in an array
	 * @access public
	 */
	public function getTemplate() {
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
		return $this->_view;
	}
	
	/**
	 * Changes the view that will be used by the controller. The view can only be changed by the controller or a child class
	 * of the controller. The default view is the child's controller's method name followed by '.html.php'.
	 */
	protected function _renderView(array $args = array()) {
		$args += $this -> _view;
		$this ->_view = $args;
	}
	
	/**
	 * Renders the parts of the template to use a template other than 'default.html.php' file. Templates
	 * need to reside in the define set by PV_TEMPLATE.
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
		$object = new Redirect($url, $options);
		
		return $object;
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
