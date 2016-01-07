<?php
/**
 * Comment for testing
 */
class Comment extends AppModel {

	public $displayField = 'comment';

	public $belongsTo = array('Article', 'User');

	public $hasOne = array('Attachment');

	public $hasAndBelongsToMany = array('Tag');
}
