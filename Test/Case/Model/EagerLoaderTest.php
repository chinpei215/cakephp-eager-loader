<?php
require_once App::pluginPath('EagerLoadable') . 'Test' . DS . 'bootstrap.php';

App::uses('EagerLoader', 'EagerLoadable.Model');

class EagerLoaderTest extends CakeTestCase {

/**
 * Fixtures
 *
 * @var array
 */
	public $fixtures = array(
		'core.user',
	);

/**
 * setUp method
 *
 * @return void
 */
	public function setUp() {
		parent::setUp();
		$this->EagerLoader = new EagerLoader();
	}

/**
 * tearDown
 *
 * @return void
 */
	public function tearDown() {
		parent::tearDown();
	}

/**
 * 
 *
 * @return void
 *
 * @dataProvider dataProviderForTestReformatContain
 */
	public function testReformatContain($contain, $expected) {
		$method = new ReflectionMethod('EagerLoader', 'reformatContain');
		$method->setAccessible(true);
		$result = $method->invoke($this->EagerLoader, $contain);
		$this->assertEquals($expected, $result);
	}

/**
 * 
 *
 * @return array
 */
	public function dataProviderForTestReformatContain() {
		return array(
			array(
				'User',
				array(
					'contain' => array(
						'User' => array('contain' => array(), 'options' => array()),
					),
					'options' => array(),
				),
			),
			array(
				'User.Profile',
				array(
					'contain' => array(
						'User' => array(
							'contain' => array(
								'Profile' => array('contain' => array(), 'options' => array()),
							),
							'options' => array(),
						),
					),
					'options' => array(),
				),
			),
			array(
				array(
					'Comment' => array(
						'User' => array(),
						'limit' => 3,
						'order' => array('id' => 'desc'),
						'conditions' => array('published' => 'Y'),
					),
				),
				array(
					'contain' => array(
						'Comment' => array(
							'contain' => array(
								'User' => array('contain' => array(), 'options' => array()),
							),
							'options' => array(
								'limit' => 3,
								'order' => array('id' => 'desc'),
								'conditions' => array('published' => 'Y'),
							),
						),
					),
					'options' => array(),
				),
			),

			array(
				array(
					'User' => array('fields' => array('name')),
					'User.Profile' => array('fields' => array('address')),
					'Comment.User.Profile',
				),
				array(
					'contain' => array(
						'User' => array(
							'contain' => array(
								'Profile' => array('contain' => array(), 'options' => array('fields' => array('address'))),
							),
							'options' => array('fields' => array('name')),
						),
						'Comment' => array(
							'contain' => array(
								'User' => array(
									'contain' => array(
										'Profile' => array('contain' => array(), 'options' => array()),
									),
									'options' => array(),
								),
							), 
							'options' => array(),
						),
					),
					'options' => array(),
				),
			),
		);
	}

/**
 * 
 *
 * @return void
 */
	public function testBuildJoinQuery() {
		$this->loadFixtures('User');
		$User = ClassRegistry::init('User');

		$db = $User->getDataSource();

		$method = new ReflectionMethod('EagerLoader', 'buildJoinQuery');
		$method->setAccessible(true);
		$result = $method->invokeArgs($this->EagerLoader, array(
			array('fields' => array()),
			$User, 
			'INNER',
			array('Article.user_id' => 'User.id')
		));

		$expected = array(
			'fields' => array_map(array($db, 'name'), array(
				'User.id', 
				'User.user', 
				'User.password', 
				'User.created', 
				'User.updated', 
				'Article.user_id', 
			)),
			'joins' => array(
				array(
					'type' => 'INNER',
					'table' => $db->fullTableName($User),
					'alias' => 'User',
					'conditions' => array(
						'Article.user_id' => (object)array('type' => 'identifier', 'value' => 'User.id'),
					),
				),
			)
		);

		$this->assertEquals($expected, $result);
	}

}
