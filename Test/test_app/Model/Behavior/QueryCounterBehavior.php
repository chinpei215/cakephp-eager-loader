<?php

class QueryCounterBehavior extends ModelBehavior {

	private $runtime = array(); // @codingStandardsIgnoreLine

/**
 * beforeFind
 *
 * @param Model $model Model
 * @param array $query Query
 * @return mixed
 */
	public function beforeFind(Model $model, $query) {
		$db = $model->getDataSource();
		$log = $db->getLog();
		$this->runtime[$model->alias]['count'] = -$log['count'];
		return true;
	}

/**
 * afterFind 
 *
 * @param Model $model Model
 * @param array $results Results
 * @param bool $primary Primary
 * @return mixed
 */
	public function afterFind(Model $model, $results, $primary = false) {
		$db = $model->getDataSource();

		$log = $db->getLog();
		$count = $log['count'];

		if ($db instanceof Sqlite) {
			foreach ($log['log'] as $log) {
				if (strpos($log['query'], 'sqlite_master') !== false) {
					--$count;
				}
			}
		}

		$this->runtime[$model->alias]['count'] += $count;
	}

/**
 * queryCount
 *
 * @param Model $model Model
 * @return int
 */
	public function queryCount(Model $model) {
		return $this->runtime[$model->alias]['count'];
	}

}
