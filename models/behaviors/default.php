<?php
/**
 * DefaultBehavior
 *
 * Summary:
 * Maintains that one and only one record is marked as a default when adding or
 * editing records. Default can apply to all records in the whole table, or a
 * set of records identified by a single or multiple "group" keys.
 *
 * Description:
 *
 * Consider the following example
 *
 * Folder, 		Page, 		Visible
 * /,			About Us,	1
 * /,			News,		1
 * /,			Home,		1
 * /products,	AVG,		1
 * /products,	Good,		1
 * /products,	Best,		1
 * /products,	Impossible,	0
 *
 * - Save
 * 		- If record wants to be default
 * 			- Mark all other records in set as default
 * 		- If removing default
 *	 		- If love thy neighbour, mark the next record as the defualt
 * 			- Otherwise mark the first record in the set as default
 * - Delete
 * 		- If love thy neighbour, mark the next record as the defualt
 * 		- Otherwise mark the first record in the set as default
 *
 * Example Usage:
 *
 * var $actsAs = array(
 *		'Default' => array(
 *			'default_field' => 'default',
 * 			'order_fields' => 'ordering',
 * 			'group_fields' => 'folder_id',
 * 			'love_thy_neighbour' => true,
 * 			'group_conditions' => array(
 *				'visible' => 1
 * 			)
 * 		);
 * );
 *
 * Settings:
 *
 * default_field
 * The field in your table that marks a record as the default.
 * Defualt: "default"
 *
 * order_fields
 * The order used to determine neighbours or the first row in a set
 * Default: "id"
 *
 * group_fields
 * Used to specify subsets within your table, such as if you were setting a default address for a user.
 * You would want to make a subset for that user with "user_id" for this value.
 * Defualt: false
 *
 * love_thy_neighbour
 * When deleting / or removing a default from a row, another row must receive the default. This will determine
 * if the next (or previous if their is no next row) will get the default (true) or the first record in the set
 * will receive the default.
 *
 * group_conditions
 * Specify any further conditions that are necessary to determine if a record can receive the default. For example
 * there would be no point in giving a page in a CMS the default if it was marked as invisible.
 *
 * @author Craig Morris <craig@waww.com.au>
 * @link http://www.waww.com.au/default
 * @link http://gist.github.com/121223
 * @copyright (c) 2009 Craig Morris
 * @license MIT License - http://www.opensource.org/licenses/mit-license.php
 *
 */
class DefaultBehavior extends ModelBehavior
{
	/**
	* Default settings for a model that has this behavior attached.
	*
	* @var array
	* @access protected
	*/
	protected $_defaults = array(
		'default_field' => 'default',
		'order_fields' => 'id',
		'group_fields' => false,
		'love_thy_neighbour' => true,
		'group_conditions' => array()
	);

	function setup(&$model, $config = array()) {

		// Ensure config is an array
		if (!is_array($config)) {
			$config = array($config);
		}

		// Merge defaults with passed config and set as settings
		$this->settings[$model->alias] = array_merge($this->_defaults, $config);

		// If group_fields in settings is a string, make it an array
		if ( is_string($this->settings[$model->alias]['group_fields']) ) {
			$this->settings[$model->alias]['group_fields'] = array($this->settings[$model->alias]['group_fields']);
		}

		// If order_fields in settings is a string, make it an array
		if ( is_string($this->settings[$model->alias]['order_fields']) ) {
			$this->settings[$model->alias]['order_fields'] = array($this->settings[$model->alias]['order_fields']);
		}
	}

	function afterSave(&$model, $created) 
	{
		extract($this->settings[$model->alias]);
		$model->contain();
		$data = $model->read(null, $model->id);
		$id = $model->id;

		// another defualt out there???
		$conditions = $this->_getSetConditions($model, $data);
		$other_default = $model->find('count', array('conditions' => array_merge($conditions, array($default_field => 1)), 'contain' => array()));

		// this is the new default field and there is an old default, mark it as not default.
		if ( !empty($data[$model->alias][$default_field]) && $other_default ) {
			$update = array(
				$default_field => 0
			);
			$model->contain();
			$model->updateAll($update, $conditions);
		}
		// this is not the default and there is no other default, find the (next) neighbour and make it defualt.
		else if ( !$other_default ) {
			if ( !$this->_pass($model, $data) ) {
				// if we cant pass to a neighbour, force it as default.
				$model->saveField($default_field, 1, array('callbacks' => false));
			}
		}
		
		$model->data = $data;
		$model->id = $id;
		return true;
	}

	function beforeDelete(&$model, $cascade) {
		extract($this->settings[$model->alias]);
		$data = $model->read(null, $model->id);

		// if it is a default, we need to pass it on.
		if ( $data[$model->alias][$default_field] ) {
			$this->_pass($model, $data);
		}
		
		return true;
	}

	/**
	* Constructs conditions array for all possible candidates to be default.
	*
	* @todo This is called twice through a certain path in afterSave
	* @param mixed $model
	* @param mixed $data
	*/
	function _getSetConditions(&$model, $data) {
		extract($this->settings[$model->alias]);

		// exclude the row being updated / deleted
		$conditions = array(
			$model->alias . '.' . $model->primaryKey . ' !=' => $model->id
		);

		// restrict to within the subset
		if ( $group_fields ) foreach ($group_fields as $field) {
			if ( strpos($field, '.') !== false ) {
				list($modelName, $fieldName) = explode('.', $field);
			}
			else {
				$fieldName = $field;
			}
			$conditions[$field] = $data[$model->alias][$fieldName];
		}

		// add any other conditions passed in the settings.
		$conditions = array_merge($conditions, $group_conditions);
		return $conditions;
	}

	function _pass(&$model, $data) {
		extract($this->settings[$model->alias]);

		// get conditions for set
		$conditions = $this->_getSetConditions($model, $data);

		// get neighbours of the current records
		if ( $love_thy_neighbour )
		{
			// add id to fields as we need to retrieve it to update the record.
			$fields = $order_fields;
			$fields[] = $model->primaryKey;

			// fetch
			$model->contain();
			$neighbours = $model->findNeighbours($conditions, $fields, $data[$model->alias][$order_fields[0]]);

			// return false if we dont have a neighbour
			if ( empty($neighbours['prev']) && empty($neighbours['next']) ) {
				return false;
			}

			// update
			$model->id = !empty($neighbours['next']) ? $neighbours['next'][$model->alias]['id'] : $neighbours['prev'][$model->alias]['id'];
		}
		// get the first record in the set.
		else {
			$order = $order_fields;
			$first = $model->find('first', compact('conditions', 'order'));
			$model->id = $first[$model->alias][$model->primaryKey];
		}

		return $model->saveField($default_field, 1, array('callbacks' => false));
	}

}
?>