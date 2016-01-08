<?php
require_once App::pluginPath('EagerLoader') . 'Test' . DS . 'bootstrap.php';

App::uses('EagerLoader', 'EagerLoader.Model');

class EagerLoaderTest extends CakeTestCase {

/**
 * autoFixtures property
 *
 * @var bool
 */
	public $autoFixtures = false;

/**
 * Fixtures
 *
 * @var array
 */
	public $fixtures = array(
		'core.user',
		'core.articles_tag',
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
 * Tests reformatContain method
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
 * Data provider for testReformatContain
 *
 * @return array
 */
	public function dataProviderForTestReformatContain() {
		return array(
			array(
				// {{{ #0
				'User',
				array(
					'options' => array(),
					'contain' => array(
						'User' => array('options' => array(), 'contain' => array()),
					),
				),
				// }}}
			),
			array(
				// {{{ #1
				'User.Profile',
				array(
					'options' => array(),
					'contain' => array(
						'User' => array(
							'options' => array(),
							'contain' => array(
								'Profile' => array('options' => array(), 'contain' => array()),
							),
						),
					),
				),
				// }}}
			),
			array(
				// {{{ #2
				array(
					'Comment' => array(
						'User' => array(),
						'limit' => 3,
						'order' => array('id' => 'desc'),
						'conditions' => array('published' => 'Y'),
					),
				),
				array(
					'options' => array(),
					'contain' => array(
						'Comment' => array(
							'options' => array(
								'limit' => 3,
								'order' => array('id' => 'desc'),
								'conditions' => array('published' => 'Y'),
							),
							'contain' => array(
								'User' => array('options' => array(), 'contain' => array()),
							),
						),
					),
				),
				// }}}
			),
			array(
				// {{{ #3
				array(
					'User' => array('fields' => array('name')),
					'User.Profile' => array('fields' => array('address')),
					'Comment.User.Profile',
				),
				array(
					'options' => array(),
					'contain' => array(
						'User' => array(
							'contain' => array(
								'Profile' => array('options' => array('fields' => array('address')), 'contain' => array()),
							),
							'options' => array('fields' => array('name')),
						),
						'Comment' => array(
							'options' => array(),
							'contain' => array(
								'User' => array(
									'options' => array(),
									'contain' => array(
										'Profile' => array('options' => array(), 'contain' => array()),
									),
								),
							), 
						),
					),
				),
				// }}}
			),
		);
	}

/**
 * Tests buildJoinQuery method
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
			$User, 
			array('fields' => array()),
			'INNER',
			array('Article.user_id' => 'User.id'),
			array(
				'conditions' => array(
					'User.created >=' => '2015-01-01',
				)
			)
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
						'User.created >=' => '2015-01-01',
					),
				),
			)
		);

		$this->assertEquals($expected, $result);
	}

/**
 * Tests perseContain method 
 *
 * @return void
 *
 * @dataProvider dataProviderForTestParseContain
 */
	public function testParseContain($model, $alias, $contain, $expected) {
		$this->loadFixtures('ArticlesTag');

		$model = ClassRegistry::init($model);

		$method = new ReflectionMethod('EagerLoader', 'parseContain');
		$method->setAccessible(true);

		$result = $method->invokeArgs($this->EagerLoader, array(
			$model,
			$alias,
			$contain,
			array(
				'root' => $model->alias,
				'aliasPath' => $model->alias,
				'propertyPath' => '',
			)
		));

		// Remove instances
		$result = Hash::remove($result, '{s}.{s}.target');
		$result = Hash::remove($result, '{s}.{s}.parent');
		$result = Hash::remove($result, '{s}.{s}.habtm');

		$this->assertEquals($expected, $result);
	}

	public function dataProviderForTestParseContain() {
		return array( 
			array(
				// {{{ #0
				'Comment',
				'Article',
				array(
					'options' => array(),
					'contain' => array(
						'User' => array(
							'options' => array(),
							'contain' => array(),
						),
					),
				),
				array(
					'Comment' => array(
						'Article' => array(
							'parentAlias' => 'Comment',
							'parentKey' => 'article_id',
							'targetKey' => 'id',
							'aliasPath' => 'Comment.Article',
							'propertyPath' => 'Article',
							'options' => array(),
							'has' => false,
							'belong' => true,
							'many' => false,
							'external' => false,
						),
						'User' => array(
							'parentAlias' => 'Article',
							'parentKey' => 'user_id',
							'targetKey' => 'id',
							'aliasPath' => 'Comment.Article.User',
							'propertyPath' => 'Article.User',
							'options' => array(),
							'has' => false,
							'belong' => true,
							'many' => false,
							'external' => false,
						),
					),
				),
				// }}}
			),
			array(
				// {{{ #1
				'User',
				'Article',
				array(
					'options' => array(),
					'contain' => array(
						'Comment' => array(
							'options' => array('limit' => 3),
							'contain' => array(
								'User' => array('options' => array(), 'contain' => array()),
								'Attachment' => array('options' => array(), 'contain' => array()),
							),
						),
						'Tag' => array('options' => array(), 'contain' => array())
					),
				),
				array(
					'User' => array(
						'Article' => array(
							'parentAlias' => 'User',
							'parentKey' => 'id',
							'targetKey' => 'user_id',
							'aliasPath' => 'User.Article',
							'propertyPath' => 'Article',
							'options' => array(),
							'has' => true,
							'belong' => false,
							'many' => true,
							'external' => true,
						),
					),
					'User.Article' => array(
						'Comment' => array(
							'parentAlias' => 'Article',
							'parentKey' => 'id',
							'targetKey' => 'article_id',
							'aliasPath' => 'User.Article.Comment',
							'propertyPath' => 'Article.Comment',
							'options' => array('limit' => 3),
							'has' => true,
							'belong' => false,
							'many' => true,
							'external' => true,
						),
						'ArticlesTag' => array(
							'parentAlias' => 'Article',
							'parentKey' => 'id',
							'targetKey' => 'article_id',
							'aliasPath' => 'User.Article.Tag',
							'propertyPath' => 'Article.Tag',
							'options' => array(),
							'has' => true,
							'belong' => true,
							'many' => true,
							'habtmAlias' => 'Tag',
							'habtmKey' => 'id',
							'assocKey' => 'tag_id',
							'external' => true,
						),
					),
					'User.Article.Comment' => array(
						'User' => array(
							'parentAlias' => 'Comment',
							'parentKey' => 'user_id',
							'targetKey' => 'id',
							'aliasPath' => 'User.Article.Comment.User',
							'propertyPath' => 'Comment.User',
							'options' => array(),
							'has' => false,
							'belong' => true,
							'many' => false,
							'external' => false,
						),
						'Attachment' => array(
							'parentAlias' => 'Comment',
							'parentKey' => 'id',
							'targetKey' => 'comment_id',
							'aliasPath' => 'User.Article.Comment.Attachment',
							'propertyPath' => 'Comment.Attachment',
							'options' => array(),
							'has' => true,
							'belong' => false,
							'many' => false,
							'external' => false,
						),
					),
				),
				// }}}
			),
		);
	}
}
