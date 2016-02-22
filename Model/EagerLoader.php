<?php
/**
 * EagerLoader class
 *
 * @internal
 */
class EagerLoader {

	private static $handlers = array(); // @codingStandardsIgnoreLine

	private $id; // @codingStandardsIgnoreLine

	private $metas = array(); // @codingStandardsIgnoreLine

	private $containOptions = array(  // @codingStandardsIgnoreLine
		'conditions' => 1,
		'fields' => 1,
		'order' => 1,
		'limit' => 1,
		'offset' => 1,
	);

/**
 * Constructor
 */
	public function __construct() {
		ClassRegistry::init('EagerLoader.EagerLoaderModel');
		$this->id = max(self::ids()) + 1;
	}

/**
 * Handles beforeFind event
 *
 * @param Model $model Model
 * @param array $query Query
 * @return array Modified query
 */
	public static function handleBeforeFind(Model $model, $query) {
		if (is_array($query)) {
			if (isset($query['contain'])) {
				if ($query['contain'] === false) {
					$query['recursive'] = -1;
				} else {
					$EagerLoader = new EagerLoader();
					$query = $EagerLoader->transformQuery($model, $query);

					self::$handlers[$EagerLoader->id] = $EagerLoader;
					if (count(self::$handlers) > 1000) {
						$id = min(self::ids());
						unset(self::$handlers[$id]);
					}
				}
			}
		}
		return $query;
	}

/**
 * Handles afterFind event
 *
 * @param Model $model Model
 * @param array $results Results
 * @return array Modified results
 * @throws UnexpectedValueException
 */
	public static function handleAfterFind(Model $model, $results) {
		if (is_array($results)) {
			$id = Hash::get($results, '0.EagerLoaderModel.id');
			if ($id) {
				if (empty(self::$handlers[$id])) {
					throw new UnexpectedValueException(sprintf('EagerLoader "%s" is not found', $id));
				}

				$EagerLoader = self::$handlers[$id];
				unset(self::$handlers[$id]);

				$results = $EagerLoader->transformResults($model, $results);
			}
		}
		return $results;
	}

/**
 * Returns object ids
 *
 * @return array
 */
	private static function ids() { // @codingStandardsIgnoreLine
		$ids = array_keys(self::$handlers);
		if (!$ids) {
			return array(0);
		}
		return $ids;
	}

/**
 * Modifies the passed query to fetch the top level attachable associations.
 *
 * @param Model $model Model
 * @param array $query Query
 * @return array Modified query
 */
	private function transformQuery(Model $model, array $query) { // @codingStandardsIgnoreLine
		ClassRegistry::init('EagerLoader.EagerLoaderModel');

		$contain = $this->reformatContain($query['contain']);
		foreach ($contain['contain'] as $key => $val) {
			$this->parseContain($model, $key, $val);
		}

		$query = $this->attachAssociations($model, $model->alias, $query);

		$db = $model->getDataSource();
		$value = $db->value($this->id);
		$name = $db->name('EagerLoaderModel' . '__' . 'id');
		$query['fields'][] = "($value) AS $name";
		$query['callbacks'] = true;

		return $query;
	}

/**
 * Modifies the results
 *
 * @param Model $model Model
 * @param array $results Results
 * @return array Modified results
 */
	private  function transformResults(Model $model, array $results) { // @codingStandardsIgnoreLine
		foreach ($results as &$result) {
			unset($result['EagerLoaderModel']);
		}
		return $this->loadExternal($model, $model->alias, $results);
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
		$query = $this->normalizeQuery($model, $query);

		foreach ($this->metas($path) as $meta) {
			extract($meta);
			if ($external) {
				$query = $this->addField($query, "$parentAlias.$parentKey");
			} else {
				$query = $this->buildJoinQuery($target, $query, 'LEFT', array("$parentAlias.$parentKey" => "$alias.$targetKey"), $options);
			}
		}

		$query['recursive'] = -1;
		$query['contain'] = false;

		return $query;
	}

/**
 * Fetches meta data
 *
 * @param string $path Path of the association
 * @return array
 */
	private function metas($path) { // @codingStandardsIgnoreLine
		if (isset($this->metas[$path])) {
			return $this->metas[$path];
		}
		return array();
	}

/**
 * Fetches external associations
 *
 * @param Model $model Model
 * @param string $path The target path of the external primary model, such as 'User.Article'
 * @param array $results The results of the parent model
 * @return array
 */
	protected function loadExternal(Model $model, $path, array $results) { // @codingStandardsIgnoreLine
		if ($results) {
			foreach ($this->metas($path) as $meta) {
				extract($meta);
				if ($external) {
					$results = $this->mergeExternalExternal($model, $results, $meta);
				} else {
					$results = $this->mergeInternalExternal($model, $results, $meta);
				}
			}
		}
		return $results;
	}

/**
 * Merges results of external associations of an external association
 *
 * @param Model $model Model
 * @param array $results Results
 * @param array $meta Meta data to be used for eager loading
 * @return array
 */
	private function mergeExternalExternal(Model $model, array $results, array $meta) { // @codingStandardsIgnoreLine
		extract($meta);

		$db = $target->getDataSource();

		$assocAlias = $alias;
		$assocKey = $targetKey;

		$options = $this->attachAssociations($target, $aliasPath, $options);
		if ($has && $belong) {
			$assocAlias = $habtmAlias;
			$assocKey = $habtmParentKey;

			$options = $this->buildJoinQuery($habtm, $options, 'INNER', array(
				"$alias.$targetKey" => "$habtmAlias.$habtmTargetKey",
			), $options);
		}

		$options = $this->addField($options, "$assocAlias.$assocKey");

		$ids = Hash::extract($results, "{n}.$parentAlias.$parentKey");
		$ids = array_unique($ids);

		if (!empty($finderQuery)) {
			$assocResults = array();
			foreach ($ids as $id) {
				$eachQuery = str_replace('{$__cakeID__$}', $db->value($id), $finderQuery);
				$eachAssocResults = $db->fetchAll($eachQuery, $target->cacheQueries);
				$eachAssocResults = Hash::insert($eachAssocResults, "{n}.EagerLoaderModel.assoc_id", $id);
				$assocResults = array_merge($assocResults, $eachAssocResults);
			}
		} elseif ($this->hasLimitOffset($options)) {
			$assocResults = array();
			foreach ($ids as $id) {
				$eachOptions = $options;
				$eachOptions['conditions'][] = array("$assocAlias.$assocKey" => $id);
				$eachAssocResults = $db->read($target, $eachOptions);
				$eachAssocResults = Hash::insert($eachAssocResults, "{n}.EagerLoaderModel.assoc_id", $id);
				$assocResults = array_merge($assocResults, $eachAssocResults);
			}
		} else {
			$options['fields'][] = '(' . $db->name($assocAlias . '.' . $assocKey) . ') AS ' . $db->name('EagerLoaderModel' . '__' . 'assoc_id');
			$options['conditions'][] = array("$assocAlias.$assocKey" => $ids);
			$assocResults = $db->read($target, $options);
		}

		$assocResults = $this->filterResults($parent, $alias, $assocResults);
		$assocResults = $this->loadExternal($target, $aliasPath, $assocResults);

		if ($has && $belong) {
			foreach ($assocResults as &$assocResult) {
				$assocResult[$alias][$habtmAlias] = $assocResult[$habtmAlias];
				unset($assocResult[$habtmAlias]);
			}
			unset($assocResult);
		}

		foreach ($results as &$result) {
			$assoc = array();
			foreach ($assocResults as $assocResult) {
				if ((string)$result[$parentAlias][$parentKey] === (string)$assocResult['EagerLoaderModel']['assoc_id']) {
					$assoc[] = $assocResult[$alias];
				}
			}
			if (!$many) {
				$assoc = $assoc ? current($assoc) : array();
			}
			$result = $this->mergeAssocResult($result, $assoc, $propertyPath);
		}

		return $results;
	}

/**
 * Merges results of external associations of an internal association
 *
 * @param Model $model Model
 * @param array $results Results
 * @param array $meta Meta data to be used for eager loading
 * @return array
 */
	private function mergeInternalExternal(Model $model, array $results, array $meta) { // @codingStandardsIgnoreLine
		extract($meta);

		$assocResults = array();
		foreach ($results as $n => &$result) {
			if ($result[$alias][$targetKey] === null) {
				// Remove NULL association created by LEFT JOIN
				if (empty($eager)) {
					$assocResults[$n] = array( $alias => array() );
				}
			} else {
				$assocResults[$n] = array( $alias => $result[$alias] );
			}
			unset($result[$alias]);
		}
		unset($result);

		if (!empty($eager) && !isset($model->$alias)) {
			$assocResults = $this->filterResults($parent, $alias, $assocResults);
		}
		$assocResults = $this->loadExternal($target, $aliasPath, $assocResults);

		foreach ($results as $n => &$result) {
			if (isset($assocResults[$n][$alias])) {
				$assoc = $assocResults[$n][$alias];
				$result = $this->mergeAssocResult($result, $assoc, $propertyPath);
			}
		}
		unset($result);

		return $results;
	}

/**
 * Merges associated result
 *
 * @param array $result Results
 * @param array $assoc Associated results
 * @param string $propertyPath Path of the results
 * @return array
 */
	private function mergeAssocResult(array $result, array $assoc, $propertyPath) { // @codingStandardsIgnoreLine
		return Hash::insert($result, $propertyPath, $assoc + (array)Hash::get($result, $propertyPath));
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
			'conditions' => array(),
			'order' => array()
		);

		if (!$query['fields']) {
			$query['fields'] = $db->fields($model, null, array(), false);
		}

		$query['fields'] = (array)$query['fields'];
		foreach ($query['fields'] as &$field) {
			if ($model->isVirtualField($field)) {
				$fields = $db->fields($model, null, array($field), false);
				$field = $fields[0];
			} else {
				$field = $this->normalizeField($model, $field);
			}
		}
		unset($field);

		$query['conditions'] = (array)$query['conditions'];
		foreach ($query['conditions'] as $key => $val) {
			if ($model->hasField($key)) {
				unset($query['conditions'][$key]);
				$key = $this->normalizeField($model, $key);
				$query['conditions'][] = array($key => $val);
			} elseif ($model->isVirtualField($key)) {
				unset($query['conditions'][$key]);
				$expression = $db->dispatchMethod('_parseKey', array($key, $val, $model));
				$query['conditions'][] = $db->expression($expression);
			}
		}

		$order = array();
		foreach ((array)$query['order'] as $key => $val) {
			if (is_int($key)) {
				$val = $this->normalizeField($model, $val);
			} else {
				$key = $this->normalizeField($model, $key);
			}
			$order += array($key => $val);
		}
		$query['order'] = $order;

		return $query;
	}

/**
 * Normalize field
 *
 * @param Model $model Model
 * @param string $field Name of the field
 * @return string
 */
	private function normalizeField(Model $model, $field) { // @codingStandardsIgnoreLine
		if ($model->hasField($field)) {
			$field = $model->alias . '.' . $field;
		} elseif ($model->isVirtualField($field)) {
			$db = $model->getDataSource();
			$field = $model->getVirtualField($field);
			$field = $db->dispatchMethod('_quoteFields', array($field));
			$field = '(' . $field . ')';
		}
		return $field;
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
		$query = $this->normalizeQuery($target, $query);

		foreach ($keys as $lhs => $rhs) {
			$query = $this->addField($query, $lhs);
			$query = $this->addField($query, $rhs);
			$options['conditions'][] = array($lhs => $db->identifier($rhs));
		}

		$query['joins'][] = array(
			'type' => $joinType,
			'table' => $target,
			'alias' => $target->alias,
			'conditions' => $options['conditions'],
		);
		return $query;
	}

/**
 * Adds a field into the `fields` option of the query
 *
 * @param array $query Query
 * @param string $field Name of the field
 * @return Modified query
 */
	private function addField(array $query, $field) { // @codingStandardsIgnoreLine
		if (!in_array($field, $query['fields'], true)) {
			$query['fields'][] = $field;
		}
		return $query;
	}

/**
 * Parse the `contain` option of the query recursively
 *
 * @param Model $parent Parent model of the contained model
 * @param string $alias Alias of the contained model
 * @param array $contain Reformatted `contain` option for the deep associations
 * @param array|null $context Context
 * @return array
 * @throws InvalidArgumentException
 */
	private function parseContain(Model $parent, $alias, array $contain, $context = null) { // @codingStandardsIgnoreLine
		if ($context === null) {
			$context = array(
				'root' => $parent->alias,
				'aliasPath' => $parent->alias,
				'propertyPath' => '',
				'forceExternal' => false,
			);
		}

		$aliasPath = $context['aliasPath'] . '.' . $alias;
		$propertyPath = ($context['propertyPath'] ? $context['propertyPath'] . '.' : '') . $alias;

		$types = $parent->getAssociated();
		if (!isset($types[$alias])) {
			throw new InvalidArgumentException(sprintf('Model "%s" is not associated with model "%s"', $parent->alias, $alias), E_USER_WARNING);
		}

		$parentAlias = $parent->alias;
		$target = $parent->$alias;
		$type = $types[$alias];
		$relation = $parent->{$type}[$alias];

		$options = $contain['options'] + array_intersect_key(Hash::filter($relation), $this->containOptions);

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

		if (!empty($relation['external'])) {
			$external = true;
		}

		if (!empty($relation['finderQuery'])) {
			$finderQuery = $relation['finderQuery'];
		}

		$meta = compact(
			'alias', 'parent', 'target',
			'parentAlias', 'parentKey',
			'targetKey', 'aliasPath', 'propertyPath',
			'options', 'has', 'many', 'belong', 'external', 'finderQuery',
			'habtm', 'habtmAlias', 'habtmParentKey', 'habtmTargetKey'
		);

		if ($this->isExternal($context, $meta)) {
			$meta['external'] = true;

			$context['root'] = $aliasPath;
			$context['propertyPath'] = $alias;

			$path = $context['aliasPath'];
		} else {
			$meta['external'] = false;
			if ($context['root'] !== $context['aliasPath']) {
				$meta['eager'] = true;
			}

			$context['propertyPath'] = $propertyPath;

			$path = $context['root'];
		}

		$this->metas[$path][] = $meta;

		$context['aliasPath'] = $aliasPath;
		$context['forceExternal'] = !empty($finderQuery);

		foreach ($contain['contain'] as $key => $val) {
			$this->parseContain($target, $key, $val, $context);
		}

		return $this->metas;
	}

/**
 * Returns whether the target is external or not
 *
 * @param array $context Context
 * @param array $meta Meta data to be used for eager loading
 * @return bool
 */
	private function isExternal(array $context, array $meta) { // @codingStandardsIgnoreLine
		extract($meta);

		if ($parent->useDbConfig !== $target->useDbConfig) {
			return true;
		}
		if (!empty($external)) {
			return true;
		}
		if (!empty($many)) {
			return true;
		}
		if (!empty($finderQuery)) {
			return true;
		}
		if ($this->hasLimitOffset($options)) {
			return true;
		}
		if ($context['forceExternal']) {
			return true;
		}

		$metas = $this->metas($context['root']);
		$aliases = Hash::extract($metas, '{n}.alias');
		if (in_array($alias, $aliases, true)) {
			return true;
		}

		return false;
	}

/**
 * Returns where `limit` or `offset` option exists
 *
 * @param array $options Options
 * @return bool
 */
	private function hasLimitOffset($options) { // @codingStandardsIgnoreLine
		return !empty($options['limit']) || !empty($options['offset']);
	}

/**
 * Triggers afterFind() method
 *
 * @param Model $parent Model
 * @param string $alias Alias
 * @param array $results Results
 * @return array
 */
	private function filterResults(Model $parent, $alias, array $results) { // @codingStandardsIgnoreLine
		$db = $parent->getDataSource();
		$db->dispatchMethod('_filterResultsInclusive', array(&$results, $parent, array($alias)));
		return $results;
	}
}
