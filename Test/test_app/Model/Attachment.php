<?php
/**
 * Attachment for testing
 */
class Attachment extends AppModel {

	public $displayField = 'attachment';

	public $belongsTo = array('Comment');
}

