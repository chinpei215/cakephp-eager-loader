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
		'core.article',
		'core.comment',
		'core.attachment',
		'core.tag',
		'core.articles_tag',
		'core.apple',
		'core.category',
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
 * Tests reformatContain method
 *
 * @param array|string $contain Value of `contain` option
 * @param array $expected Expected
 * @return void
 *
 * @dataProvider dataProviderForTestReformatContain
 */
	public function testReformatContain($contain, $expected) {
		$method = new ReflectionMethod($this->EagerLoader, 'reformatContain');
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

		$method = new ReflectionMethod($this->EagerLoader, 'buildJoinQuery');
		$method->setAccessible(true);
		$result = $method->invokeArgs($this->EagerLoader, array(
			$User,
			array('fields' => array()),
			'INNER',
			array('Article.user_id' => 'User.id'),
			array(
				'conditions' => array(
					array('Article.user_id' => array(1, 2, 3)),
				)
			)
		));

		$expected = array(
			// {{{
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
					'table' => $User,
					'alias' => 'User',
					'conditions' => array(
						array('Article.user_id' => array(1, 2, 3)),
						array('Article.user_id' => (object)array('type' => 'identifier', 'value' => 'User.id')),
					),
				),
			),
			'conditions' => array(),
			'order' => array(),
			// }}}
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
		$this->loadFixtures('ArticlesTag', 'Apple');

		$model = ClassRegistry::init($model);

		$method = new ReflectionMethod($this->EagerLoader, 'parseContain');
		$method->setAccessible(true);

		$result = $method->invokeArgs($this->EagerLoader, array($model, $alias, $contain));

		// Remove something
		$result = Hash::remove($result, '{s}.{n}.target');
		$result = Hash::remove($result, '{s}.{n}.parent');
		$result = Hash::remove($result, '{s}.{n}.habtm');
		$result = Hash::remove($result, '{s}.{n}.finderQuery');

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
				// {{{ #0 normal
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
						array(
							'alias' => 'Article',
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
						array(
							'alias' => 'User',
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
							'eager' => true,
						),
					),
				),
				// }}}
			),
			array(
				// {{{ #1 complex
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
						array(
							'alias' => 'Article',
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
						array(
							'alias' => 'Comment',
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
						array(
							'alias' => 'Tag',
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
						array(
							'alias' => 'User',
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
						array(
							'alias' => 'Attachment',
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
				// {{{ #2 HABTM
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
						array(
							'alias' => 'Tag',
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
						array(
							'alias' => 'Article',
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
			array(
				// {{{ #3 association (external)
				'Article',
				'SecondComment',
				array('options' => array(), 'contain' => array()),
				array(
					'Article' => array(
						array(
							'alias' => 'SecondComment',
							'parentAlias' => 'Article',
							'parentKey' => 'id',
							'targetKey' => 'article_id',
							'aliasPath' => 'Article.SecondComment',
							'propertyPath' => 'SecondComment',
							'options' => array(
								'order' => 'SecondComment.id',
								'limit' => 1,
								'offset' => 1,
							),
							'has' => true,
							'belong' => false,
							'many' => false,
							'external' => true,
						),
					),
				),
				// }}}
			),
			array(
				// {{{ #4 association (finderQuery)
				'Apple',
				'NextApple',
				array(
					'options' => array(),
					'contain' => array(
						'ParentApple' => array('options' => array(), 'contain' => array()),
					)
				),
				array(
					'Apple' => array(
						array(
							'alias' => 'NextApple',
							'parentAlias' => 'Apple',
							'parentKey' => 'id',
							'targetKey' => 'apple_id',
							'aliasPath' => 'Apple.NextApple',
							'propertyPath' => 'NextApple',
							'options' => array(),
							'has' => true,
							'belong' => false,
							'many' => false,
							'external' => true,
						),
					),
					'Apple.NextApple' => array(
						array(
							'alias' => 'ParentApple',
							'parentAlias' => 'NextApple',
							'parentKey' => 'apple_id',
							'targetKey' => 'id',
							'aliasPath' => 'Apple.NextApple.ParentApple',
							'propertyPath' => 'NextApple.ParentApple',
							'options' => array(),
							'has' => false,
							'belong' => true,
							'many' => false,
							'external' => true,
						),
					),
				),
				// }}}
			),
			array(
				// {{{ #5 duplication
				'Attachment',
				'Comment',
				array(
					'options' => array(),
					'contain' => array(
						'Article' => array(
							'options' => array(),
							'contain' => array(
								'User' => array('options' => array(), 'contain' => array()),
							),
						),
						'User' => array('options' => array(), 'contain' => array()),
					)
				),
				array(
					'Attachment' => array(
						array(
							'alias' => 'Comment',
							'parentAlias' => 'Attachment',
							'parentKey' => 'comment_id',
							'targetKey' => 'id',
							'aliasPath' => 'Attachment.Comment',
							'propertyPath' => 'Comment',
							'options' => array(),
							'has' => false,
							'belong' => true,
							'many' => false,
							'external' => false,
						),
						array(
							'alias' => 'Article',
							'parentAlias' => 'Comment',
							'parentKey' => 'article_id',
							'targetKey' => 'id',
							'aliasPath' => 'Attachment.Comment.Article',
							'propertyPath' => 'Comment.Article',
							'options' => array(),
							'has' => false,
							'belong' => true,
							'many' => false,
							'external' => false,
							'eager' => true,
						),
						array(
							'alias' => 'User',
							'parentAlias' => 'Article',
							'parentKey' => 'user_id',
							'targetKey' => 'id',
							'aliasPath' => 'Attachment.Comment.Article.User',
							'propertyPath' => 'Comment.Article.User',
							'options' => array(),
							'has' => false,
							'belong' => true,
							'many' => false,
							'external' => false,
							'eager' => true,
						),
					),
					'Attachment.Comment' => array(
						array(
							'alias' => 'User',
							'parentAlias' => 'Comment',
							'parentKey' => 'user_id',
							'targetKey' => 'id',
							'aliasPath' => 'Attachment.Comment.User',
							'propertyPath' => 'Comment.User',
							'options' => array(),
							'has' => false,
							'belong' => true,
							'many' => false,
							'external' => true,
						),
					),
				),
				// }}}
			),
			array(
				// {{{ #6 self
				'Apple',
				'ParentApple',
				array(
					'options' => array(),
					'contain' => array(
						'ParentApple' => array('options' => array(), 'contain' => array()),
					)
				),
				array(
					'Apple' => array(
						array(
							'alias' => 'ParentApple',
							'parentAlias' => 'Apple',
							'parentKey' => 'apple_id',
							'targetKey' => 'id',
							'aliasPath' => 'Apple.ParentApple',
							'propertyPath' => 'ParentApple',
							'options' => array(),
							'has' => false,
							'belong' => true,
							'many' => false,
							'external' => false,
						),
					),
					'Apple.ParentApple' => array(
						array(
							'alias' => 'ParentApple',
							'parentAlias' => 'ParentApple',
							'parentKey' => 'apple_id',
							'targetKey' => 'id',
							'aliasPath' => 'Apple.ParentApple.ParentApple',
							'propertyPath' => 'ParentApple.ParentApple',
							'options' => array(),
							'has' => false,
							'belong' => true,
							'many' => false,
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

		$method = new ReflectionMethod($this->EagerLoader, 'parseContain');
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
 * @param string $model Model
 * @param array $query Query
 * @param array $expected Expected
 * @return void
 *
 * @dataProvider dataProviderForTestNormalizeQuery
 */
	public function testNormalizeQuery($model, $query, $expected) {
		$this->loadFixtures($model);

		$model = ClassRegistry::init($model);

		$db = $model->getDataSource();
		$startQuote = $db->startQuote;
		$endQuote = $db->endQuote;
		$db->startQuote = '';
		$db->endQuote = '';

		$method = new ReflectionMethod($this->EagerLoader, 'normalizeQuery');
		$method->setAccessible(true);
		$result = $method->invokeArgs($this->EagerLoader, array($model, $query));

		$db->startQuote = $startQuote;
		$db->endQuote = $endQuote;

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
				'User',
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
					'order' => array(),
				),
				// }}}
			),
			array(
				// {{{ #1
				'User',
				array(
					'fields' => 'User.id',
					'conditions' => '1 = 1',
				),
				array(
					'fields' => array('User.id'),
					'conditions' => array('1 = 1'),
					'order' => array(),
				),
				// }}}
			),
			array(
				// {{{ #2
				'User',
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
						array('User.user' => 'larry'),
					),
					'order' => array(),
				),
				// }}}
			),
			array(
				// {{{ #3
				'User',
				array(
					'fields' => 'id',
					'conditions' => array(),
					'order' => array(
						'id' => 'ASC',
						'User.id' => 'DESC',
						'created' => 'DESC',
						'updated',
					),
				),
				array(
					'fields' => array(
						'User.id',
					),
					'conditions' => array(),
					'order' => array(
						'User.id' => 'ASC',
						'User.created' => 'DESC',
						'User.updated',
					),
				),
				// }}}
			),
			array(
				// {{{ #4 Virtual fields
				'Category',
				array(
					'fields' => 'is_root',
					'conditions' => array('is_root' => 0),
					'order' => array(
						'is_root',
					),
				),
				array(
					'fields' => array(
						'(CASE WHEN Category.parent_id = 0 THEN 1 ELSE 0 END) AS  Category__is_root',
					),
					'conditions' => array(
						(object)array(
							'type' => 'expression',
							'value' => '(CASE WHEN Category.parent_id = 0 THEN 1 ELSE 0 END) = 0',
						)
					),
					'order' => array(
						'(CASE WHEN Category.parent_id = 0 THEN 1 ELSE 0 END)',
					),
				),
				// }}}
			),
		);
	}

/**
 * Tests mergeExternalExternal and mergeInternalExternal
 *
 * @param string $parent Name of the parent model
 * @param string $target Name of the target model
 * @param array $meta Meta data to be used for eager loading
 * @param array $results Results
 * @param array $fixtures Fixtures to be used
 * @param array $expectedArgument Expected argument for loadExternal method
 * @param array $expectedResults Expected results
 * @return void
 *
 * @dataProvider dataProviderForTestMergeExternal
 */
	public function testMergeExternal($parent, $target, $meta, $results, $fixtures, $expectedArgument, $expectedResults) {
		call_user_func_array(array($this, 'loadFixtures'), $fixtures);

		$parent = ClassRegistry::init($parent);
		$target = $parent->$target;

		$meta += array(
			'parent' => $parent,
			'target' => $target,
			'alias' => $target->alias,
			'parentAlias' => $parent->alias,
			'aliasPath' => $parent->alias . '.' . $target->alias,
			'propertyPath' => $parent->alias . '.' . $target->alias,
		);

		if (isset($meta['habtmAlias'])) {
			$meta['habtm'] = $parent->{$meta['habtmAlias']};
		}

		if ($target->alias === 'NextApple') {
			$meta['finderQuery'] = $target->getNextAppleFinderQuery();
		}

		$EagerLoader = $this->getMock('EagerLoader', array('loadExternal'));
		$EagerLoader->expects($this->once())
			->method('loadExternal')
			->with($target, $meta['aliasPath'], $expectedArgument)
			->will($this->returnArgument(2));

		$method = new ReflectionMethod($EagerLoader, ($meta['external'] ? 'mergeExternalExternal' : 'mergeInternalExternal'));
		$method->setAccessible(true);
		$merged = $method->invokeArgs($EagerLoader, array($target, $results, $meta));

		$this->assertEquals($expectedResults, $merged);
	}

/**
 * Data provider for testMergeExternal method
 *
 * @return array
 */
	public function dataProviderForTestMergeExternal() {
		return array(
			array(
				// {{{ #0 hasMany
				'User',
				'Comment',
				// $meta
				array(
					'parentKey' => 'id',
					'targetKey' => 'user_id',
					'options' => array('fields' => 'id'),
					'has' => true,
					'belong' => false,
					'many' => true,
					'external' => true,
				),
				// $results
				array(
					array(
						'User' => array(
							'id' => '2',
						),
					),
					array(
						'User' => array(
							'id' => '4',
						),
					),
				),
				// $frixtures
				array('User', 'Comment'),
				// $expectedArgument
				array(
					array(
						'Comment' => array(
							'id' => '1',
							'user_id' => '2',
						),
						'EagerLoaderModel' => array(
							'assoc_id' => '2',
						),
					),
					array(
						'Comment' => array(
							'id' => '2',
							'user_id' => '4',
						),
						'EagerLoaderModel' => array(
							'assoc_id' => '4',
						),
					),
					array(
						'Comment' => array(
							'id' => '6',
							'user_id' => '2',
						),
						'EagerLoaderModel' => array(
							'assoc_id' => '2',
						),
					),
				),
				// $expectedResults
				array(
					array(
						'User' => array(
							'id' => '2',
							'Comment' => array(
								array(
									'id' => '1',
									'user_id' => '2',
								),
								array(
									'id' => '6',
									'user_id' => '2',
								),
							),
						),
					),
					array(
						'User' => array(
							'id' => '4',
							'Comment' => array(
								array(
									'id' => '2',
									'user_id' => '4',
								),
							),
						),
					)
				),
				// }}}
			),
			array(
				// {{{ #1 hasMany (limited)
				'User',
				'Comment',
				// $meta
				array(
					'parentKey' => 'id',
					'targetKey' => 'user_id',
					'options' => array('fields' => 'id', 'limit' => 1),
					'has' => true,
					'belong' => false,
					'many' => true,
					'external' => true,
				),
				// $results
				array(
					array(
						'User' => array(
							'id' => '2',
						),
					),
					array(
						'User' => array(
							'id' => '4',
						),
					),
				),
				// $frixtures
				array('User', 'Comment'),
				// $expectedArgument
				array(
					array(
						'Comment' => array(
							'id' => '1',
							'user_id' => '2',
						),
						'EagerLoaderModel' => array(
							'assoc_id' => '2',
						),
					),
					array(
						'Comment' => array(
							'id' => '2',
							'user_id' => '4',
						),
						'EagerLoaderModel' => array(
							'assoc_id' => '4',
						),
					),
				),
				// $expectedResults
				array(
					array(
						'User' => array(
							'id' => '2',
							'Comment' => array(
								array(
									'id' => '1',
									'user_id' => '2',
								),
							),
						),
					),
					array(
						'User' => array(
							'id' => '4',
							'Comment' => array(
								array(
									'id' => '2',
									'user_id' => '4',
								),
							),
						),
					)
				),
				// }}}
			),
			array(
				// {{{ #2 hasOne (external)
				'Comment',
				'Attachment',
				// $meta
				array(
					'parentKey' => 'id',
					'targetKey' => 'comment_id',
					'options' => array('fields' => 'id'),
					'has' => true,
					'belong' => false,
					'many' => false,
					'external' => true,
				),
				// $results
				array(
					array(
						'Comment' => array(
							'id' => '5',
						),
					),
				),
				// $frixtures
				array('Comment', 'Attachment'),
				// $expectedArgument
				array(
					array(
						'Attachment' => array(
							'id' => '1',
							'comment_id' => '5',
						),
						'EagerLoaderModel' => array(
							'assoc_id' => '5',
						),
					),
				),
				// $expectedResults
				array(
					array(
						'Comment' => array(
							'id' => '5',
							'Attachment' => array(
								'id' => '1',
								'comment_id' => '5',
							),
						),
					),
				),
				// }}}
			),
			array(
				// {{{ #3 hasAndBelongsToMany
				'Article',
				'Tag',
				// $meta
				array(
					'parentKey' => 'id',
					'targetKey' => 'id',
					'options' => array('fields' => 'id', 'order' => array('ArticlesTag.article_id')),
					'habtmAlias' => 'ArticlesTag',
					'habtmParentKey' => 'article_id',
					'habtmTargetKey' => 'tag_id',
					'has' => true,
					'belong' => true,
					'many' => true,
					'external' => true,
				),
				// $results
				array(
					array(
						'Article' => array(
							'id' => '1',
						),
					),
					array(
						'Article' => array(
							'id' => '2',
						),
					),
				),
				// $frixtures
				array('Article', 'Tag', 'ArticlesTag'),
				// $expectedArgument
				array(
					array(
						'Tag' => array(
							'id' => '1',
						),
						'ArticlesTag' => array(
							'article_id' => '1',
							'tag_id' => '1',
						),
						'EagerLoaderModel' => array(
							'assoc_id' => '1',
						),
					),
					array(
						'Tag' => array(
							'id' => '2',
						),
						'ArticlesTag' => array(
							'article_id' => '1',
							'tag_id' => '2',
						),
						'EagerLoaderModel' => array(
							'assoc_id' => '1',
						),
					),
					array(
						'Tag' => array(
							'id' => '1',
						),
						'ArticlesTag' => array(
							'article_id' => '2',
							'tag_id' => '1',
						),
						'EagerLoaderModel' => array(
							'assoc_id' => '2',
						),
					),
					array(
						'Tag' => array(
							'id' => '3',
						),
						'ArticlesTag' => array(
							'article_id' => '2',
							'tag_id' => '3',
						),
						'EagerLoaderModel' => array(
							'assoc_id' => '2',
						),
					),
				),
				// $expectedResults
				array(
					array(
						'Article' => array(
							'id' => '1',
							'Tag' => array(
								array(
									'id' => '1',
									'ArticlesTag' => array(
										'article_id' => '1',
										'tag_id' => '1',
									),
								),
								array(
									'id' => '2',
									'ArticlesTag' => array(
										'article_id' => '1',
										'tag_id' => '2',
									),
								),
							),
						),
					),
					array(
						'Article' => array(
							'id' => '2',
							'Tag' => array(
								array(
									'id' => '1',
									'ArticlesTag' => array(
										'article_id' => '2',
										'tag_id' => '1',
									),
								),
								array(
									'id' => '3',
									'ArticlesTag' => array(
										'article_id' => '2',
										'tag_id' => '3',
									),
								),
							),
						),
					),
				),
				// }}}
			),
			array(
				// {{{ #4 hasOne (finderQuery)
				'Apple',
				'NextApple',
				// $meta
				array(
					'parentAlias' => 'Apple',
					'parentKey' => 'id',
					'targetKey' => 'apple_id',
					'aliasPath' => 'Apple.NextApple',
					'propertyPath' => 'Apple.NextApple',
					'options' => array(),
					'has' => true,
					'belong' => false,
					'many' => false,
					'external' => true,
				),
				// $results
				array(
					array(
						'Apple' => array(
							'id' => '1',
						),
					),
					array(
						'Apple' => array(
							'id' => '5',
						),
					),
				),
				// $fixtures
				array('Apple'),
				// $expectedArgument
				array(
					array(
						'NextApple' => array(
							'id' => '2',
							'apple_id' => '1',
							'color' => 'Bright Red 1',
							'name' => 'Bright Red Apple',
							'created' => '2006-11-22 10:43:13',
							'modified' => '2006-11-30 18:38:10',
						),
						'EagerLoaderModel' => array(
							'assoc_id' => '1',
						),
					),
					array(
						'NextApple' => array(
							'id' => '6',
							'apple_id' => 4,
							'color' => 'My new appleOrange',
							'name' => 'My new apple',
							'created' => '2006-12-25 05:29:39',
							'modified' => '2006-12-25 05:29:39',
						),
						'EagerLoaderModel' => array(
							'assoc_id' => '5',
						),
					),
				),
				// $expectedResults
				array(
					array(
						'Apple' => array(
							'id' => '1',
							'NextApple' => array(
								'id' => '2',
								'apple_id' => 1,
								'color' => 'Bright Red 1',
								'name' => 'Bright Red Apple',
								'created' => '2006-11-22 10:43:13',
								'modified' => '2006-11-30 18:38:10',
							),
						),
					),
					array(
						'Apple' => array(
							'id' => '5',
							'NextApple' => array(
								'id' => '6',
								'apple_id' => 4,
								'color' => 'My new appleOrange',
								'name' => 'My new apple',
								'created' => '2006-12-25 05:29:39',
								'modified' => '2006-12-25 05:29:39',
							),
						),
					),
				),
				// }}}
			),
			array(
				// {{{ #5 belongsTo
				'Article',
				'User',
				// $meta
				array(
					'parentAlias' => 'Article',
					'parentKey' => 'user_id',
					'targetKey' => 'id',
					'aliasPath' => 'Article.User',
					'propertyPath' => 'Article.User',
					'options' => array(
						'fields' => 'id',
					),
					'has' => false,
					'belong' => true,
					'many' => false,
					'external' => false,
				),
				// $results
				array(
					array(
						'Article' => array(
							'id' => '1',
							'user_id' => '1',
						),
						'User' => array(
							'id' => '1',
							'dummy' => '1',
						),
					),
					array(
						'Article' => array(
							'id' => '3',
							'user_id' => '1',
						),
						'User' => array(
							'id' => '1',
							'dummy' => '2',
						),
					)
				),
				// $fixtures
				array('Article', 'User'),
				// $expectedArgument
				array(
					array(
						'User' => array(
							'id' => '1',
							'dummy' => '1',
						),
					),
					array(
						'User' => array(
							'id' => '1',
							'dummy' => '2',
						),
					),
				),
				// $expectedResults
				array(
					array(
						'Article' => array(
							'id' => '1',
							'user_id' => '1',
							'User' => array(
								'id' => '1',
								'dummy' => '1',
							),
						),
					),
					array(
						'Article' => array(
							'id' => '3',
							'user_id' => '1',
							'User' => array(
								'id' => '1',
								'dummy' => '2',
							),
						),
					),
				),
				// }}}
			),
			array(
				// {{{ #6 hasOne
				'Comment',
				'Attachment',
				// $meta
				array(
					'parentAlias' => 'Comment',
					'parentKey' => 'id',
					'targetKey' => 'comment_id',
					'aliasPath' => 'Comment.Attachment',
					'propertyPath' => 'Comment.Attachment',
					'options' => array(
						'fields' => 'id',
					),
					'has' => true,
					'belong' => false,
					'many' => false,
					'external' => false,
				),
				// $results
				array(
					array(
						'Comment' => array(
							'id' => '1',
						),
						'Attachment' => array(
							'id' => null,
							'comment_id' => null,
						),
					),
					array(
						'Comment' => array(
							'id' => '5',
						),
						'Attachment' => array(
							'id' => '1',
							'comment_id' => '5',
						),
					)
				),
				// $fixtures
				array('Comment', 'Attachment'),
				// $expectedArgument
				array(
					array(
						'Attachment' => array(),
					),
					array(
						'Attachment' => array(
							'id' => '1',
							'comment_id' => '5',
						),
					),
				),
				// $expectedResults
				array(
					array(
						'Comment' => array(
							'id' => '1',
							'Attachment' => array(),
						),
					),
					array(
						'Comment' => array(
							'id' => '5',
							'Attachment' => array(
								'id' => '1',
								'comment_id' => '5',
							),
						),
					),
				),
				// }}}
			),
		);
	}

/**
 * Tests that no memory leak occurs
 *
 * @return void
 */
	public function testGarbageCollection() {
		$this->loadFixtures('User', 'Article');
		$User = ClassRegistry::init('User');

		for ($i = 0; $i < 1100; ++$i) {
			EagerLoader::handleBeforeFind($User, array('contain' => 'Article'));
		}

		$prop = new ReflectionProperty($this->EagerLoader, 'handlers');
		$prop->setAccessible(true);
		$handlers = $prop->getValue(null);
		$this->assertEquals(1000, count($handlers));
	}

/**
 * Tests that an exception occurs if invalid ID specified
 *
 * @return void
 *
 * @expectedException UnexpectedValueException
 * @expectedExceptionMessage EagerLoader "foo" is not found
 */
	public function testNotFound() {
		$User = ClassRegistry::init('User');
		EagerLoader::handleAfterFind($User, array(array('EagerLoaderModel' => array('id' => 'foo'))));
	}
}
