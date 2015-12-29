<?php

class EagerLoader extends Model
{
	public $useTable = false;

	private $options = [];

	private $containOptions = [
		'associations' => 1,
		'foreignKey' => 1,
		'conditions' => 1,
		'fields' => 1,
		'sort' => 1,
		'matching' => 1,
		'queryBuilder' => 1,
		'finder' => 1,
		'joinType' => 1,
		'strategy' => 1,
		'negateMatch' => 1
	];

	public function isVirtualField($field) {
		return true;
	}

	public function attachAssociations(Model $model, $query, $primary = true) {
		$db = $model->getDataSource();
		$query['fields'] = $db->fields($model);

		$containments = $this->reformatContain($query['contain']);

		$queue = [];
		foreach ($containments as $alias => $options) {
			$queue[] = [
				'container' => $model,
				'aliasPath' => ($primary ? '' : $model->alias . '.') . $alias,
				'alias' => $alias,
				'options' => $options,
			];
		}

		$joined = [];
		$external = [];

		while ($data = array_shift($queue)) {
			$alias = $data['alias'];
			$container = $data['container'];
			$aliasPath = $data['aliasPath'];
			$options = $data['options'];

			$target = $container->$alias;
			if (!$target instanceof Model) {
				trigger_error(sprintf('Model "%s" is not associated with model "%s"', $container->alias, $alias), E_USER_WARNING);
				continue;
			}

			if (isset($joined[$alias]) || $model->useDbConfig !== $target->useDbConfig || (!isset($container->belongsTo[$alias]) && !isset($container->hasOne[$alias]))) {
				$external[] = $data;
				continue;
			}

			foreach ($options as $key => $val) {
				if (!isset($this->containOptions[$key])) {
					$queue[] = [
						'container' => $target,
						'aliasPath' => $aliasPath . '.' . $key,
						'alias' => $key,
						'options' => $val,
					];

					unset($options[$key]);
				}
			}

			if (isset($container->belongsTo[$alias])) {
				$foreignKey = $container->belongsTo[$alias]['foreignKey'];
				$conditions = $db->getConstraint('belongsTo', $container, $target, $target->alias, compact('foreignKey'));
				$field = $container->schema($foreignKey);
				$joinType = ($field['null'] ? 'LEFT' : 'INNER');
			} else {
				$foreignKey = $container->hasOne[$alias]['foreignKey'];
				$conditions = $db->getConstraint('hasOne', $container, $target, $target->alias, compact('foreignKey'));
				$joinType = 'LEFT';
			}

			if (isset($options['conditions'])) {
				$conditions = array_merge($conditions, (array)$options['conditions']);
			}

			$query['joins'][] = [
				'type' => $joinType,
				'table' => $db->fullTableName($target),
				'alias' => $alias,
				'conditions' => $conditions,
			];

			$query['fields'] = array_merge($query['fields'], $db->fields($target));

			$joined[$alias] = $aliasPath;
		}

		$query['fields'][] = '(' . $this->id . ') AS EagerLoader__id';
		$query['recursive'] = -1;
		$query['contain'] = false;
		$query['eagerLoader'] = [
			'joined' => $joined,
			'external' => $external,
		];

		return $query;
	}

	public function loadExternal($query, array $results) {
		foreach ($query['eagerLoader']['external'] as $data) {
			$container = $data['container'];
			$alias = $data['alias'];
			$aliasPath = $data['aliasPath'];
			$options = $data['options'];
			$target = $container->$alias;

			$db = $target->getDataSource();

			// TODO: reformatContain
			$contain = array_diff_key($options, $this->containOptions);
			$options = array_diff_key($options, $contain);
			$options['contain'] = $contain;
			$options += ['conditions' => []];

			if (isset($container->hasMany[$alias]) && !isset($options['limit'])) {
				$ids = Hash::extract($results, '{n}.' . $container->alias . '.' . $container->primaryKey);

				$foreignKey = $container->hasMany[$alias]['foreignKey'];
				$options['conditions'] = array_merge($options['conditions'], [$alias . '.' . $foreignKey => $ids]);
				$options = $this->attachAssociations($target, $options, false);
				
				$many = $this->loadExternal($options, $db->read($target, $options));

				$indexed = Hash::combine($many, "{n}.$alias.{$target->primaryKey}", "{n}.$alias", "{n}.$alias.{$foreignKey}");

				foreach ($results as &$result) {
					$id = $result[$container->alias][$container->primaryKey];
					$insert = isset($indexed[$id]) ? array_values($indexed[$id]) : [];
					$result = Hash::insert($result, $aliasPath, $insert);
				}

				unset($result);
			} else {
				//TODO
			}
		}

		foreach ($results as &$result) {
			unset($result[$this->alias]);
			foreach ($query['eagerLoader']['joined'] as $alias => $aliasPath) {
				if ($alias !== $aliasPath) {
					$result = Hash::insert($result, $aliasPath, $result[$alias] + (array)Hash::get($result, $aliasPath));
					unset($result[$alias]);
				}
			}
		}

		return $results;
	}

	private function reformatContain($associations, $original = []) {
		$result = $original;

		foreach ((array)$associations as $model => $options) {
			$pointer =& $result;
			if (is_int($model)) {
				$model = $options;
				$options = [];
			}

			//if ($options instanceof EagerLoadable) {
			//	$options = $options->asContainArray();
			//	$model = key($options);
			//	$options = current($options);
			//}

			if (isset($this->containOptions[$model])) {
				$pointer[$model] = $options;
				continue;
			}

			if (strpos($model, '.')) {
				$path = explode('.', $model);
				$model = array_pop($path);
				foreach ($path as $t) {
					$pointer += [$t => []];
					$pointer =& $pointer[$t];
				}
			}

			if (is_array($options)) {
				//$options = isset($options['config']) ?
				//	$options['config'] + $options['associations'] :
				//	$options;

				$options = $this->reformatContain(
					$options,
					isset($pointer[$model]) ? $pointer[$model] : []
				);
			}

			//if ($options instanceof Closure) {
			//	$options = ['queryBuilder' => $options];
			//}

			$pointer += [$model => []];
			$pointer[$model] = $options + $pointer[$model];
		}

		return $result;
	}

}
