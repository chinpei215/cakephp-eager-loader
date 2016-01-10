<?php
class Apple extends AppModel {

	public $belongsTo = array(
		'ParentApple' => array(
			'className' => 'Apple',
			'foreignKey' => 'apple_id'
		)
	);

	public $hasOne = array(
		'SampleA' => array(
			'className' => 'Sample',
			'conditions' => array(
				'SampleA.id' => 1,
			),
			'external' => true,
		),
	);

	public $hasMany = array(
		'Sample'
	);

/**
 * Constructor
 *
 * @param mixed $id ID
 * @param string $table Table
 * @param string $ds DataSource
 */
	public function __construct($id = false, $table = null, $ds = null) {
		parent::__construct($id, $table, $ds);

		$this->hasOne['NextApple'] = array(
			'className' => 'Apple',
			'foreignKey' => 'apple_id',
			'finderQuery' => $this->getNextAppleFinderQuery(),
		);
	}

/**
 * Finder query for NextApple
 *
 * @return string
 */
	public function getNextAppleFinderQuery() {
		$db = $this->getDataSource();

		return $db->buildStatement(
			array(
				'fields' => $db->fields($this, 'NextApple', array('id', 'apple_id', 'name', 'color', 'created', 'modified')),
				'alias' => 'NextApple',
				'table' => $db->fullTableName($this),
				'conditions' => array(
					'id >' => '{$__cakeID__$}',
				),
				'limit' => 1,
			),
			$this
		);
	}
}
