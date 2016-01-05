<?php

class EagerLoader extends Model {

	public $useTable = false;

	private $settings = [];

	private $containOptions = [
		'conditions' => 1,
		'fields' => 1,
		'order' => 1,
		'limit' => 1,
	];

	public function isVirtualField($field) {
		return true;
	}

/**
 * 
 *
 * @param $model
 * @param $query
 *
 * @return array
 */
	public function transformQuery(Model $model, $query) {
		static $id = 0;
		$this->id = (++$id);

		$contain = $this->reformatContain($query['contain']);
		foreach ($contain['contain'] as $key => $val) {
			$this->parseContain($model, $key, $val, [
				'root' => $model->alias, 
				'aliasPath' => $model->alias,
			]);
		}

		$query = $this->attachAssociations($model, $model->alias, $query);
		$query['fields'] = array_merge(['(' . $this->id . ') AS EagerLoader__id'], $query['fields']);

		return $query;
	}

/**
 * 
 *
 * @param $model
 * @param $query
 *
 * @return array
 */
	public function attachAssociations(Model $model, $path, array $query) {
		$db = $model->getDataSource();

		$query += [
			'fields' => [],
			'conditions' => [],
		];
		$query['fields'] = array_merge((array)$query['fields'], $db->fields($model));

		$map =& $this->settings[$this->id]['map'][$path];

		foreach ($map['joins'] as $meta) {
			extract($meta);

			$joinType = 'LEFT';
			if ($belong) {
				$field = $parent->schema($parentKey);
				$joinType = ($field['null'] ? 'LEFT' : 'INNER');
			}

			$conditions = [
				"$parentAlias.$parentKey" => $db->identifier("$alias.$targetKey")
			];
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
		}

		$query['recursive'] = -1;
		$query['contain'] = false;

		return $query;
	}

/**
 * 
 * @param string $path
 * @param array $results
 *
 * @return array
 */
	public function loadExternal($path, array $results) {
		$map =& $this->settings[$this->id]['map'][$path];

		foreach ($map['externals'] as $meta) {
			extract($meta);

			$db = $target->getDataSource();

			if ($has && $belong) {
				$options += [
					'fields' => [],
				];
				$options['fields'] = array_merge((array)$options['fields'], $db->fields($habtm));
				$options['joins'][] = [
					'type' => 'INNER',
					'table' => $db->fullTableName($habtm),
					'alias' => $habtmAlias,
					'conditions' => [
						"$alias.$assocKey" => $db->identifier("$habtmAlias.$habtmKey")
					]
				];
			}

			$options = $this->attachAssociations($target, $aliasPath, $options);

			if (empty($options['limit'])) {
				$ids = Hash::extract($results, "{n}.$parentAlias.$parentKey");
				$ids = array_unique($ids);
				$options['conditions'] = array_merge($options['conditions'], ["$alias.$targetKey" => $ids]);
				$assocResults = $this->loadExternal($aliasPath, $db->read($target, $options));
			}

			foreach ($results as &$result) {
				$assoc = [];

				if (!empty($options['limit'])) {
					$eachOptions = $options;
					$eachOptions['conditions']["$alias.$targetKey"] = $result[$parentAlias][$parentKey];
					$assocResults = $this->loadExternal($aliasPath, $db->read($target, $eachOptions));
				}
				foreach ($assocResults as $assocResult) {
					if ($result[$parentAlias][$parentKey] == $assocResult[$alias][$targetKey]) {
						if ($has && $belong) {
							$assoc[] = $assocResult[$habtmAlias] + [$alias => $assocResult[$alias]];
						} else {
							$assoc[] = $assocResult[$alias];
						}
					}
				}
				if (!$many) {
					$assoc = $assoc ? current($assoc) : [];
				}

				$result = Hash::insert($result, $propertyPath, $assoc);
			}
			unset($result);
		}

		foreach ($map['joins'] as $meta) {
			extract($meta);
			foreach ($results as &$result) {
				if ($alias !== $propertyPath) {
					$result = Hash::insert($result, $propertyPath, $result[$alias] + (array)Hash::get($result, $propertyPath));
					unset($result[$alias]);
				}
			}
		}
		
		foreach ($results as &$result) {
			unset($result[$this->alias]);
		}

		return $results;
	}

/**
 * 
 *
 * @param $contain
 *
 * @return array
 */
	private function reformatContain($contain) {
		$result = [
			'contain' => [],
			'options' => [],
		];

		$contain = (array)$contain;
		foreach ($contain as $key => $val) {
			if (is_int($key)) {
				$key = $val;
				$val = [];
			}

			if (!isset($this->containOptions[$key])) {
				if (strpos($key, '.') !== false) {
					$expanded = Hash::expand([$key => $val]);
					list($key, $val) = each($expanded);
				}
				$ref =& $result['contain'][$key];
				$ref = Hash::merge((array)$ref, $this->reformatContain($val));
			} else {
				$result['options'][$key] = $val;
			}
		}

		return $result;
	}

/**
 * 
 *
 * @param $parent
 * @param $alias
 * @param $contain
 * @param $paths
 *
 * @return array
 */
	private function parseContain(Model $parent, $alias, $contain, array $paths) {
		$contain = (array)$contain;

		$map =& $this->settings[$this->id]['map'];

		$aliasPath = $paths['aliasPath'] . '.' . $alias;
		$propertyPath = implode('.', array_slice(explode('.', $aliasPath), 1));

		$map[$aliasPath] = [
			'joins' => [],
			'externals' => [],
		];

		$types = $parent->getAssociated();
		if (!isset($types[$alias])) {
			trigger_error(sprintf('Model "%s" is not associated with model "%s"', $parent->alias, $alias), E_USER_WARNING);
			return;
		}

		$parentAlias = $parent->alias;
		$target = $parent->$alias;
		$type = $types[$alias];
		$relation = $parent->{$type}[$alias];

		$has = (stripos($type, 'has') !== false);
		$many = (stripos($type, 'many') !== false);
		$belong = (stripos($type, 'belong') !== false);

		if ($has) {
			$parentKey = $parent->primaryKey;
			$targetKey = $relation['foreignKey'];
		} else {
			$parentKey = $relation['foreignKey'];
			$targetKey = $target->primaryKey;
		}

		if ($has && $belong) {
			$habtm = $target;
			$habtmAlias = $alias;
			$habtmKey = $habtm->primaryKey;
			$assocKey = $relation['associationForeignKey'];
			$alias = $relation['with'];
			$target = $parent->$alias;
		}

		$options = $contain['options'];

		$meta = compact(
			'parent', 'target',
			'parentAlias', 'parentKey',
			'alias', 'targetKey',
			'aliasPath', 'propertyPath',
			'type', 'options', 'has', 'many', 'belong',
			'habtm', 'habtmAlias', 'habtmKey', 'assocKey'
		);

		$tmp = explode($paths['aliasPath'], '.');
		$rootAlias = end($tmp);
		if ($many || $alias === $rootAlias || isset($map[$paths['root']]['joins'][$alias])) {
			$paths['root'] = $aliasPath;
			$map[$paths['aliasPath']]['externals'][$alias] = $meta;
		} else {
			$map[$paths['root']]['joins'][$alias] = $meta;
		}

		$paths['aliasPath'] = $aliasPath;
		foreach ($contain['contain'] as $key => $val) {
			$this->parseContain($target, $key, $val, $paths);
		}
	}
}
