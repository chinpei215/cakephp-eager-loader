<?php
App::build(array(
	'Model' => array(App::pluginPath('EagerLoader') . 'Test' . DS . 'test_app' . DS . 'Model' . DS),
	'Model/Behavior' => array(App::pluginPath('EagerLoader') . 'Test' . DS . 'test_app' . DS . 'Model' . DS . 'Behavior' . DS),
), true);

App::uses('ConnectionManager', 'Model');
ConnectionManager::create('test_external', array(
	'datasource' => 'Database/Sqlite',
	'database' => TMP . 'tests' . DS . 'test_external.db',
))->cacheSources = false;
