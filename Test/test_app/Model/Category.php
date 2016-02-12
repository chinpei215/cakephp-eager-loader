<?php
class Category extends AppModel {

	public $belongsTo = array(
		'ParentCategory' => array(
			'className' => 'Category',
			'foreignKey' => 'parent_id',
		),
	);

/**
 * {@inheritDoc}
 */
	public function __construct($id = false, $table = null, $ds = null) {
		parent::__construct($id, $table, $ds);
		$this->virtualFields['is_root'] = "CASE WHEN {$this->alias}.parent_id = 0 THEN 1 ELSE 0 END";
	}
}
