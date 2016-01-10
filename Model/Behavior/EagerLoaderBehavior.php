<?php
App::uses('EagerLoader', 'EagerLoader.Model');

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
		return EagerLoader::handleBeforeFind($model, $query);
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
		return EagerLoader::handleAfterFind($model, $results);
	}
}
