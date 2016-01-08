<?php
/**
 * EagerLoaderBehavior
 */
class EagerLoaderBehavior extends ModelBehavior {

/**
 * beforeFind callback
 *
 * @param Model $model Model using the behavior
 * @param array $query Query
 * @return array
 */
	public function beforeFind(Model $model, $query) {
		if (!empty($query['contain'])) {
			$EagerLoader = ClassRegistry::init('EagerLoader.EagerLoader');
			$query = $EagerLoader->transformQuery($model, $query);
		}
		return $query;
	}

/**
 * afterFind callback
 *
 * @param Model $model Model using the behavior
 * @param array $results The results of the find operation
 * @param bool $primary Whether this model is being queried directly
 * @return array
 */
	public function afterFind(Model $model, $results, $primary = false) {
		$id = Hash::get($results, '0.EagerLoader.id');
		if ($id) {
			$EagerLoader = ClassRegistry::init('EagerLoader.EagerLoader');
			$EagerLoader->id = $id;
			foreach ($results as &$result) {
				unset($result['EagerLoader']);
			}
			return $EagerLoader->loadExternal($model->alias, $results);
		}
	}
}
