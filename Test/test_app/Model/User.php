<?php
class User extends AppModel {

	public $hasMany = array('Article', 'Comment');

	public $hasOne = array('Profile');
}
