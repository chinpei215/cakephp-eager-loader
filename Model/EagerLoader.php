<?php

/**
 * EagerLoader class
 */
class EagerLoader extends Model {

	public $useTable = false;

	private $settings = array(); // @codingStandardsIgnoreLine

	private $containOptions = array(  // @codingStandardsIgnoreLine
		'conditions' => 1,
		'fields' => 1,
		'order' => 1,
		'limit' => 1,
	);

/**
 * Returns true
 *
 * @param string $field Name of field to look for
 * @return bool
 */
	public function isVirtualField($field) {
		return true;
	}

/**
 * Modifies the passed query to fetch the top level attachable associations.
 *
 * @param Model $model Model
 * @param array $query Query
 * @return array Modified query
 */
	public function transformQuery(Model $model, $query) {
		static $id = 0;
		$this->id = (++$id);

		$contain = $this->reformatContain($query['contain']);
		foreach ($contain['contain'] as $key => $val) {
			$this->parseContain($model, $key, $val, array(
				'root' => $model->alias,
				'aliasPath' => $model->alias,
				'propertyPath' => '',
			));
		}

		$query = $this->attachAssociations($model, $model->alias, $query);
		$query['fields'] = array_merge($query['fields'], array('(' . $this->id . ') AS EagerLoader__id'));

		return $query;
	}

/**
 * Modifies the query to fetch attachable associations.
 *
 * @param Model $model Model
 * @param string $path The target path of the model, such as 'User.Article'
 * @param array $query Query
 * @return array Modified query
 */
	private function attachAssociations(Model $model, $path, array $query) { // @codingStandardsIgnoreLine
		$db = $model->getDataSource();

		$query = $this->normalizeQuery($model, $query);
		$metas =& $this->settings[$this->id][$path];

		if ($metas) {
			foreach ($metas as $alias => $meta) {
				extract($meta);
				if ($external) {
					$query = $this->addKeyField($parent, $query, "$parentAlias.$parentKey");
				} else {
					$joinType = 'LEFT';
					if ($belong) {
						$field = $parent->schema($parentKey);
						$joinType = ($field['null'] ? 'LEFT' : 'INNER');
					}

					$query = $this->buildJoinQuery($target, $query, $joinType, array("$parentAlias.$parentKey" => "$alias.$targetKey"), $options);
				}
			}
		}

		$query['recursive'] = -1;
		$query['contain'] = false;

		return $query;
	}

/**
 * Fetches external associations
 * 
 * @param string $path The target path of the external primary model, such as 'User.Article'
 * @param array $results The results of the parent model
 * @param bool $clear If true, the settings for eager loading will be removed
 * @return array
 */
	public function loadExternal($path, array $results, $clear = true) {
		$metas =& $this->settings[$this->id][$path];

		if ($metas) {
			$metas = Hash::sort($metas, '{s}.propertyPath', 'desc');

			foreach ($metas as $alias => $meta) {
				extract($meta);

				$assocResults = array();

				$assocAlias = $alias;
				$assocKey = $targetKey;

				if ($external) {
					$db = $target->getDataSource();

					$options = $this->attachAssociations($target, $aliasPath, $options);
					if ($has && $belong) {
						$assocAlias = $habtmAlias;
						$assocKey = $habtmParentKey;

						$options = $this->buildJoinQuery($habtm, $options, 'INNER', array(
							"$alias.$targetKey" => "$habtmAlias.$habtmTargetKey",
						), $options);
					}

					$options = $this->addKeyField($target, $options, "$assocAlias.$assocKey");

					$ids = Hash::extract($results, "{n}.$parentAlias.$parentKey");
					$ids = array_unique($ids);

					if (empty($options['limit'])) {
						$options['conditions'] = array_merge($options['conditions'], array("$assocAlias.$assocKey" => $ids));
						$assocResults = $db->read($target, $options);
					} else {
						foreach ($ids as $id) {
							$options['conditions']["$assocAlias.$assocKey"] = $id;
							$assocResults = array_merge($db->read($target, $options), $assocResults);
						}
					}

					// Triggers afterFind for the external primary model.
					$this->$alias = $target; // Hack for DboSource::_filterResults()
					$db->dispatchMethod('_filterResultsInclusive', array(&$assocResults, $this, array($alias)));
				} else {
					foreach ($results as &$result) {
						$assocResults[] = array( $alias => $result[$alias] );
						unset($result[$alias]);
					}
				}

				$assocResults = $this->loadExternal($aliasPath, $assocResults, false);

				foreach ($results as &$result) {
					$assoc = array();

					foreach ($assocResults as $assocResult) {
						if ($result[$parentAlias][$parentKey] == $assocResult[$assocAlias][$assocKey]) {
							if ($has && $belong) {
								$assoc[] = $assocResult[$alias] + array($assocAlias => $assocResult[$assocAlias]);
							} else {
								$assoc[] = $assocResult[$alias];
							}
						}
					}

					if (!$many) {
						$assoc = $assoc ? current($assoc) : array();
					}

					$result = Hash::insert($result, $propertyPath, $assoc + (array)Hash::get($result, $propertyPath));
				}
				unset($result);
			}
		}

		if ($clear) {
			unset($this->settings[$this->id]);
		}

		return $results;
	}

/**
 * Reformat `contain` array  
 *
 * @param array|string $contain The value of `contain` option of the query
 * @return array
 */
	private function reformatContain($contain) { // @codingStandardsIgnoreLine
		$result = array(
			'options' => array(),
			'contain' => array(),
		);

		$contain = (array)$contain;
		foreach ($contain as $key => $val) {
			if (is_int($key)) {
				$key = $val;
				$val = array();
			}

			if (!isset($this->containOptions[$key])) {
				if (strpos($key, '.') !== false) {
					$expanded = Hash::expand(array($key => $val));
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
 * Normalizes the query
 *
 * @param Model $model Model
 * @param array $query Query
 * @return array Normalized query
 */
	private function normalizeQuery(Model $model, array $query) { // @codingStandardsIgnoreLine
		$db = $model->getDataSource();

		$query += array(
			'fields' => array(),
			'conditions' => array()
		);

		if (!$query['fields']) {
			$query['fields'] = $db->fields($model, null, array(), false);
		}

		$query['fields'] = (array)$query['fields'];
		foreach ($query['fields'] as &$field) {
			if ($model->hasField($field)) {
				$field = $model->alias . '.' . $field;
			}
		}
		unset($field);

		$query['conditions'] = (array)$query['conditions'];
		foreach ($query['conditions'] as $key => $val) {
			if ($model->hasField($key)) {
				$query['conditions'][$model->alias . '.' . $key] = $val;
				unset($query['conditions'][$key]);
			}
		}

		return $query;
	}

/**
 * Modifies the query to apply joins.
 *
 * @param Model $target Model to be joined
 * @param array $query Query
 * @param string $joinType The type for join
 * @param array $keys Key fields being used for join
 * @param array $options Extra options for join
 * @return array Modified query
 */
	private function buildJoinQuery(Model $target, array $query, $joinType, array $keys, array $options) { // @codingStandardsIgnoreLine
		$db = $target->getDataSource();

		$options = $this->normalizeQuery($target, $options);
		$query['fields'] = array_merge($query['fields'], $options['fields']);

		foreach ($keys as $lhs => $rhs) {
			$query = $this->addKeyField($target, $query, $lhs);
			$query = $this->addKeyField($target, $query, $rhs);
			$options['conditions'][$lhs] = $db->identifier($rhs);
		}

		$query['joins'][] = array(
			'type' => $joinType,
			'table' => $db->fullTableName($target),
			'alias' => $target->alias,
			'conditions' => $options['conditions'],
		);
		return $query;
	}

/**
 * Adds a key field into the `fields` option of the query
 *
 * @param Model $model Model
 * @param array $query Query
 * @param string $key Name of the key field
 * @return Modified query
 */
	private function addKeyField(Model $model, array $query, $key) { // @codingStandardsIgnoreLine
		if (!in_array($key, $query['fields'], true)) {
			$query['fields'][] = $key;
		}
		return $query;
	}

/**
 * Parse the `contain` option of the query recursively
 *
 * @param Model $parent Parent model of the contained model
 * @param string $alias Alias of the contained model
 * @param array $contain Reformatted `contain` option for the deep associations
 * @param array $paths Path information of the root model, etc.
 * @return array
 * @throws InvalidArgumentException
 */
	private function parseContain(Model $parent, $alias, array $contain, array $paths) { // @codingStandardsIgnoreLine
		$map =& $this->settings[$this->id];

		$aliasPath = $paths['aliasPath'] . '.' . $alias;
		$propertyPath = ($paths['propertyPath'] ? $paths['propertyPath'] . '.' : '') . $alias;

		$types = $parent->getAssociated();
		if (!isset($types[$alias])) {
			throw new InvalidArgumentException(sprintf('Model "%s" is not associated with model "%s"', $parent->alias, $alias), E_USER_WARNING);
		}

		$parentAlias = $parent->alias;
		$target = $parent->$alias;
		$type = $types[$alias];
		$relation = $parent->{$type}[$alias];
		$options = $contain['options'];

		$has = (stripos($type, 'has') !== false);
		$many = (stripos($type, 'many') !== false);
		$belong = (stripos($type, 'belong') !== false);

		if ($has && $belong) {
			$parentKey = $parent->primaryKey;
			$targetKey = $target->primaryKey;
			$habtmAlias = $relation['with'];
			$habtm = $parent->$habtmAlias;
			$habtmParentKey = $relation['foreignKey'];
			$habtmTargetKey = $relation['associationForeignKey'];
		} elseif ($has) {
			$parentKey = $parent->primaryKey;
			$targetKey = $relation['foreignKey'];
		} else {
			$parentKey = $relation['foreignKey'];
			$targetKey = $target->primaryKey;
		}

		$tmp = explode($paths['root'], '.');
		$rootAlias = end($tmp);
		if ($many || $alias === $rootAlias || isset($map[$paths['root']][$alias])) {
			$external = true;
			$paths['root'] = $aliasPath;
			$paths['propertyPath'] = $alias;
			$path = $paths['aliasPath'];
		} else {
			$external = false;
			$paths['propertyPath'] = $propertyPath;
			$path = $paths['root'];
		}

		$map[$path][$alias] = compact(
			'parent', 'target',
			'parentAlias', 'parentKey',
			'targetKey', 'aliasPath', 'propertyPath',
			'options', 'has', 'many', 'belong', 'external',
			'habtm', 'habtmAlias', 'habtmParentKey', 'habtmTargetKey'
		);

		$paths['aliasPath'] = $aliasPath;
		foreach ($contain['contain'] as $key => $val) {
			$this->parseContain($target, $key, $val, $paths);
		}

		return $map;
	}
}
