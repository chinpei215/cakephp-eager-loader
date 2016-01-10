<?php
class Sample extends AppModel {

	public $belongsTo = array('Apple');

/**
 * afterFind
 *
 * @param array $results Results
 * @param bool $primary Primary
 * @return array
 */
	public function afterFind($results, $primary = false) {
		foreach ($results as &$result) {
			$apple = $this->Apple->find('first', array(
				'fields' => array(
					'Apple.id',
					'Apple.apple_id',
				),
				'contain' => array(
					'ParentApple' => array('fields' => array('id', 'name')),
				),
				'conditions' => array('Apple.id' => $result[$this->alias]['id']),
			));

			if ($apple) {
				$apple = $apple['Apple'] + $apple;
				unset($apple['Apple']);
			}
			$result[$this->alias]['Apple'] = $apple;
		}
		return $results;
	}
}
