<?php

class ProfileFixture extends CakeTestFixture {

/**
 * fields property
 *
 * @var array
 */
	public $fields = array(
		'id' => array('type' => 'integer', 'key' => 'primary'),
		'user_id' => array('type' => 'integer', 'null' => true),
		'nickname' => array('type' => 'string', 'null' => true),
		'company' => array('type' => 'string', 'null' => true),
	);

/**
 * records property
 *
 * @var array
 */
	public $records = array(
		array('user_id' => '3', 'nickname' => 'phpnut', 'company' => 'Cake Software Foundation, Inc.'),
	);
}

