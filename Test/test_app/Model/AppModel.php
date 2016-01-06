<?php
/**
 * AppModel for testing
 */
class AppModel extends Model {

	public $actsAs = array(
		'EagerLoadable.EagerLoadable',
	);
}
