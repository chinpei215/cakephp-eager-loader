<?php

class ExternalCommentFixture extends CakeTestFixture {

	public $useDbConfig = 'test_external';

/**
 * fields property
 *
 * @var array
 */
	public $fields = array(
		'id' => array('type' => 'integer', 'key' => 'primary'),
		'article_id' => array('type' => 'integer', 'null' => true),
		'comment' => 'text',
	);

/**
 * records property
 *
 * @var array
 */
	public $records = array(
		array('article_id' => '3', 'comment' => 'External Comment'),
	);
}
