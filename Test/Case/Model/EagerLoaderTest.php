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
 * @param array|string $contain Value of `contain` option
 * @param array $expected Expected
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
					'Article.user_id' => array(1, 2, 3),
				)
			)
		));

		$expected = array(
			'fields' => array(
				'User.id',
				'User.user',
				'User.password',
				'User.created',
				'User.updated',
				'Article.user_id',
			),
			'joins' => array(
				array(
					'type' => 'INNER',
					'table' => $db->fullTableName($User),
					'alias' => 'User',
					'conditions' => array(
						'AND' => array(
							array('Article.user_id' => array(1, 2, 3)),
							array('Article.user_id' => (object)array('type' => 'identifier', 'value' => 'User.id')),
						),
					),
				),
			)
		);

		$this->assertEquals($expected, $result);
	}

/**
 * Tests perseContain method
 *
 * @param string $model Parent model of the contained model
 * @param string $alias Alias of the contained model
 * @param array $contain Reformatted `contain` option for the deep associations
 * @param array $expected Expected
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

/**
 * Data provider for testParseContain
 *
 * @return array
 */
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
						'Tag' => array(
							'parentAlias' => 'Article',
							'parentKey' => 'id',
							'targetKey' => 'id',
							'aliasPath' => 'User.Article.Tag',
							'propertyPath' => 'Article.Tag',
							'options' => array(),
							'has' => true,
							'belong' => true,
							'many' => true,
							'habtmAlias' => 'ArticlesTag',
							'habtmParentKey' => 'article_id',
							'habtmTargetKey' => 'tag_id',
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
			array(
				// {{{ #2
				'Article',
				'Tag',
				array(
					'options' => array(),
					'contain' => array(
						'Article' => array('options' => array(), 'contain' => array()),
					),
				),
				array(
					'Article' => array(
						'Tag' => array(
							'parentAlias' => 'Article',
							'parentKey' => 'id',
							'targetKey' => 'id',
							'aliasPath' => 'Article.Tag',
							'propertyPath' => 'Tag',
							'options' => array(),
							'has' => true,
							'belong' => true,
							'many' => true,
							'habtmAlias' => 'ArticlesTag',
							'habtmParentKey' => 'article_id',
							'habtmTargetKey' => 'tag_id',
							'external' => true,
						),
					),
					'Article.Tag' => array(
						'Article' => array(
							'parentAlias' => 'Tag',
							'parentKey' => 'id',
							'targetKey' => 'id',
							'aliasPath' => 'Article.Tag.Article',
							'propertyPath' => 'Tag.Article',
							'options' => array(),
							'has' => true,
							'belong' => true,
							'many' => true,
							'habtmAlias' => 'ArticlesTag',
							'habtmParentKey' => 'tag_id',
							'habtmTargetKey' => 'article_id',
							'external' => true,
						),
					),
				),
				// }}}
			),
		);
	}

/**
 * Tests that parseContain method throws an exception,
 * if the parent model is not associated with the specified model.
 *
 * @return void
 *
 * @expectedException InvalidArgumentException
 * @expectedExceptionMessage Model "User" is not associated with model "Something"
 */
	public function testParseContainThrowsException() {
		$User = ClassRegistry::init('User');

		$method = new ReflectionMethod('EagerLoader', 'parseContain');
		$method->setAccessible(true);
		$method->invokeArgs($this->EagerLoader, array(
			$User,
			'Something',
			array('options' => array(), 'contain' => array()),
			array(
				'root' => 'User',
				'aliasPath' => 'User',
				'propertyPath' => '',
			)
		));
	}

/**
 * Tests normalizeQuery method
 *
 * @param array $query Query
 * @param array $expected Expected
 * @return void
 *
 * @dataProvider dataProviderForTestNormalizeQuery
 */
	public function testNormalizeQuery($query, $expected) {
		$this->loadFixtures('User');

		$User = ClassRegistry::init('User');

		$method = new ReflectionMethod('EagerLoader', 'normalizeQuery');
		$method->setAccessible(true);
		$result = $method->invokeArgs($this->EagerLoader, array(
			$User,
			$query,
		));

		$this->assertEquals($expected, $result);
	}

/**
 * Data provider for testNormalizeQuery
 *
 * @return array
 */
	public function dataProviderForTestNormalizeQuery() {
		return array(
			array(
				// {{{ #0
				array(),
				array(
					'fields' => array(
						'User.id',
						'User.user',
						'User.password',
						'User.created',
						'User.updated',
					),
					'conditions' => array(),
				),
				// }}}
			),
			array(
				// {{{ #1
				array(
					'fields' => 'User.id',
					'conditions' => '1 = 1',
				),
				array(
					'fields' => array('User.id'),
					'conditions' => array('1 = 1'),
				),
				// }}}
			),
			array(
				// {{{ #2
				array(
					'fields' => array(
						'User.id',
						'user',
						'password',
					),
					'conditions' => array(
						'user' => 'larry',
					),
				),
				array(
					'fields' => array(
						'User.id',
						'User.user',
						'User.password',
					),
					'conditions' => array(
						'User.user' => 'larry',
					),
				),
				// }}}
			),
		);
	}
}
