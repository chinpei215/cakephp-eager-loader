<?php
/**
 * EagerLoaderModel class
 *
 * @internal
 */
class EagerLoaderModel extends Model {

	public $useTable = false;

/**
 * Returns true
 *
 * @param string $field Name of field to look for
 * @return bool
 */
	public function isVirtualField($field) {
		return true;
	}
}
