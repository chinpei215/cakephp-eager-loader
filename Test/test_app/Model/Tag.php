<?php
class Tag extends AppModel {

	public $hasAndBelongsToMany = array('Article', 'Comment');
}
