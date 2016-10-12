<?php

class ArticlesCategoryFixture extends CakeTestFixture {

/**
 * fields property
 *
 * @var array
 */
	public $fields = array(
		'article_id' => array('type' => 'integer'),
		'category_id' => array('type' => 'integer'),
	);

/**
 * records property
 *
 * @var array
 */
	public $records = array(
		array('article_id' => 1, 'category_id' => 1),
		array('article_id' => 2, 'category_id' => 2),
	);
}
