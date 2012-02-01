<?php

Class Template extends PVStaticInstance {

	private $registry;
	private $request;
	private $tempate_path;
	private $vars = array();

	/**
	 * The constrcutor for the template.
	 */
	function __construct($registry, $request) {
		
		if (self::_hasAdapter(get_called_class(), __FUNCTION__))
			return self::_callAdapter(get_called_class(), __FUNCTION__, $registry, $request);
		
		$filtered = self::_applyFilter(get_class(), __FUNCTION__, array('registry' => $registry, 'request' => $request), array('event' => 'args'));
		$registry = $filtered['registry'];
		$request = $filtered['request'];
		
		$filtered = self::_applyFilter(get_called_class(), __FUNCTION__, array('registry' => $registry, 'request' => $request), array('event' => 'args'));
		$registry = $filtered['registry'];
		$request = $filtered['request'];
		
		$this -> registry = $registry;
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
		
		$filename = $class . '.php';
		$file = SITE_PATH . 'extensions' . DS . 'template' . DS . $filename;
		if (!file_exists($file)) {
			return false;
		}
		require_once $file;
		//$object_name=strtolower($class);
		//$object= new $class;
		//$this->vars[$object_name]=$object;
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

	public function __set($index, $value) {
		$this -> vars[$index] = $value;
	}

	public function __get($index) {
		if (!isset($this -> vars[$index])) {
			$class = new $index();
			$this -> vars[$index] = $class;
		}

		return $this -> vars[$index];
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
		
		ob_start( array($this , '_displayContents' ) );
		
		$filtered = self::_applyFilter(get_class(), __FUNCTION__, array('view' => $view, 'template' => $template), array('event' => 'args'));
		$view = $filtered['view'];
		$template = $filtered['template'];
		
		$filtered = self::_applyFilter(get_called_class(), __FUNCTION__, array('view' => $view, 'template' => $template), array('event' => 'args'));
		$view = $filtered['view'];
		$template = $filtered['template'];

		$path = SITE_PATH . '/views' . '/' . $view['view'] . '/' . $view['prefix'] . '.' . $view['type'] . '.' . $view['extension'];

		$this -> tempate_path = $path;
		
		if(!$template['disable'])
			include (PV_TEMPLATES. $template['prefix'] . '.' . $template['type'] . '.' . $template['extension']);
		
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
		
		return PVTemplate::updateHeader($buffer);
	}
	
	/**
	 * Peforms a final check on the header to ensure that the site title
	 * has been site. If it has not, it will set it automatically.
	 * 
	 */
	protected function _finalHeaderCheck($view) {
		
		$title = PVTemplate::getSiteTitle();
		
		if(empty($title))
			PVTemplate::setTitleTitle( $view );
		
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

		foreach ($this->vars as $key => $value) {
			$$key = $value;
		}

		require ($this -> tempate_path);
	}
	
	public function header(){
		
		return '{HEADER_ADDITION}';
		
	}

}
?>
