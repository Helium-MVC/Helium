<?php

namespace prodigyview\helium;

/**
 * This class is designed to act as the template parser that will render html found in the templates folders and the views
 * folder of each site.
 * 
 * @package prodigyview\helium
 */
Class He2Template extends \PVStaticInstance {

	/**
	 * The global registry 
	 */
	protected $_registry;
	
	/**
	 * The request and headers of the current page 
	 */
	protected $request;
	
	/**
	 * The path where the template exist
	 */
	protected $_tempate_path;
	
	/**
	 * Stored class instance to be called in the view
	 */
	protected $_vars = array();
	
	/**
	 * The view being called
	 */
	protected $_view;
	
	/**
	 * The template being used that wraps around the view 
	 */
	protected $_template;

	/**
	 * The constrcutor for the template.
	 * 
	 * @param object $registry The global registry object
	 * @param PVRequests $request A requests object
	 * 
	 * @return void
	 */
	function __construct($registry = null, $request = null) {
		
		if (self::_hasAdapter(get_called_class(), __FUNCTION__))
			return self::_callAdapter(get_called_class(), __FUNCTION__, $registry, $request);
		
		$filtered = self::_applyFilter(get_class(), __FUNCTION__, array('registry' => $registry, 'request' => $request), array('event' => 'args'));
		$registry = $filtered['registry'];
		$request = $filtered['request'];
		
		$filtered = self::_applyFilter(get_called_class(), __FUNCTION__, array('registry' => $registry, 'request' => $request), array('event' => 'args'));
		$registry = $filtered['registry'];
		$request = $filtered['request'];
		
		$this -> _registry = $registry;
		$this -> request = $request;

		spl_autoload_register(array($this, 'templateExtensionLoader'));

	}

	/**
	 * Loads classes in the extensions/template folder. The files become helpers in the view.
	 * 
	 * @param string $class Name of the class to include
	 * 
	 * @return void
	 * @access public
	 */
	public function templateExtensionLoader($class) {
		
		if (self::_hasAdapter(get_called_class(), __FUNCTION__))
			return self::_callAdapter(get_called_class(), __FUNCTION__, $class );
		
		$class = str_replace('\\', '/', $class);
		$filename = $class . '.php';
		$file = SITE_PATH . 'extensions' . DS . 'template' . DS . $filename;
		if (!file_exists($file)) {
			return false;
		}
		require_once $file;
	}

	/**
	 * Register an spl auto loader for helper files that will become part of the template
	 * 
	 * @return void
	 * @access public
	 */
	function loadTemplateExtensions() {
		
		if (self::_hasAdapter(get_called_class(), __FUNCTION__))
			return self::_callAdapter(get_called_class(), __FUNCTION__);
		
		spl_autoload_register('templateExtensionLoader');
	}

	/**
	 * Magig function, Set an instance or string of a class to be called in the view
	 * 
	 * @param string $index The key to reference when calling the object
	 * @param string $value The name of the class to call
	 * 
	 * @return void
	 */
	public function __set($index, $value) {
		$this -> _vars[$index] = $value;
	}

	/**
	 * Magic Function, calls the object to be used in the view or template
	 * 
	 * @param string $index The key of the object being called.
	 * 
	 * @return Object Will return an instance of an object
	 */
	public function __get($index) {
		if (!isset($this -> _vars[$index]) && class_exists($index) ) {
			$class = new $index($this -> _registry, $this -> request);
			$this -> _vars[$index] = $class;
		}

		return $this -> _vars[$index];
	}

	/**
	 * Includes the view that will be displayed.
	 * 
	 * @param array $view Contains the arguements that define the view that will be displayed.
	 * 			-'view' _string_: The folder that the view will reside in
	 * 			-'prefix' _string_: The first part of the view. If the view is add.html.php, the add would be the prefix. Default value is index
	 * 			-'type' _string_: The format for the view. The default is html.
	 * 			-'exenstion' _string_: The extension of the view. The default extension is .php
	 * @param array $template Contains the arguements that define the template that will be displayed
	 * 			-'prefix' _string_: The first part of the template. The default value for the prefix is 'default'
	 * 			-'type' _string_: The
	 * 
	 */
	public function show($view, $template) {
		
		if (self::_hasAdapter(get_called_class(), __FUNCTION__))
			return self::_callAdapter(get_called_class(), __FUNCTION__, $view, $template);
		
		$filtered = self::_applyFilter(get_class(), __FUNCTION__, array('view' => $view, 'template' => $template), array('event' => 'args'));
		$view = $filtered['view'];
		$template = $filtered['template'];
		
		$filtered = self::_applyFilter(get_called_class(), __FUNCTION__, array('view' => $view, 'template' => $template), array('event' => 'args'));
		$view = $filtered['view'];
		$template = $filtered['template'];
		
		$this -> _view = $view;
		$this -> _template =$template;
		
		$this -> _titleCheck($view);
		
		if(!$template['disable'])
			ob_start( array($this , '_displayContents' ) );
		else
			ob_start();

		$path = SITE_PATH . '/views' . '/' . $view['view'] . '/' . $view['prefix'] . '.' . $view['type'] . '.' . $view['extension'];

		$this -> _tempate_path = $path;
		
		if(!$template['disable'])
			include (\PV_TEMPLATES. $template['prefix'] . '.' . $template['type'] . '.' . $template['extension']);
		
		ob_end_flush();
		
	}
	
	/**
	 * Takes the content from the output buffer, and runs it through the updateHeader function, This will replace
	 * the tags in the header and input javascript and css that has been enqueed in the library.
	 * 
	 * @param string $buffer The buffer from the ob_start
	 * 
	 * @return string $buffer The buffer to display
	 * @access protected
	 */
	protected function _displayContents($buffer) {
		
		return \PVTemplate::updateHeader($buffer);
	}
	
	/**
	 * Peforms a final check on the header to ensure that the site title
	 * has been site. If it has not, it will set it automatically. The $view that is passed in is an
	 * array that contains information about the entire view.
	 * 
	 * @param array $view An array that contains the information about the view to be displayed
	 * 
	 * @return void
	 * @access protected
	 */
	protected function _titleCheck($view) {
		
		if (self::_hasAdapter(get_class(), __FUNCTION__))
			return self::_callAdapter(get_class(), __FUNCTION__, $view);
		
		$view = self::_applyFilter(get_class(), __FUNCTION__, $view, array('event' => 'args'));
		
		$title = \PVTemplate::getSiteTitle();
		
		if(empty($title))
			\PVTemplate::setSiteTitle($view['view']. ' '. $view['prefix'] );
		
	}

	/**
	 * Displays the content in a view that will render in a template. Call this function once in the template folder.
	 * 
	 * @return void
	 * @access public
	 */
	public function content() {
		
		if (self::_hasAdapter(get_called_class(), __FUNCTION__))
			return self::_callAdapter(get_called_class(), __FUNCTION__);

		foreach ($this->_vars as $key => $value) {
			$$key = $value;
		}

		require ($this -> _tempate_path);
	}
	
	/**
	 * Returns the header to be placed at the top of a template. The header contains tags that will be replaced
	 * at the end of output buffering with the site's title, meta descriptiong, keywords, and additional javascript
	 * libraries. This method should be called between the <head></head> tags in your template file.
	 * 
	 * @return string $tags Returns a string of tags that will be placed at the top of the header
	 * @access public
	 */
	public function header(){
			
		if (self::_hasAdapter(get_called_class(), __FUNCTION__))
			return self::_callAdapter(get_called_class(), __FUNCTION__);
		
		$header_placeholders = '<title>{SITE_TITLE}</title>{HEADER_ADDITION}';
		
		$header_placeholders = self::_applyFilter(get_class(), __FUNCTION__, $header_placeholders , array('event' => 'return'));
		
		return $header_placeholders;
		
	}
	
	/**
	 * After the template class is no longer application, we can call a clean up to reduce
	 * reduce resource utilization created from the template.
	 */
	public function cleanup() {
		
		if (self::_hasAdapter(get_called_class(), __FUNCTION__))
			return self::_callAdapter(get_called_class(), __FUNCTION__);
		
		spl_autoload_unregister (array($this, 'templateExtensionLoader'));
		unset($this -> _vars);
		unset($this -> _registry);
		unset($this -> request);
	}

}
