<?php
class EagerLoadableBehavior extends ModelBehavior {

	private $queries = [];

	public function setup(Model $model, $config = array()) {
		$this->settings[$model->alias] = $config;
	}

	public function beforeFind(Model $model, $query) {
		static $id = 0;

		$query += [
			'joins' => [],
			'contain' => false,
		];

		if (!$query['contain']) {
			return true;
		}

		$EagerLoader = ClassRegistry::init('EagerLoadable.EagerLoader');
		$EagerLoader->id = ++$id;

		$query = $EagerLoader->attachAssociations($model, $query);
		$this->queries[$id] = $query;

		return $query;
	}

	public function afterFind(Model $model, $results, $primary = false) {
		$id = Hash::get($results, '0.EagerLoader.id');
		if (isset($this->queries[$id])) {
			$EagerLoader = ClassRegistry::init('EagerLoadable.EagerLoader');
			$EagerLoader->id = $id;
			return $EagerLoader->loadExternal($this->queries[$id], $results);
		}
	}
}
