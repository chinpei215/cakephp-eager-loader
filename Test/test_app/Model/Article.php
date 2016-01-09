<?php
/**
 * Article for testing
 */
class Article extends AppModel {

	public $hasMany = array('Comment');

	public $hasOne = array(
		'FirstComment' => array(
			'className' => 'Comment',
			'limit' => 1,
			'order' => 'FirstComment.id',
		),
		'SecondComment' => array(
			'className' => 'Comment',
			'limit' => 1,
			'offset' => 1,
			'order' => 'SecondComment.id',
		),
	);

	public $belongsTo = array('User');

	public $hasAndBelongsToMany = array('Tag');
}
