<?php
class EagerLoadableBehavior extends ModelBehavior {

	public function setup(Model $model, $config = array()) {
		$this->settings[$model->alias] = $config;
	}

	public function beforeFind(Model $model, $query) {
		if (!empty($query['contain'])) {
			$EagerLoader = ClassRegistry::init('EagerLoadable.EagerLoader');
			$query = $EagerLoader->transformQuery($model, $query);
		}
		return $query;
	}

	public function afterFind(Model $model, $results, $primary = false) {
		$id = Hash::get($results, '0.EagerLoader.id');
		if ($id) {
			$EagerLoader = ClassRegistry::init('EagerLoadable.EagerLoader');
			$EagerLoader->id = $id;
			foreach ($results as &$result) {
				unset($result['EagerLoader']);
			}
			return $EagerLoader->loadExternal($model->alias, $results);
		}
	}
}
