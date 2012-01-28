<?php

Abstract Class Model extends PVStaticInstance {

	protected $registry;
	protected $errors;
	protected $_config = array(
		'create_table' => true, 
		'column_check' => true, 
		'storage' => ''
		);

	function __construct($registry = null) {

		if (self::_hasAdapter(get_called_class(), __FUNCTION__))
			return self::_callAdapter(get_called_class(), __FUNCTION__, $registry);

		$registry = self::_applyFilter(get_class(), __FUNCTION__, $registry, array('event' => 'args'));
		$registry = self::_applyFilter(get_called_class(), __FUNCTION__, $registry, array('event' => 'args'));

		if ($registry == null)
			$this -> registry = new PVCollection();
		else
			$this -> registry = $registry;

		self::_notify(get_class() . '::' . __FUNCTION__, $this, $registry);
		self::_notify(get_called_class() . '::' . __FUNCTION__, $this, $registry);

	}
	
	/**
	 * Checks the schema that was defined in the models $_schema array. If the schema does not exist in the database,
	 * the table and columns associated with that schema will be created. Will also new columns to the database. This method
	 * should not be used with schema databases such as Mongo.
	 * 
	 * @return void
	 * @access protected
	 */
	protected function checkSchema() {

		if (self::_hasAdapter(get_class(), __FUNCTION__))
			return self::_callAdapter(get_class(), __FUNCTION__);

		if (self::_hasAdapter(get_called_class(), __FUNCTION__))
			return self::_callAdapter(get_called_class(), __FUNCTION__);

		$table_name = $this -> _formTableName(get_class($this));
		$tablename = PVDatabase::formatTableName(strtolower($table_name));
		
		$check_table_name = (PVDatabase::getDatabaseType() == 'postgresql') ? PVDatabase::formatTableName(strtolower($table_name), false) : $tablename;
		$schema = PVDatabase::getSchema(false);

		if (!PVDatabase::tableExist($check_table_name, $schema) && isset($this -> _schema) && $this -> _config['create_table']) {
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
		} else if (isset($this -> _schema) && $this -> _config['column_check']) {
			$schema = $this -> _schema;

			foreach ($schema as $key => $value) {
				if (!PVDatabase::columnExist($check_table_name, $key)) {
					if (isset($value['default']) && empty($value['default']) && !($value['default'] === 0))
						$value['default'] = '\'\'';
					else if (isset($value['default']) && $value['type'] == 'string')
						$value['default'] = '\'' . $value['default'] . '\'';

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
	 * 			- 'event' _array_: An array of events that validation will occur. Default is array('create', 'update')
	 * 
	 * @return boolean $validation Returns true if no errors are found, otherwise returns false
	 * @access public
	 */
	public function validate($data, $options = array()) {

		if (self::_hasAdapter(get_class(), __FUNCTION__))
			return self::_callAdapter(get_class(), __FUNCTION__, $data, $options);

		if (self::_hasAdapter(get_called_class(), __FUNCTION__))
			return self::_callAdapter(get_called_class(), __FUNCTION__, $data, $options);

		$defaults = array('event' => '');

		$options += $defaults;

		$filtered = self::_applyFilter(get_class(), __FUNCTION__, array('data' => $data, 'options' => $options), array('event' => 'args'));
		$data = $filtered['data'];
		$options = $filtered['options'];

		$data = self::_applyFilter(get_called_class(), __FUNCTION__, array('data' => $data, 'options' => $options), array('event' => 'args'));
		$data = $filtered['data'];
		$options = $filtered['options'];

		$hasError = true;
		$this -> errors = array();

		if (!empty($this -> _validators)) {

			foreach ($this->_validators as $field => $rules) {

				foreach ($rules as $key => $rule) {
						
					if(!isset($rule['event'])) {
						$rule_defaults = $this -> _getValidationRuleDefaults();
						$rule += $rule_defaults;
					}

					if ($this -> _checkValidationEvent($options['event'], $rule['event'])  && !PVValidator::check($key, @$data[$field])) {
						$hasError = false;
						$this -> _addValidationError($field, $rule['error']);
					}

				}//end second foreach
			}//end foreach

		}//end validators

		$this -> registry -> errors = $this -> errors;

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
		
		$defaults = array('validate' => true, 'use_schema' => true, 'sync_data' => true, 'validate_options' => array('event' => 'create'));
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
			$defaults = $this -> _getModelDefaults();
			$data += $defaults;

			$input_data = array();
			$primary_keys = array();
			$auto_incremented_field = '';
			
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
	
					if ($field_options['unique'] == true) {
						//$primary_key=$field;
					}
	
				}//end foreach
			} else {
				$input_data = $data;
			}
		
			$options = $this -> _configureConnection($options);
			$id = PVDatabase::preparedReturnLastInsert($table_name, $auto_incremented_field, $table_name, $input_data, array(), $options);

			if ($id) {
				$conditions = array('conditions' => array($auto_incremented_field => $id));

				$this -> first($conditions);
				$created = true;
			}
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
	public function update($data, $conditions = array(), $options = array()) {

		if (self::_hasAdapter(get_class(), __FUNCTION__))
			return self::_callAdapter(get_class(), __FUNCTION__, $data, $conditions, $options);

		if (self::_hasAdapter(get_called_class(), __FUNCTION__))
			return self::_callAdapter(get_called_class(), __FUNCTION__, $data, $conditions, $options);

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
		
		if (!$options['validate'] || $this -> validate($data, $options['validate_options'])) {
			
			$table_name = $this -> _formTableName(get_class($this));
			$table_name = PVDatabase::formatTableName($table_name);
			$defaults = $this -> _getModelDefaults();
			
			
			$input_data = array();
			$primary_key = '';
			$wherelist = isset($conditions['conditions']) ? $conditions['conditions'] : array();
			
			if($options['use_schema']) {
				$data += $defaults;
				
				foreach ($this->_schema as $field => $field_options) {
					$field_options += $this -> _getFieldOptionsDefaults();
	
					if ((!isset($field_options['null']) || (isset($field_options['null']) && !$field_options['null'])) || !empty($data[$field])) {
						if ($field_options['primary_key']) {
							$primary_key = $field;
							$wherelist[$field] = (!empty($this -> _collection -> $field)) ? $field_options['default'] : $this -> _collection -> $field;
						}
	
						$input_data[$field] = (!$data[$field]) ? $this -> $field : $data[$field];
					}
	
				}//end foreach
			} else {
				$input_data = $data;
				$wherelist = isset($conditions['conditions']) ? $conditions['conditions'] : array();
			}
			
			$options = $this -> _configureConnection($options);
			$result = PVDatabase::preparedUpdate($table_name, $input_data, $wherelist, array(), array(), $options);

			$this -> addToCollection($input_data);

			if ($options['sync_data'])
				$this -> sync();
		}

		self::_notify(get_class() . '::' . __FUNCTION__, $this, $result, $data, $conditions);
		self::_notify(get_called_class() . '::' . __FUNCTION__, $this, $result, $data, $conditions);
		
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
	public function delete($data, array $options = array()) {

		if (self::_hasAdapter(get_class(), __FUNCTION__))
			return self::_callAdapter(get_class(), __FUNCTION__, $data);

		if (self::_hasAdapter(get_called_class(), __FUNCTION__))
			return self::_callAdapter(get_called_class(), __FUNCTION__, $data);

		$data = self::_applyFilter(get_class(), __FUNCTION__, $data, array('event' => 'args'));
		$data = self::_applyFilter(get_called_class(), __FUNCTION__, $data, array('event' => 'args'));

		$table_name = $this -> _formTableName(get_class($this));
		$table_name = PVDatabase::formatTableName(strtolower($table_name));

		$options = $this -> _configureConnection($options);
		$result = PVDatabase::preparedDelete($table_name, $data, array(), $options);
		
		self::_notify(get_class() . '::' . __FUNCTION__, $this, $result, $data);
		self::_notify(get_called_class() . '::' . __FUNCTION__, $this, $result, $data);

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

		if (self::_hasAdapter(get_called_class(), __FUNCTION__))
			return self::_callAdapter(get_called_class(), __FUNCTION__, $conditions);

		$conditions = self::_applyFilter(get_class(), __FUNCTION__, $conditions, array('event' => 'args'));
		$conditions = self::_applyFilter(get_called_class(), __FUNCTION__, $conditions, array('event' => 'args'));

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

		} else {

			$this -> checkSchema();

			$defaults = array('conditions' => array());
			$conditions += $defaults;
			$table_name = $this -> _formTableName(get_class($this));
			$table_name = PVDatabase::formatTableName(strtolower($table_name));
			$input_data = array();
			$query = 'SELECT * FROM ' . $table_name . ' ';

			if (isset($conditions['join']) && isset($this -> _joins)) {
				foreach ($conditions['join'] as $join) {

					if (isset($this -> _joins[$join]))
						$query .= $this -> _joinTable($this -> _joins[$join]) . ' ';

				}//end foreach
			}

			$WHERE_CLAUSE = '';
			$first = true;
			$placeholder_count = 1;
			foreach ($conditions['conditions'] as $key => $condition) {
				if (is_array($condition)) {

				} else {
					if ($first)
						$WHERE_CLAUSE .= $key . '='.PVDatabase::getPreparedPlaceHolder($placeholder_count);
					else
						$WHERE_CLAUSE .= ' AND ' . $key . '='.PVDatabase::getPreparedPlaceHolder($placeholder_count);

					$input_data[$key] = $condition;
					$first = false;
				}
				$placeholder_count++;
			}//end foreach

			if (!empty($WHERE_CLAUSE)) {
				$query .= 'WHERE ' . $WHERE_CLAUSE . ' ';
			}

			$query .= 'LIMIT 1';
			
			$result = PVDatabase::preparedSelect($query, $input_data, $formats = '');
			$row = PVDatabase::fetchArray($result);
			
			if(!empty($row)) {
				foreach ($row as $key => $value) {
					if (!PVValidator::isInteger($key))
						$this -> addToCollectionWithName($key, $value);
				}
			}
		}

		self::_notify(get_class() . '::' . __FUNCTION__, $this, $conditions);
		self::_notify(get_called_class() . '::' . __FUNCTION__, $this, $conditions);
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
	 * 
	 * @return void Return results are added as part of the model
	 * @access public
	 * @todo Add in the ability for pagination
	 */
	public function find($conditions = array(), $options = array()) {

		if (self::_hasAdapter(get_called_class(), __FUNCTION__))
			return self::_callAdapter(get_called_class(), __FUNCTION__, $conditions);

		$conditions = self::_applyFilter(get_class(), __FUNCTION__, $conditions, array('event' => 'args'));
		$conditions = self::_applyFilter(get_called_class(), __FUNCTION__, $conditions, array('event' => 'args'));
		if (PVDatabase::getDatabaseType() == 'mongo') {
			
			$args = array(
				'where' => isset($conditions['conditions']) ? $conditions['conditions'] : array(), 
				'fields' => isset($conditions['fields']) ? $conditions['fields'] : array(), 
				'table' => $this -> _formTableName(get_class($this)),
				'limit' => isset($conditions['limit']) ? $conditions['limit'] : null,
				'offset' => isset($conditions['offset']) ? $conditions['offset'] : null,
				'order_by' => isset($conditions['order_by']) ? $conditions['order_by'] : null,
				
			);
			
			$options = $this -> _configureConnection($options);
			$result = PVDatabase::selectStatement($args, $options);

			foreach ($result as $row) {
				$this -> addToCollection($row);
			}

		} else {
			$this -> checkSchema();

			$table_name = $this -> _formTableName(get_class($this));
			$table_name = PVDatabase::formatTableName(strtolower($table_name));
			$input_data = array();
			$query = 'SELECT * FROM ' . $table_name . ' ';

			if (isset($conditions['join']) && isset($this -> _joins)) {
				foreach ($conditions['join'] as $join) {

					if (isset($this -> _joins[$join]))
						$query .= $this -> _joinTable($this -> _joins[$join]) . ' ';

				}//end foreach
			}

			$WHERE_CLAUSE = '';
			$first = true;
			$placeholder_count = 1;
			if (isset($conditions['conditions'])) {
				foreach ($conditions['conditions'] as $key => $condition) {
					if (is_array($condition)) {

					} else {
						if ($first)
							$WHERE_CLAUSE .= $key . '='.PVDatabase::getPreparedPlaceHolder($placeholder_count);
						else
							$WHERE_CLAUSE .= ' AND ' . $key . '='.PVDatabase::getPreparedPlaceHolder($placeholder_count);

						$input_data[$key] = $condition;
						$first = false;
					}
					$placeholder_count++;
				}//end foreach

				if (!empty($WHERE_CLAUSE)) {
					$query .= 'WHERE ' . $WHERE_CLAUSE . ' ';
				}
			}
			
			$result = PVDatabase::preparedSelect($query, $input_data);
			
			if(PVDatabase::getDatabaseType() == 'postgresql') {
				while($row = PVDatabase::fetchFields($result))
					$this -> addToCollection($row);
			} else {
				$rows = PVDatabase::fetchFields($result);
				
				if(!empty($rows)) {
					foreach ($rows as $row) {
					
						$this -> addToCollection($row);
					}
				}
			}
		}
		
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

		if (isset($this -> registry -> errors[$error_name])) {
			foreach ($this->registry->errors[$error_name] as $error) {
				echo $error;
			}//end foreach
		}

	}//endError
	
	
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

		$table = ($args['format_table']) ? PVDatabase::formatTableName(strtolower($args['table'])) : $args['table'];

		switch(strtolower($args['type'])) :
			case 'left' :
				$join = 'LEFT JOIN';
				break;
			case 'right' :
				$join = 'RIGHT JOIN';
				break;
			case 'join' :
				$join = 'JOIN';
				break;
			case 'full' :
				$join = 'FULL JOIN';
				break;
			default :
				$join = 'NATURAL JOIN';
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
				$defaults[$key] = (isset($this -> data -> $key)) ? $this -> data -> $key : @$value['default'];
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
	 * letters as seperate words and adding an '_' between them. The string will also be made to lower case.
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
		
		$this -> errors[$field][] = PVTemplate::errorMessage($error_message);
		
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
			'event' => array('create', 'update')
		);
		
		$defaults = self::_applyFilter(get_class(), __FUNCTION__, $defaults , array('event' => 'return'));
		$defaults = self::_applyFilter(get_called_class(), __FUNCTION__, $defaults , array('event' => 'return'));
		
		return $defaults;
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
?>
