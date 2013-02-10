<?php

Abstract Class Model extends PVStaticInstance {

	protected $registry;
	protected $_errors;
	protected $_config = array(
		'create_table' => true, 
		'column_check' => true,
		'table_name' => null, 
		'storage' => '',
		'connection' => null,
		'cache' => false,
		'cache_method' => null
	);

	/**
	 * The constructor for the model class. Can be used to assign default data to a model
	 * 
	 * @param mixed $data The value passed should either be null or an array
	 * @param array $options Options to be set for the model
	 * 
	 * @return void
	 * @access public
	 */
	public function __construct($data = null, array $options = array()) {

		if (self::_hasAdapter(get_called_class(), __FUNCTION__))
			return self::_callAdapter(get_called_class(), __FUNCTION__, $registry);

		$data = self::_applyFilter(get_class(), __FUNCTION__, $data, array('event' => 'args'));
		$data = self::_applyFilter(get_called_class(), __FUNCTION__, $data, array('event' => 'args'));
		
		$this -> registry = new PVCollection();

		if ($data) {
			foreach($data as $key => $value)
				$this -> addToCollectionWithName($key, $value);
		}
		
		$default_config = array(
			'create_table' => true, 
			'column_check' => true, 
			'storage' => '',
			'connection' => null
		);
		
		$this -> _config += $default_config;

		self::_notify(get_class() . '::' . __FUNCTION__, $this, $data);
		self::_notify(get_called_class() . '::' . __FUNCTION__, $this, $data);

	}
	
	/**
	 * Checks the schema that was defined in the models $_schema array. If the schema does not exist in the database,
	 * the table and columns associated with that schema will be created. Will also new columns to the database. This method
	 * should not be used with schema databases such as Mongo.
	 * 
	 * @param boolean force_check Will force a check even if the schema check is disabled in the config
	 * 
	 * @return void
	 * @access public
	 */
	public function checkSchema($force_check = false) {

		if (self::_hasAdapter(get_class(), __FUNCTION__))
			return self::_callAdapter(get_class(), __FUNCTION__);

		if (self::_hasAdapter(get_called_class(), __FUNCTION__))
			return self::_callAdapter(get_called_class(), __FUNCTION__);

		$table_name = $this -> _formTableName(get_class($this));
		$tablename = PVDatabase::formatTableName(strtolower($table_name));
		
		$check_table_name = (PVDatabase::getDatabaseType() == 'postgresql') ? PVDatabase::formatTableName(strtolower($table_name), false) : $tablename;
		$schema = PVDatabase::getSchema(false);

		if (($this -> _config['create_table'] && !PVDatabase::tableExist($check_table_name, $schema) && isset($this -> _schema)) || ($force_check == true && !PVDatabase::tableExist($check_table_name, $schema))) {
			$primary_keys = '';
			$first = 1;
			$schema = $this -> _schema;

			foreach ($schema as $key => $value) {
				if (isset($value['primary_key'])) {
					$primary_keys .= (!$first) ? ',' . $key : $key;
					$first = 0;
				} else if (isset($value['default']) && empty($value['default']) && !($value['default'] === 0)) {
					$schema[$key]['default'] = '\'\'';
				}
			}//endforeach

			$options = array('primary_key' => $primary_keys);
			PVDatabase::createTable($tablename, $schema, $options);
		} else if (($this -> _config['column_check'] && isset($this -> _schema)) || $force_check == true ) {
			$schema = $this -> _schema;

			foreach ($schema as $key => $value) {
				if (!PVDatabase::columnExist($check_table_name, $key)) {
					PVDatabase::addColumn($tablename, $key, $value);
				}
			}//end foreach
		}
	}//end checkSchema

	/**
	 * Validates data that is passed to the model against the protected array $_validators. If all validation clears, a true will be
	 * returned. Otherwise validation errors are passed to _addValidationError
	 * 
	 * @param array $data The data to check for errors. Data should be in key => value format
	 * @param array $options Options that be used to configure validation
	 * 			- 'event' _array_: An array of events that validation will occur. Default is an empty
	 * 			-'sync_data' _boolean_: If set to true, will sync the data passed to the model's collection
	 * 
	 * @return boolean $validation Returns true if no errors are found, otherwise returns false
	 * @access public
	 */
	public function validate($data, $options = array()) {

		if (self::_hasAdapter(get_class(), __FUNCTION__))
			return self::_callAdapter(get_class(), __FUNCTION__, $data, $options);

		if (self::_hasAdapter(get_called_class(), __FUNCTION__))
			return self::_callAdapter(get_called_class(), __FUNCTION__, $data, $options);

		$defaults = array('event' => '', 'sync_data' => true);

		$options += $defaults;

		$filtered = self::_applyFilter(get_class(), __FUNCTION__, array('data' => $data, 'options' => $options), array('event' => 'args'));
		$data = $filtered['data'];
		$options = $filtered['options'];

		$filtered = self::_applyFilter(get_called_class(), __FUNCTION__, array('data' => $data, 'options' => $options), array('event' => 'args'));
		$data = $filtered['data'];
		$options = $filtered['options'];

		$hasError = true;
		$this -> _errors = array();

		if (!empty($this -> _validators)) {

			foreach ($this->_validators as $field => $rules) {

				foreach ($rules as $key => $rule) {
						
					
					$rule += $this -> _getValidationRuleDefaults();
					
					if ($this -> _checkValidationEvent($options['event'], $rule['event'])  && !PVValidator::check($key, @$data[$field], $rule['options'])) {
						$hasError = false;
						$this -> _addValidationError($field, $rule['error']);
					}

				}//end second foreach
			}//end foreach

		}//end validators
		
		if($options['sync_data']) {
			foreach($data as $key => $value) {
				$this -> addToCollectionWithName($key, $value);
			}
		}

		$this -> registry -> errors = $this -> _errors;

		self::_notify(get_class() . '::' . __FUNCTION__, $this, $hasError, $data);
		self::_notify(get_called_class() . '::' . __FUNCTION__, $this, $hasError, $data);

		return $hasError;
	}//end validate

	/**
	 * Creates a record in the database. The record created will be guided by the model's schema.
	 * 
	 * @param array $data The data that will be used create the record. Empty values will default to the schema
	 * @param array $options Options that be used to define the creation of a record
	 * 			-'validate' _boolean_: Will validate data before attempting to create a record. Default is true. If set
	 * 			to false, validation will not occur and a record will be created regardless of the data passed through.
	 * 			-'use_schema' _boolean_: Will included the schema to the current data that is passed through. Default is true.
	 * 			If set to false, the schema will not be used and set default values will not apply
	 * 			-'sync_data' _boolean_: After creation, the information used to create the record will be synched with model.This means
	 * 			the data will be accessible through methods such as $model -> $field_name
	 * 			-'validate_options' _array_: Options that ded
	 * 
	 * @return boolean $created Returns true if the the object is created. Data will be added directly into the instance
	 * @access public
	 * @todo as unique modifier, if applicable
	 */
	public function create(array $data, array $options = array()) {

		if (self::_hasAdapter(get_class(), __FUNCTION__))
			return self::_callAdapter(get_class(), __FUNCTION__, $data, $options);

		if (self::_hasAdapter(get_called_class(), __FUNCTION__))
			return self::_callAdapter(get_called_class(), __FUNCTION__, $data, $options);
		
		$this -> _setConnection();
		
		$defaults = array('validate' => true, 'use_schema' => true, 'sync_data' => true, 'validate_options' => array('event' => 'create'), 'return_last_id' => true);
		$options += $defaults;

		$filtered = self::_applyFilter(get_class(), __FUNCTION__, array('data' => $data, 'options' => $options), array('event' => 'args'));
		$data = $filtered['data'];
		$options = $filtered['options'];

		$filtered = self::_applyFilter(get_called_class(), __FUNCTION__, array('data' => $data, 'options' => $options), array('event' => 'args'));
		$data = $filtered['data'];
		$options = $filtered['options'];

		$created = false;
		$id = 0;

		if (PVDatabase::getDatabaseType() != 'mongo') {
			$this -> checkSchema();
		}

		if (!$options['validate'] || $this -> validate($data,$options['validate_options'])) {
			$table_name = $this -> _formTableName(get_class($this));
			$table_name = PVDatabase::formatTableName(strtolower($table_name));
			
			if($options['use_schema']) {
				$defaults = $this -> _getModelDefaults();
				$data += $defaults;
			}

			$input_data = array();
			$primary_keys = array();
			$auto_incremented_field = null;
			
			if($options['validate']) {
				foreach ($this->_schema as $field => $field_options) {
					$field_options += $this -> _getFieldOptionsDefaults();
					$input_data[$field] = (empty($data[$field])) ? $field_options['default'] : $data[$field];
	
					if ($field_options['auto_increment'] == true) {
						$auto_incremented_field = $field;
						unset($input_data[$field]);
					}
					
				 	if ($field_options['primary_key'] == true && !$field_options['auto_increment']) {
						$primary_keys[$field] = $input_data[$field];
					}
					
					if(isset($field_options['cast']))
							$input_data[$field] = $this -> _castData($input_data[$field] , $field_options['cast']);
	
					if ($field_options['unique'] == true) {
						//$primary_key=$field;
					}
	
				}//end foreach
			} else {
				$input_data = $data;
			}
		
			$options = $this -> _configureConnection($options);
			
			if($options['return_last_id'] && !empty($auto_incremented_field))
				$id = PVDatabase::preparedReturnLastInsert($table_name, $auto_incremented_field, $table_name, $input_data, array(), $options);
			else {
				PVDatabase::preparedInsert($table_name, $input_data);
				$created = true;
			}

			if ($id) {
				
				$created = true;
			}
		}
		
		$this -> _resetConnection();
		
		if($created == true && $auto_incremented_field){
			$conditions = array('conditions' => array($auto_incremented_field => $id));
			$this -> first($conditions);
		}
		
		self::_notify(get_class() . '::' . __FUNCTION__, $this, $created, $id, $data, $options);
		self::_notify(get_called_class() . '::' . __FUNCTION__, $this, $created, $id, $data, $options);
		
		return $created;
	}

	/**
	 * Updates a record in the database based on the passed values and conditions.
	 * 
	 * @param array $data The fields to be updated in the database. The key => value format will become column => value format
	 * @param array $conditions The conditions for updating the data
	 * @param array $options Options that can alter how the update occurs
	 * 				-'validate' _boolean_: Validate the data first before attempting to update. Default is true, if set to false,
	 * 				update will occur without validation
	 * 				-'use_schema' _boolean_: Use the schema for adding in default values. Default is true
	 * 				-'sync_data' _boolean_ : On completion of update, the data with be synced with the instance
	 * 				-'validate_options' _array_: An array to pass to the validation process
	 * @return mixed $result Returns the result from the database
	 * @access protected
	 */
	public function update(array $data = array(),array $conditions = array(), array $options = array()) {

		if (self::_hasAdapter(get_class(), __FUNCTION__))
			return self::_callAdapter(get_class(), __FUNCTION__, $data, $conditions, $options);

		if (self::_hasAdapter(get_called_class(), __FUNCTION__))
			return self::_callAdapter(get_called_class(), __FUNCTION__, $data, $conditions, $options);
		
		$this -> _setConnection();

		$defaults = array('validate' => true, 'use_schema' => true, 'sync_data' => true, 'validate_options' => array('event' => 'update'));
		$options += $defaults;
		
		$filtered = self::_applyFilter(get_class(), __FUNCTION__, array('data' => $data, 'conditions' => $conditions, 'options' => $options), array('event' => 'args'));
		$data = $filtered['data'];
		$conditions = $filtered['conditions'];
		$options = $filtered['options'];

		$filtered = self::_applyFilter(get_called_class(), __FUNCTION__, array('data' => $data, 'conditions' => $conditions, 'options' => $options), array('event' => 'args'));
		$data = $filtered['data'];
		$conditions = $filtered['conditions'];
		$options = $filtered['options'];

		$result = false;
		
		if($options['use_schema']) {
			$defaults = $this -> _getModelDefaults();
			$data += $defaults;
		}
		
		if (!$options['validate'] || $this -> validate($data, $options['validate_options'])) {
			
			$table_name = $this -> _formTableName(get_class($this));
			$table_name = PVDatabase::formatTableName($table_name);
			
			$input_data = array();
			$primary_key = '';
			$wherelist = isset($conditions['conditions']) ? $conditions['conditions'] : array();
			
			if($options['use_schema']) {
				
				foreach ($this->_schema as $field => $field_options) {
					$field_options += $this -> _getFieldOptionsDefaults();
	
					if ((!isset($field_options['null']) || (isset($field_options['null']) && !$field_options['null'])) || !empty($data[$field])) {
						if ($field_options['primary_key']) {
							$primary_key = $field;
							$wherelist[$field] = (!empty($this -> _collection -> $field)) ? $field_options['default'] : $this -> _collection -> $field;
						}
	
						$input_data[$field] = (!$data[$field]) ? $this -> $field : $data[$field];
						
						if(isset($field_options['cast']))
							$input_data[$field] = $this -> _castData($input_data[$field] , $field_options['cast']);
					}
	
				}//end foreach
			} else {
				$input_data = $data;
				$wherelist = isset($conditions['conditions']) ? $conditions['conditions'] : array();
			}
			
			$options = $this -> _configureConnection($options);
			$result = PVDatabase::preparedUpdate($table_name, $input_data, $wherelist, array(), array(), $options);
			$this -> addToCollection($input_data);
		}

		$this -> _resetConnection();
		
		
		if ($result && $options['sync_data']){
				$this -> sync();
		}
		
		self::_notify(get_class() . '::' . __FUNCTION__, $this, $result, $data, $conditions, $options);
		self::_notify(get_called_class() . '::' . __FUNCTION__, $this, $result, $data, $conditions, $options);

		return $result;
	}//end update

	/**
	 * Deletes a value in a table or a collection based on the passed values
	 * 
	 * @param array $data The data in key => value format that becomes column => value format
	 * 
	 * @return void
	 * @access public
	 * @todo create a more complex delete
	 */
	public function delete($conditions, array $options = array()) {
		
		if (self::_hasAdapter(get_class(), __FUNCTION__))
			return self::_callAdapter(get_class(), __FUNCTION__, $conditions, $options);

		if (self::_hasAdapter(get_called_class(), __FUNCTION__))
			return self::_callAdapter(get_called_class(), __FUNCTION__, $conditions, $options);
		
		$this -> _setConnection();

		$filtered = self::_applyFilter(get_class(), __FUNCTION__, array('conditions' => $conditions, 'options' => $options), array('event' => 'args'));
		$conditions = $filtered['conditions'];
		$options = $filtered['options'];
		
		$filtered = self::_applyFilter(get_called_class(), __FUNCTION__, array('conditions' => $conditions, 'options' => $options), array('event' => 'args'));
		$conditions = $filtered['conditions'];
		$options = $filtered['options'];
		
		if (PVDatabase::getDatabaseType() != 'mongo') {
			$this -> checkSchema();
		}
		
		$args = array(
				'where' => isset($conditions['conditions']) ? $conditions['conditions'] : array(), 
				'fields' => isset($conditions['fields']) ? $conditions['fields'] : array(), 
				'table' => PVDatabase::formatTableName($this -> _formTableName(get_class($this))),
				'limit' => isset($conditions['limit']) ? $conditions['limit'] : null,
				'offset' => isset($conditions['offset']) ? $conditions['offset'] : null,
				'order_by' => isset($conditions['order_by']) ? $conditions['order_by'] : null,
				
		);

		$options = $this -> _configureConnection($options);
		$result = PVDatabase::preparedDelete($args['table'], $args['where'], array(), $options);
		
		$this -> _resetConnection();
		
		self::_notify(get_class() . '::' . __FUNCTION__, $this, $result, $conditions, $options);
		self::_notify(get_called_class() . '::' . __FUNCTION__, $this, $result, $conditions, $options);

		return $result;
	}//end delete

	/**
	 * Returns the first result found in the database based upon the passed conditions. The results DO NOT require
	 * the getIterator function, but are accessible directly from the instance.
	 * 
	 * @param array $conditions The conditions used for finding row or document
	 * 		-'conditions' _array_:The conditions used finding a value. The array key => value return into column = 'value'
	 * 		-'join' _array_: Used the joins specefied in the child model in the '$_joins; variable
	 * 
	 * @return void Return values are placed in the instance and are accessible through the instance
	 * @access public
	 * @todo create a more complex searching method
	 */
	public function first($conditions = array(), $options = array()) {
		
		if (self::_hasAdapter(get_class(), __FUNCTION__))
			return self::_callAdapter(get_class(), __FUNCTION__, $conditions, $options);

		if (self::_hasAdapter(get_called_class(), __FUNCTION__))
			return self::_callAdapter(get_called_class(), __FUNCTION__, $conditions, $options);
		
		$this -> _setConnection();

		$filtered = self::_applyFilter(get_class(), __FUNCTION__, array('conditions' => $conditions, 'options' => $options), array('event' => 'args'));
		$conditions = $filtered['conditions'];
		$options = $filtered['options'];
		
		$filtered = self::_applyFilter(get_called_class(), __FUNCTION__, array('conditions' => $conditions, 'options' => $options), array('event' => 'args'));
		$conditions = $filtered['conditions'];
		$options = $filtered['options'];

		if (PVDatabase::getDatabaseType() == 'mongo') {

			$conditions = isset($conditions['conditions']) ? $conditions['conditions'] : array();
			$fields = isset($conditions['fields']) ? $conditions['fields'] : array();

			$args = array('where' => $conditions, 'fields' => $fields, 'table' => $this -> _formTableName(get_class($this)));
			$options['findOne'] = true;
			$options = $this -> _configureConnection($options);
			
			$result = PVDatabase::selectStatement($args, $options);
			
			if ($result) {
				foreach ($result as $key => $value) {
					
					if (!PVValidator::isInteger($key))
						$this -> addToCollectionWithName($key, $value);
				}
			}//end if result
			
			if(isset($options['gridFS']) && method_exists($result, 'getBytes'))
				$this -> addToCollectionWithName('getBytes', $result -> getBytes());
			
		} else {

			$this -> checkSchema();
			
			$args = array(
				'where' => isset($conditions['conditions']) ? $conditions['conditions'] : array(), 
				'fields' => isset($conditions['fields']) ? $conditions['fields'] : '*', 
				'table' => PVDatabase::formatTableName(strtolower($this -> _formTableName(get_class($this)))),
				'limit' => isset($conditions['limit']) ? $conditions['limit'] : 1,
				'offset' => isset($conditions['offset']) ? $conditions['offset'] : null,
				'order_by' => isset($conditions['order_by']) ? $conditions['order_by'] : null,
				
			);

			$query ='';
			if (isset($conditions['join']) && isset($this -> _joins)) {
				foreach ($conditions['join'] as $join) {

					if (isset($this -> _joins[$join]))
						$query .= $this -> _joinTable($this -> _joins[$join]) . ' ';

				}//end foreach
			}
			$args['join'] = $query;
			
			$result = PVDatabase::selectPreparedStatement($args,$options);
			$row = PVDatabase::fetchArray($result);
			
			if(!empty($row)) {
				foreach ($row as $key => $value) {
					if (!PVValidator::isInteger($key))
						$this -> addToCollectionWithName($key, $value);
				}
			}
		}

		$this -> _resetConnection();
		
		self::_notify(get_class() . '::' . __FUNCTION__, $this, $result, $conditions, $options);
		self::_notify(get_called_class() . '::' . __FUNCTION__, $this, $result, $conditions, $options);
	}

	/**
	 * Search for fields in the database based on conditions. In SQL database, joins can be used with other tables. The fields found
	 * will be added to the model instance. To get those fields, use $model -> getIterator() method on the instance.The arguements based
	 * will differ depending on the database.
	 * 
	 * @param array $conditions Conditions used for query that database
	 * 			-'conditions' _mixed_: Either an explicit SQL Where clause or an array of options.
	 * 			-'fields' _mixed_: Either an array or string of fields to return
	 * 			-'limit' _int_: A limit on the amount of fields that will be returned
	 * 			-'offset' _int_: An offset to the results in the query
	 * 			-'order_by' _string_: How to order
	 * 			-'join' _array_: Used the joins specefied in the child model in the '$_joins; variable
	 * @param array $options Options can be used to customize the finding of data
	 * 			-'results' _mixed_: How the results will be returned. Default option is 'object', in wich the results will be stored in an
	 * 			stdObject. The other option is 'model', in which the results will be stored in a new instance of the current model
	 * 
	 * @return void Return results are added as part of the model
	 * @access public
	 * @todo Add in the ability for pagination
	 * @todo Write a function for recording results into the current model collection
	 */
	public function find($conditions = array(), array $options = array()) {
		
		if (self::_hasAdapter(get_class(), __FUNCTION__))
			return self::_callAdapter(get_class(), __FUNCTION__, $conditions, $options);

		if (self::_hasAdapter(get_called_class(), __FUNCTION__))
			return self::_callAdapter(get_called_class(), __FUNCTION__, $conditions, $options);
		
		$this -> _setConnection();

		$filtered = self::_applyFilter(get_class(), __FUNCTION__, array('conditions' => $conditions, 'options' => $options), array('event' => 'args'));
		$conditions = $filtered['conditions'];
		$options = $filtered['options'];
		
		$filtered = self::_applyFilter(get_called_class(), __FUNCTION__, array('conditions' => $conditions, 'options' => $options), array('event' => 'args'));
		$conditions = $filtered['conditions'];
		$options = $filtered['options'];
		
		$defaults = array('results' => 'object');
		$options += $defaults;
		
		if (PVDatabase::getDatabaseType() == 'mongo') {
			
			$args = array(
				'where' => isset($conditions['conditions']) ? $conditions['conditions'] : array(), 
				'fields' => isset($conditions['fields']) ? $conditions['fields'] : array(), 
				'table' => $this -> _formTableName(get_class($this)),
				'limit' => isset($conditions['limit']) ? $conditions['limit'] : null,
				'offset' => isset($conditions['offset']) ? $conditions['offset'] : null,
				'order_by' => isset($conditions['order_by']) ? $conditions['order_by'] : null,
				'group_by' => isset($conditions['group_by']) ? $conditions['group_by'] : null,
				'paginate' => isset($conditions['paginate']) ? $conditions['paginate'] : false,
				'results_per_page' => isset($conditions['results_per_page']) ? $conditions['results_per_page'] : 20,
				'current_page' => isset($conditions['current_page']) ? $conditions['current_page'] : 0,
				
			);
			
			$options = $this -> _configureConnection($options);
			$result = PVDatabase::selectStatement($args, $options);

			foreach ($result as $row) {
				
				if(isset($options['gridFS'])){
						
					$bytes = 0;
					if(method_exists($row, 'getBytes'))
						$bytes = $row -> getBytes();
					
					$row = array('file' => $row-> file);
					$row['getBytes'] = $bytes;
				}
				
				if($options['results'] == 'model') {
					$class= get_called_class();
					$row = new $class($row);
				} 
				
				$this -> addToCollection($row);
			}

		} else {
			$this -> checkSchema();

			$args = array(
				'where' => isset($conditions['conditions']) ? $conditions['conditions'] : array(), 
				'fields' => isset($conditions['fields']) ? $conditions['fields'] : '*', 
				'table' => PVDatabase::formatTableName($this -> _formTableName(get_class($this))),
				'limit' => isset($conditions['limit']) ? $conditions['limit'] : null,
				'offset' => isset($conditions['offset']) ? $conditions['offset'] : null,
				'order_by' => isset($conditions['order_by']) ? $conditions['order_by'] : null,
				'group_by' => isset($conditions['group_by']) ? $conditions['group_by'] : null,
				'paginate' => isset($conditions['paginate']) ? $conditions['paginate'] : false,
				'results_per_page' => isset($conditions['results_per_page']) ? $conditions['results_per_page'] : 20,
				'current_page' => isset($conditions['current_page']) ? $conditions['current_page'] : 0,
			);

			$query = '';
			if (isset($conditions['join']) && isset($this -> _joins)) {
				foreach ($conditions['join'] as $join) {

					if (isset($this -> _joins[$join]))
						$query .= $this -> _joinTable($this -> _joins[$join]) . ' ';

				}//end foreach
			}

			$args['join'] = $query;
			
			if($args['paginate']) {
				$this -> _getPaginationData($args['table'], $args['current_page'], $args['results_per_page'], $args['join']);
			}
			
			$result = PVDatabase::selectPreparedStatement($args,$options);
			
			if(PVDatabase::getDatabaseType() == 'postgresql') {
				while($row = PVDatabase::fetchFields($result)){
						
					if($options['results'] == 'model') {
						$class= get_called_class();
						$row = new $class($row);
					}
					
					$this -> addToCollection($row);
				}
			} else {
				$rows = PVDatabase::fetchFields($result);
				
				if(!empty($rows)) {
					foreach ($rows as $row) {
					
						if($options['results'] == 'model') {
							$class= get_called_class();
							$row = new $class($row);
						}
			 
						$this -> addToCollection($row);
					}
				}
			}
		}
		
		$this -> _resetConnection();
		
		self::_notify(get_class() . '::' . __FUNCTION__, $this, $conditions);
		self::_notify(get_called_class() . '::' . __FUNCTION__, $this, $conditions);
	}

	/**
	 * Sync will sync the data from the database with the current schema that is set. Sync is automatically called
	 * after the methods create and update are called. Sync should be used if another source is operating on the database.
	 * 
	 * @return boolean $synced Returns true if the sync was successful
	 * @access public
	 * @todo For relational database, if joins are present, sync them also
	 */
	public function sync() {
		
		if (self::_hasAdapter(get_class(), __FUNCTION__))
			return self::_callAdapter(get_class(), __FUNCTION__);

		if (self::_hasAdapter(get_called_class(), __FUNCTION__))
			return self::_callAdapter(get_called_class(), __FUNCTION__);

		if (!isset($this -> _schema)) {
			return false;
		}
		$keys = array();
		foreach ($this->_schema as $key => $value) {
			$value += $this -> _getFieldOptionsDefaults();

			if ($value['primary_key']) {
				$keys[$key] = (!empty($this -> _collection -> $key)) ? $value['default'] : $this -> _collection -> $key;
			}
		}
		if (!empty($keys)) {
			$conditions = array('conditions' => $keys);
			$this -> first($conditions);
			return true;
		}

		return false;
	}//end sync

	/**
	 * Displays stored errors that have accumlated during a validation check.
	 * 
	 * @param string $error_name The error name is the field that is associated with the error
	 * 
	 * @return void
	 * @access public
	 */
	public function error($error_name) {

		if (self::_hasAdapter(get_called_class(), __FUNCTION__))
			return self::_callAdapter(get_called_class(), __FUNCTION__, $error_name);

		$error_name = self::_applyFilter(get_class(), __FUNCTION__, $error_name, array('event' => 'args'));
		$error_name = self::_applyFilter(get_called_class(), __FUNCTION__, $error_name, array('event' => 'args'));
		$output = '';
		
		if (isset($this -> _errors[$error_name])) {
			foreach ($this-> _errors[$error_name] as $error) {
				$output .= $error;
			}//end foreach
		}
		
		return $output;
	}//endError
	
	public function getVadilationErrors(){
		return $this -> _errors;
	}
	
	/**
	 *  For models that connect tion a relational database, this method will create a join between tables. The
	 *  options that describes how to join a table should be set in the child's model $_joins class variable.
	 * 
	 * @param array $args An array of arguements that describtes the join
	 * 
	 * @return string $join Returns an SQL query for a join created using the passed arguments
	 * @access protected
	 */
	protected function _joinTable($args = array()) {

		if (self::_hasAdapter(get_called_class(), __FUNCTION__))
			return self::_callAdapter(get_called_class(), __FUNCTION__, $args);

		$args = self::_applyFilter(get_class(), __FUNCTION__, $args, array('event' => 'args'));
		$args = self::_applyFilter(get_called_class(), __FUNCTION__, $args, array('event' => 'args'));

		$defaults = array('type' => 'natural', 'alias' => '', 'on' => '', 'format_table' => true);

		$args += $defaults;
		
		$join = '';
		
		if(isset($args['using'])) {
			$join .= $this -> _joinTable($this -> _joins[$args['using']]) . ' ';
		}

		$table = ($args['format_table']) ? PVDatabase::formatTableName(strtolower($args['table'])) : $args['table'];

		switch(strtolower($args['type'])) :
			case 'left' :
				$join .= 'LEFT JOIN';
				break;
			case 'right' :
				$join .= 'RIGHT JOIN';
				break;
			case 'join' :
				$join .= 'JOIN';
				break;
			case 'full' :
				$join .= 'FULL JOIN';
				break;
			default :
				$join .= 'NATURAL JOIN';
		endswitch;

		$join .= ' ' . $table;

		if (!empty($args['alias']))
			$join .= ' AS ' . $args['alias'];

		if (!empty($args['on']))
			$join .= ' ON ' . $args['on'];
		
		$join = self::_applyFilter(get_class(), __FUNCTION__, $join , array('event' => 'return'));
		$join = self::_applyFilter(get_called_class(), __FUNCTION__, $join , array('event' => 'return'));

		return $join;
	}

	/**
	 * Returns the default values that are in a model. The default values are determined by the schema. If the value
	 * is present in the dataset, that becomes the default. Else the value for a field becomes the default value
	 * set in the schema
	 * 
	 * @return array $defaults Returns an array of defaults for the model
	 * @access protected
	 */
	protected function _getModelDefaults() {

		if (self::_hasAdapter(get_called_class(), __FUNCTION__))
			return self::_callAdapter(get_called_class(), __FUNCTION__);

		$defaults = array();
		
		if (isset($this -> _schema)) {
			foreach ($this->_schema as $key => $value) {
				$defaults[$key] = ($this ->  _collection -> $key) ? $this ->  _collection -> $key : @$value['default'];
			}
		}
		
		$defaults = self::_applyFilter(get_class(), __FUNCTION__, $defaults , array('event' => 'return'));
		$defaults = self::_applyFilter(get_called_class(), __FUNCTION__, $defaults , array('event' => 'return'));
		
		return $defaults;
	}//end

	/**
	 * Returns the defaut options associated with a field and it's option from the model's schema.
	 * 
	 * @return array $defaults Returns an array of default options
	 * @access protected
	 */
	protected function _getFieldOptionsDefaults() {
		
		if (self::_hasAdapter(get_class(), __FUNCTION__))
			return self::_callAdapter(get_class(), __FUNCTION__);

		if (self::_hasAdapter(get_called_class(), __FUNCTION__))
			return self::_callAdapter(get_called_class(), __FUNCTION__);

		$defaults = array('primary_key' => false, 'unique' => false, 'type' => 'string', 'auto_increment' => false, 'default' => '');
		
		$defaults = self::_applyFilter(get_class(), __FUNCTION__, $defaults , array('event' => 'return'));
		$defaults = self::_applyFilter(get_called_class(), __FUNCTION__, $defaults , array('event' => 'return'));

		return $defaults;
	}

	/**
	 * Creates the table name for the database based upon the model name. Table name will be created by seperating capital
	 * letters as seperate words and adding an '_' between them. The string will also be made to lower case.. If a name
	 * exist in the $_config['table_name'], then that table name will be used and the table name will not be formatted
	 * to Helium standards.
	 * 
	 * @param string $name The name of the table to format the name of
	 * 
	 * @return string $table Returns the name table in the correct format
	 * @access protected
	 */
	protected function _formTableName($name) {
		
		if (self::_hasAdapter(get_class(), __FUNCTION__))
			return self::_callAdapter(get_class(), __FUNCTION__, $name);

		if (self::_hasAdapter(get_called_class(), __FUNCTION__))
			return self::_callAdapter(get_called_class(), __FUNCTION__, $name);
		
		if(isset($this -> _config['table_name']) && $this -> _config['table_name'] != null && !empty($this -> _config['table_name'])) {
			return $this -> _config['table_name'];
		}

		$name = self::_applyFilter(get_class(), __FUNCTION__, $name, array('event' => 'args'));
		$name = self::_applyFilter(get_called_class(), __FUNCTION__, $name, array('event' => 'args'));

		preg_match_all('/[A-Z][^A-Z]*/', $name, $results);
		$table = '';
		foreach ($results[0] as $key => $part) {
			if ($key == 0)
				$table .= strtolower($part);
			else
				$table .= '_' . strtolower($part);
		}

		self::_notify(get_class() . '::' . __FUNCTION__, $this, $name, $table);
		self::_notify(get_called_class() . '::' . __FUNCTION__, $this, $name, $table);
		
		$table = self::_applyFilter(get_class(), __FUNCTION__, $table , array('event' => 'return'));
		$table = self::_applyFilter(get_called_class(), __FUNCTION__, $table , array('event' => 'return'));
		
		return $table;
	}

	/**
	 * Returns the default configuration for the model. The configuration can be set in a child model
	 * using $_config variables.
	 * 
	 * @param array $options Takes in an array of options
	 * 
	 * @return array $options Returns a configured array of options
	 * @access protected
	 */
	protected function _configureConnection(array $options  = array()) {
		
		if (self::_hasAdapter(get_class(), __FUNCTION__))
			return self::_callAdapter(get_class(), __FUNCTION__, $options);
		
		if (self::_hasAdapter(get_called_class(), __FUNCTION__))
			return self::_callAdapter(get_called_class(), __FUNCTION__, $options);
		
		$options= self::_applyFilter(get_class(), __FUNCTION__, $options , array('event' => 'args'));
		$options= self::_applyFilter(get_called_class(), __FUNCTION__, $options , array('event' => 'args'));

		if ($this -> _config['storage'] == 'gridFS')
			$options['gridFS'] = true;
		
		$options = self::_applyFilter(get_class(), __FUNCTION__, $options , array('event' => 'return'));
		$options = self::_applyFilter(get_called_class(), __FUNCTION__, $options , array('event' => 'return'));

		return $options;
	}
	
	/**
	 * If an error occurs in the validation process, the field that the error effects
	 * and the message pertaining to the error will be passed here. The error message will be passed
	 * to PVTemplate::errorMessage function and will be assigned to the error array for the model
	 * instance.
	 * 
	 * @param string $field The field the error is associated with
	 * @param string $error_message A message that describes the error
	 * 
	 * @return void
	 * @access public
	 */
	protected function _addValidationError($field, $error_message) {
			
		if (self::_hasAdapter(get_class(), __FUNCTION__))
			return self::_callAdapter(get_class(), __FUNCTION__, $field, $error_message);
		
		if (self::_hasAdapter(get_called_class(), __FUNCTION__))
			return self::_callAdapter(get_called_class(), __FUNCTION__, $field, $error_message);
		
		$filtered = self::_applyFilter(get_class(), __FUNCTION__, array('field' => $field, 'error_message' => $error_message), array('event' => 'args'));
		$field = $filtered['field'];
		$error_message = $filtered['error_message'];
		
		$filtered = self::_applyFilter(get_called_class(), __FUNCTION__, array('field' => $field, 'error_message' => $error_message), array('event' => 'args'));
		$field = $filtered['field'];
		$error_message = $filtered['error_message'];
		
		$this -> _errors[$field][] = PVTemplate::errorMessage($error_message);
		
		self::_notify(get_class() . '::' . __FUNCTION__, $this, $field, $error_message);
		self::_notify(get_called_class() . '::' . __FUNCTION__, $this, $field, $error_message);
	}
	
	/**
	 * Validation of an model occurs on events. The following function checks sure that the validation
	 * will occur for the event.
	 * 
	 * @param mixed $passed_event The event that has occured in a string format or in an array
	 * @param mixed $allowed_events A string of a single event or an array of events that are allowed
	 * 
	 * @return boolean $match Returns true if the events match, otherwise false
	 * @access public
	 */
	protected function _checkValidationEvent($passed_event, $allowed_events) {
		
		if (self::_hasAdapter(get_class(), __FUNCTION__))
			return self::_callAdapter(get_class(), __FUNCTION__, $passed_event, $allowed_events);

		if (self::_hasAdapter(get_called_class(), __FUNCTION__))
			return self::_callAdapter(get_called_class(), __FUNCTION__, $passed_event, $allowed_events);
		
		$filtered = self::_applyFilter(get_class(), __FUNCTION__, array('passed_event' => $passed_event, 'allowed_events' => $allowed_events), array('event' => 'args'));
		$passed_event = $filtered['passed_event'];
		$allowed_events = $filtered['allowed_events'];
		
		$filtered = self::_applyFilter(get_called_class(), __FUNCTION__, array('passed_event' => $passed_event, 'allowed_events' => $allowed_events), array('event' => 'args'));
		$passed_event = $filtered['passed_event'];
		$allowed_events = $filtered['allowed_events'];
		
		$match = false;
		if(is_string($passed_event) && is_string($allowed_events) &&  $passed_event ==  $allowed_events) {
			$match = true;
		} else if(is_array($passed_event) && is_array($allowed_events)) {
			$match = PVTools::arraySearchRecursive($passed_event, $allowed_events);
		} else if(is_array($passed_event) && !is_array($allowed_events)) {
			$match = in_array($allowed_events, $passed_event);
		} else if(!is_array($passed_event) && is_array($allowed_events)) {
			$match = in_array($passed_event, $allowed_events);
		}
		
		self::_notify(get_class() . '::' . __FUNCTION__, $this, $match, $passed_event, $allowed_events);
		self::_notify(get_called_class() . '::' . __FUNCTION__, $this, $match, $passed_event, $allowed_events);
		
		$match = self::_applyFilter(get_class(), __FUNCTION__, $match , array('event' => 'return'));
		$match = self::_applyFilter(get_called_class(), __FUNCTION__, $match , array('event' => 'return'));
		
		return $match;
	}
	
	/**
	 * Returns the default values for allowed events in an array format. The default events will be checked
	 * against when a model is validating data.
	 * 
	 * @return array $events Returns the events 'create' and 'update' in an array
	 * @access publuc
	 */
	protected function _getValidationRuleDefaults(){
		
		if (self::_hasAdapter(get_class(), __FUNCTION__))
			return self::_callAdapter(get_class(), __FUNCTION__);
		
		if (self::_hasAdapter(get_called_class(), __FUNCTION__))
			return self::_callAdapter(get_called_class(), __FUNCTION__);
		
		$defaults = array(
			'event' => array('create', 'update'),
			'options' => array()
		);
		
		$defaults = self::_applyFilter(get_class(), __FUNCTION__, $defaults , array('event' => 'return'));
		$defaults = self::_applyFilter(get_called_class(), __FUNCTION__, $defaults , array('event' => 'return'));
		
		return $defaults;
	}
	
	/**
	 * Cast data to a certain type. The cast option should be set in the schema in the model.
	 * 
	 * @param mixed $data The data to be casted to a different type
	 * @param string $cast A string of what to cast to the data too. The options are:
	 * 			'boolean', 'integer', 'float', 'string', 'array', 'object', 'null', 'mongoid'
	 * 
	 * @return mixed $data The data to a new casted type, if any
	 * @access protected
	 */
	protected function _castData($data, $cast) {
		
		if (self::_hasAdapter(get_class(), __FUNCTION__))
			return self::_callAdapter(get_class(), __FUNCTION__, $data, $cast);
		
		if (self::_hasAdapter(get_called_class(), __FUNCTION__))
			return self::_callAdapter(get_called_class(), __FUNCTION__, $data, $cast);
		
		$filtered = self::_applyFilter(get_class(), __FUNCTION__, array('data' => $data, 'cast' => $cast), array('event' => 'args'));
		$data = $filtered['data'];
		$cast = $filtered['cast'];
		
		$filtered = self::_applyFilter(get_called_class(), __FUNCTION__, array('data' => $data, 'cast' => $cast), array('event' => 'args'));
		$data = $filtered['data'];
		$cast = $filtered['cast'];
		
		$cast_types = array('boolean', 'integer', 'float', 'string', 'array', 'object', 'null');
		if(in_array($cast, $cast_types)){
			settype($data, $cast);
		} else if($cast == 'mongoid'){
			$data = new MongoID($data);
		} else if($cast == 'array_recursive'){
			settype($data, 'array');
			$data = PVConversions::objectToArray($data);
		}
		
		$data = self::_applyFilter(get_class(), __FUNCTION__, $data , array('event' => 'return'));
		$data = self::_applyFilter(get_called_class(), __FUNCTION__, $data , array('event' => 'return'));
		
		return $data;
	}


	/**
	 * Sets the connection to the specified connection set in the configuration file if one is set. The connection must
	 * also be specified in the the PVDatabase connection file.
	 * 
	 * @return void
	 * @access protected
	 */
	protected function _setConnection() {
		
		if (self::_hasAdapter(get_class(), __FUNCTION__))
			return self::_callAdapter(get_class(), __FUNCTION__);

		if (self::_hasAdapter(get_called_class(), __FUNCTION__))
			return self::_callAdapter(get_called_class(), __FUNCTION__);
		
		if($this ->_config['connection'] != null) {
			$this -> _config['stored_connection'] = PVDatabase::getDatabaseLink();
			$this -> _config['stored_connection_name'] = PVDatabase::getConnectionName();
			PVDatabase::setDatabase($this ->_config['connection']);
		}
		
		return null;
		
	}
	
	/**
	 * Resets the connection the original connection before before the connection was changed
	 * using _setConnection.
	 * 
	 * @return void
	 * @access protected
	 */
	protected function _resetConnection() {
		
		if (self::_hasAdapter(get_class(), __FUNCTION__))
			return self::_callAdapter(get_class(), __FUNCTION__);

		if (self::_hasAdapter(get_called_class(), __FUNCTION__))
			return self::_callAdapter(get_called_class(), __FUNCTION__);
		
		if($this ->_config['connection'] != null) {
			PVDatabase::setDatabase($this -> _config['stored_connection_name']);
		}
		
	}
	
	protected function _getPaginationData($current_page, $results_per_page, $joins ='') {
		
		PVDatabase::getPagininationOffset($table, $joins , $where_clause = '', $current_page, $results_per_page , $order_by = '');
		
	}
	
	protected function _writeCache($name, $data) {
		
	}
	
	protected function _readCache($name, $data) {
		
	}
	
	protected function _formatCacheName($args) {
		
		$name = '';
		
		if(is_array($args)) {
				
			foreach($args as $key => $value) {
				
				
			}
		}
	}

	private function convertToPVStandardSearchQuery($data) {
		$args = array();
		if (isset($data['conditions'])) {
			foreach ($data['conditions'] as $key => $value) {
				if (is_array($value)) {
					$string = '';
					foreach ($value as $subkey => $subvalue) {
						$subkey = strtolower($subkey);
						//if($subkey=='or')

					}//end 2nd foreach
				} else {
					$args[$key] = $value;
				}
			}//end foreach
		}

	}

}
