<?php
/**
 * Article for testing
 */
class Article extends AppModel {

	public $hasMany = array('Comment');

	public $belongsTo = array('User');

	public $hasAndBelongsToMany = array('Tag');
}
