<?php

Abstract Class Controller extends PVStaticInstance {

	protected $registry;
	
	protected $_template = array();
	
	protected $_layout = array();

	/**
	 * Instantiates that controller object and creates the default parametets for the layout and the template
	 * 
	 * @param registry
	 * 
	 * @return void
	 * @access public
	 */
	public function __construct($registry) {
		$this->registry = $registry;
		
		$default_template = array(
			'type' => 'html',
			'extension' =>'php',
			'disable' => false,
		);
		
		$this->_template = $default_template;
		
		$default_layout = array(
			'prefix' => 'default',
			'type' => 'html',
			'extension' =>'php',
			'disable' => false,
		);
		
		$this->_template = $default_template;
		$this->_layout = $default_layout;
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
		return $this->_layout;
	}
	
	/**
	 * Changes the view that will be used by the controller. The view can only be changed by the controller or a child class
	 * of the controller. The default view is the child's controller's method name followed by '.html.php'.
	 */
	protected function _renderView(array $args = array()) {
		$args += $this -> template;
		$this -> template = $args;
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
		$args += $this->_layout;
		$this->_layout = $args;
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
	

?>
