<?php
/**
 * Comment for testing
 */
class Comment extends AppModel {

	public $displayField = 'comment';

	public $belongsTo = array('Article', 'Comment');

	public $hasAndBelongsToMany = array('Tag');
}
