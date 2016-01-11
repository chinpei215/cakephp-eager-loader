<?php
require_once App::pluginPath('EagerLoader') . 'Test' . DS . 'bootstrap.php';

class EagerLoaderBehaviorTest extends CakeTestCase {

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
		'core.sample',
		'core.category',
		'plugin.EagerLoader.external_comment',
		'plugin.EagerLoader.profile',
	);

/**
 * Tests find('all')
 *
 * @param string $model Name of the model
 * @param array $options Options for find method
 * @param array $fixtures Fixtures to be used
 * @param int $expectedQueryCount Expected query count
 * @param array $expectedResults Expected results
 * @return void
 *
 * @dataProvider dataProviderForTestFindAll
 */
	public function testFindAll($model, $options, $fixtures, $expectedQueryCount, $expectedResults) {
		call_user_func_array(array($this, 'loadFixtures'), $fixtures);

		$model = ClassRegistry::init($model);
		$model->Behaviors->load('QueryCounter');

		$results = $model->find('all', $options);

		$this->assertEquals($expectedQueryCount, $model->queryCount());
		$this->assertEquals($expectedResults, $results);
	}

/**
 * Data provider for testFindAll
 *
 * @return array
 */
	public function dataProviderForTestFindAll() {
		return array(
			array(
				// {{{ #0
				'Attachment',
				array(
					'fields' => 'Attachment.id',
					'contain' => array(
						'Comment' => array('fields' => 'Comment.id'),
						'Comment.Article' => array('fields' => 'Article.id'),
						'Comment.Article.User' => array('fields' => 'User.id'),
					),
				),
				array('Attachment', 'Comment', 'Article', 'User'),
				1,
				array(
					array(
						'Attachment' => array(
							'id' => '1',
							'comment_id' => '5',
						),
						'Comment' => array(
							'id' => '5',
							'article_id' => '2',
							'Article' => array(
								'id' => '2',
								'user_id' => '3',
								'User' => array(
									'id' => '3',
								),
							),
						),
					),
				),
				// }}}
			),
			array(
				// {{{ #1
				'Article',
				array(
					'fields' => array('Article.id'),
					'contain' => array(
						'User' => array('fields' => 'User.id'),
						'Comment' => array(
							'fields' => 'Comment.id',
							'order' => 'Comment.id',
							'User' => array('fields' => 'User.id'),
						),
					),
				),
				array('Article', 'Comment', 'User'),
				2,
				array(
					array(
						'Article' => array(
							'id' => '1',
							'user_id' => '1',
						),
						'User' => array(
							'id' => '1',
						),
						'Comment' => array(
							array(
								'id' => '1',
								'article_id' => '1',
								'user_id' => '2',
								'User' => array(
									'id' => '2',
								),
							),
							array(
								'id' => '2',
								'article_id' => '1',
								'user_id' => '4',
								'User' => array(
									'id' => '4',
								),
							),
							array(
								'id' => '3',
								'article_id' => '1',
								'user_id' => '1',
								'User' => array(
									'id' => '1',
								),
							),
							array(
								'id' => '4',
								'article_id' => '1',
								'user_id' => '1',
								'User' => array(
									'id' => '1',
								),
							),
						),
					),
					array(
						'Article' => array(
							'id' => '2',
							'user_id' => '3',
						),
						'User' => array(
							'id' => '3',
						),
						'Comment' => array(
							array(
								'id' => '5',
								'article_id' => '2',
								'user_id' => '1',
								'User' => array(
									'id' => '1',
								),
							),
							array(
								'id' => '6',
								'article_id' => '2',
								'user_id' => '2',
								'User' => array(
									'id' => '2',
								),
							),
						),
					),
					array(
						'Article' => array(
							'id' => '3',
							'user_id' => '1',
						),
						'User' => array(
							'id' => '1',
						),
						'Comment' => array(),
					),
				),
				// }}}
			),
			array(
				// {{{ #2
				'User',
				array(
					'fields' => array('User.user'),
					'contain' => array(
						'Article' => array(
							'fields' => array('Article.title'),
							'limit' => 1,
						),
					),
				),
				array('User', 'Article'),
				5,
				array(
					array(
						'User' => array(
							'id' => '1',
							'user' => 'mariano',
						),
						'Article' => array(
							array(
								'user_id' => '1',
								'title' => 'First Article',
							),
						),
					),
					array(
						'User' => array(
							'id' => '2',
							'user' => 'nate',
						),
						'Article' => array(
						),
					),
					array(
						'User' => array(
							'id' => '3',
							'user' => 'larry',
						),
						'Article' => array(
							array(
								'user_id' => '3',
								'title' => 'Second Article',
							),
						),
					),
					array(
						'User' => array(
							'id' => '4',
							'user' => 'garrett',
						),
						'Article' => array(
						),
					),
				),
				// }}}
			),
			array(
				// {{{ #3
				'Article',
				array(
					'fields' => 'Article.id',
					'contain' => array(
						'Tag' => array(
							'fields' => array('Tag.tag'),
						),
					),
					'conditions' => array(
						'Article.id' => 1,
					),
				),
				array('Article', 'Tag', 'ArticlesTag'),
				2,
				array(
					array(
						'Article' => array(
							'id' => '1',
						),
						'Tag' => array(
							array(
								'id' => '1',
								'tag' => 'tag1',
								'ArticlesTag' => array(
									'article_id' => '1',
									'tag_id' => '1',
								),
							),
							array(
								'id' => '2',
								'tag' => 'tag2',
								'ArticlesTag' => array(
									'article_id' => '1',
									'tag_id' => '2',
								),
							),
						),
					),
				),
				// }}}
			),
			array(
				// {{{ #4
				'User',
				array(
					'fields' => 'User.id',
					'contain' => array(
						'Article' => array(
							'fields' => array('Article.id'),
							'conditions' => array('Article.user_id' => 3),
						),
					),
					'conditions' => array(
						'User.id' => array('1', '3'),
					),
				),
				array('User', 'Article'),
				2,
				array(
					array(
						'User' => array(
							'id' => '1',
						),
						'Article' => array(),
					),
					array(
						'User' => array(
							'id' => '3',
						),
						'Article' => array(
							array(
								'id' => '2',
								'user_id' => '3',
							),
						),
					),
				),
				// }}}
			),
			array(
				// {{{ #5
				'Article',
				array(
					'fields' => 'Article.id',
					'contain' => array(
						'FirstComment' => array('fields' => 'id'),
						'SecondComment' => array('fields' => 'id'),
					),
				),
				array('Article', 'Comment'),
				7,
				array(
					array(
						'Article' => array(
							'id' => '1',
						),
						'FirstComment' => array(
							'id' => '1',
							'article_id' => '1',
						),
						'SecondComment' => array(
							'id' => '2',
							'article_id' => '1',
						),
					),
					array(
						'Article' => array(
							'id' => '2',
						),
						'FirstComment' => array(
							'id' => '5',
							'article_id' => '2',
						),
						'SecondComment' => array(
							'id' => '6',
							'article_id' => '2',
						),
					),
					array(
						'Article' => array(
							'id' => '3',
						),
						'FirstComment' => array(),
						'SecondComment' => array(),
					),
				),
				// }}}
			),
		);
	}

/**
 * Tests that find('list') also works
 *
 * @return void
 */
	public function testFindList() {
		$this->loadFixtures('Article', 'User');

		$Article = ClassRegistry::init('Article');
		$result = $Article->find('list', array(
			'fields' => array('Article.id', 'User.id'),
			'contain' => array('User')
		));

		$expected = array(
			1 => 1,
			2 => 3,
			3 => 1,
		);

		$this->assertEquals($expected, $result);
	}

/**
 * Tests that afterFind is called correctly
 *
 * @return void
 */
	public function testAfterFind() {
		$this->loadFixtures('Comment', 'Tag', 'ArticlesTag', 'Article', 'User', 'Profile', 'Attachment');

		$Comment = $this->getMockForModel('Comment', array('afterFind'));
		$Comment->expects($this->once())
			->method('afterFind')
			->with(
				// {{{
				array(
					array(
						'Comment' => array(
							'id' => '5',
							'article_id' => '2',
						),
						'Article' => array(
							'id' => '2',
							'user_id' => '3',
							'User' => array(
								'id' => '3',
								'Profile' => array(
									'id' => '1',
									'user_id' => '3',
								),
							),
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
						'Attachment' => array(
							'id' => '1',
							'comment_id' => '5',
						),
					),
				),
				true
				//}}}
			);

		$Tag = $this->getMockForModel('Tag', array('afterFind'));
		$Tag->expects($this->at(0))
			->method('afterFind')
			->with(
				// {{{
				array(
					array(
						'Tag' => array(
							'id' => '1',
						),
					),
				),
				false
				// }}}
			)
			->will($this->returnArgument(0));

		$Tag->expects($this->at(1))
			->method('afterFind')
			->with(
				// {{{
				array(
					array(
						'Tag' => array(
							'id' => '3',
						),
					),
				),
				false
				// }}}
			)
			->will($this->returnArgument(0));

		$Article = $this->getMockForModel('Article', array('afterFind'));
		$Article->expects($this->once())
			->method('afterFind')
			->with(
				// {{{
				array(
					array(
						'Article' => array(
							'id' => '2',
							'user_id' => '3',
						),
					)
				),
				false
				// }}}
			)
			->will($this->returnArgument(0));

		$User = $this->getMockForModel('User', array('afterFind'));
		$User->expects($this->once())
			->method('afterFind')
			->with(
				// {{{
				array(
					array(
						'User' => array(
							'id' => '3',
						),
					),
				),
				false
				// }}}
			)
			->will($this->returnArgument(0));

		$Profile = $this->getMockForModel('Profile', array('afterFind'));
		$Profile->expects($this->once())
			->method('afterFind')
			->with(
				// {{{
				array(
					array(
						'Profile' => array(
							'id' => '1',
							'user_id' => '3',
						),
					),
				),
				false
				// }}}
			)
			->will($this->returnArgument(0));

		$Attachment = $this->getMockForModel('Attachment', array('afterFind'));
		$Attachment->expects($this->once())
			->method('afterFind')
			->with(
				// {{{
				array(
					array(
						'Attachment' => array(
							'id' => '1',
							'comment_id' => '5',
						),
					),
				),
				false
				// }}}
			)
			->will($this->returnArgument(0));

		$result = $Comment->find('first', array(
			'fields' => 'Comment.id',
			'contain' => array(
				'Article' => array('fields' => 'Article.id'),
				'Article.Tag' => array('fields' => 'Tag.id'),
				'Article.User' => array('fields' => 'User.id'),
				'Article.User.Profile' => array('fields' => 'Profile.id'),
				'Attachment' => array('fields' => 'Attachment.id'),
			),
			'conditions' => array(
				'Comment.id' => 5,
			),
		));
	}

/**
 * Tests that afterFind works for in case of getting empty results.
 *
 * @return void
 */
	public function testAfterFindNoResults() {
		$this->loadFixtures('User', 'Article');

		$User = ClassRegistry::init('User');

		$user = $User->find('all', array(
			'contain' => 'Article',
			'conditions' => '1 != 1',
		));

		$this->assertSame(array(), $user);
	}

/**
 * Tests no contain
 *
 * @return void
 */
	public function testNoContain() {
		$this->loadFixtures('User', 'Article');
		$User = ClassRegistry::init('User');
		$result = $User->find('first', array('contain' => false));
		$this->assertFalse(isset($result['Article']));
	}

/**
 * Tests external datasource
 *
 * @return void
 */
	public function testExternalDatasource() {
		$this->loadFixtures('Article', 'ExternalComment');

		$Article = ClassRegistry::init('Article');

		$result = $Article->find('first', array(
			'fields' => 'id',
			'contain' => 'ExternalComment',
			'conditions' => array('id' => 3),
		));

		$expected = array(
			'Article' => array(
				'id' => 3,
			),
			'ExternalComment' => array(
				array(
					'id' => 1,
					'article_id' => 3,
					'comment' => 'External Comment',
				),
			)
		);

		$this->assertFalse($Article->useDbConfig === $Article->ExternalComment->useDbConfig);
		$this->assertEquals($expected, $result);
	}

/**
 * Tests that EagerLoaderBehavior can be coexistent with ContainableBehavior
 *
 * @return void
 */
	public function testCoexistentWithContainableBehavior() {
		$this->loadFixtures('Comment', 'Article', 'User');

		$expected = array(
			// {{{
			array(
				'Comment' => array(
					'id' => 1, 'article_id' => 1, 'user_id' => 2, 'comment' => 'First Comment for First Article',
					'published' => 'Y', 'created' => '2007-03-18 10:45:23', 'updated' => '2007-03-18 10:47:31'
				),
				'Article' => array(
					'id' => 1, 'user_id' => 1, 'title' => 'First Article', 'body' => 'First Article Body',
					'published' => 'Y', 'created' => '2007-03-18 10:39:23', 'updated' => '2007-03-18 10:41:31',
					'User' => array(
						'id' => 1, 'user' => 'mariano', 'password' => '5f4dcc3b5aa765d61d8327deb882cf99',
						'created' => '2007-03-17 01:16:23', 'updated' => '2007-03-17 01:18:31'
					),
				),
			),
			array(
				'Comment' => array(
					'id' => 2, 'article_id' => 1, 'user_id' => 4, 'comment' => 'Second Comment for First Article',
					'published' => 'Y', 'created' => '2007-03-18 10:47:23', 'updated' => '2007-03-18 10:49:31'
				),
				'Article' => array(
					'id' => 1, 'user_id' => 1, 'title' => 'First Article', 'body' => 'First Article Body',
					'published' => 'Y', 'created' => '2007-03-18 10:39:23', 'updated' => '2007-03-18 10:41:31',
					'User' => array(
						'id' => 1, 'user' => 'mariano', 'password' => '5f4dcc3b5aa765d61d8327deb882cf99',
						'created' => '2007-03-17 01:16:23', 'updated' => '2007-03-17 01:18:31'
					),
				),
			),
			array(
				'Comment' => array(
					'id' => 3, 'article_id' => 1, 'user_id' => 1, 'comment' => 'Third Comment for First Article',
					'published' => 'Y', 'created' => '2007-03-18 10:49:23', 'updated' => '2007-03-18 10:51:31'
				),
				'Article' => array(
					'id' => 1, 'user_id' => 1, 'title' => 'First Article', 'body' => 'First Article Body',
					'published' => 'Y', 'created' => '2007-03-18 10:39:23', 'updated' => '2007-03-18 10:41:31',
					'User' => array(
						'id' => 1, 'user' => 'mariano', 'password' => '5f4dcc3b5aa765d61d8327deb882cf99',
						'created' => '2007-03-17 01:16:23', 'updated' => '2007-03-17 01:18:31'
					),
				),
			),
			array(
				'Comment' => array(
					'id' => 4, 'article_id' => 1, 'user_id' => 1, 'comment' => 'Fourth Comment for First Article',
					'published' => 'N', 'created' => '2007-03-18 10:51:23', 'updated' => '2007-03-18 10:53:31'
				),
				'Article' => array(
					'id' => 1, 'user_id' => 1, 'title' => 'First Article', 'body' => 'First Article Body',
					'published' => 'Y', 'created' => '2007-03-18 10:39:23', 'updated' => '2007-03-18 10:41:31',
					'User' => array(
						'id' => 1, 'user' => 'mariano', 'password' => '5f4dcc3b5aa765d61d8327deb882cf99',
						'created' => '2007-03-17 01:16:23', 'updated' => '2007-03-17 01:18:31'
					),
				),
			),
			array(
				'Comment' => array(
					'id' => 5, 'article_id' => 2, 'user_id' => 1, 'comment' => 'First Comment for Second Article',
					'published' => 'Y', 'created' => '2007-03-18 10:53:23', 'updated' => '2007-03-18 10:55:31'
				),
				'Article' => array(
					'id' => 2, 'user_id' => 3, 'title' => 'Second Article', 'body' => 'Second Article Body',
					'published' => 'Y', 'created' => '2007-03-18 10:41:23', 'updated' => '2007-03-18 10:43:31',
					'User' => array(
						'id' => 3, 'user' => 'larry', 'password' => '5f4dcc3b5aa765d61d8327deb882cf99',
						'created' => '2007-03-17 01:20:23', 'updated' => '2007-03-17 01:22:31'
					),
				),
			),
			array(
				'Comment' => array(
					'id' => 6, 'article_id' => 2, 'user_id' => 2, 'comment' => 'Second Comment for Second Article',
					'published' => 'Y', 'created' => '2007-03-18 10:55:23', 'updated' => '2007-03-18 10:57:31'
				),
				'Article' => array(
					'id' => 2, 'user_id' => 3, 'title' => 'Second Article', 'body' => 'Second Article Body',
					'published' => 'Y', 'created' => '2007-03-18 10:41:23', 'updated' => '2007-03-18 10:43:31',
					'User' => array(
						'id' => 3, 'user' => 'larry', 'password' => '5f4dcc3b5aa765d61d8327deb882cf99',
						'created' => '2007-03-17 01:20:23', 'updated' => '2007-03-17 01:22:31'
					),
				),
			),
			// }}}
		);

		$options = array('contain' => 'Article.User');

		$Comment = ClassRegistry::init('Comment');
		$Comment->Behaviors->load('QueryCounter');

		$results = $Comment->find('all', $options);
		$this->assertEquals(1, $Comment->queryCount());
		$this->assertEquals($expected, $results);

		$Comment->Behaviors->load('Containable');
		$results = $Comment->find('all', $options);
		$this->assertEquals(1, $Comment->queryCount());
		$this->assertEquals($expected, $results);

		$Comment->Behaviors->disable('EagerLoader');
		$results = $Comment->find('all', $options);
		$this->assertEquals(7, $Comment->queryCount()); // ContainableBehavior cannot load deep associations eagerly
		$this->assertEquals($expected, $results);
	}

/**
 * Tests caling find method in afterFind
 *
 * @return void
 */
	public function testCallingFindInAfterFind() {
		$this->loadFixtures('Apple', 'Sample');

		$Apple = ClassRegistry::init('Apple');

		$options = array(
			'fields' => array(
				'Apple.id',
			),
			'contain' => 'SampleA',
			'conditions' => array('Apple.id' => 3),
		);

		$expected = array(
			// {{{
			'Apple' => array(
				'id' => '3',
			),
			'SampleA' => array(
				'id' => '1',
				'apple_id' => '3',
				'name' => 'sample1',
				'Apple' => array(
					'id' => '1',
					'apple_id' => '2',
					'ParentApple' => array(
						'id' => '2',
						'name' => 'Bright Red Apple'
					)
				)
			)
			// }}}
		);
		$result = $Apple->find('first', $options);
		$this->assertEquals($expected, $result);

		$Apple->Behaviors->load('Containable');
		$Apple->Behaviors->disable('EagerLoader');

		$expected = array(
			// {{{
			'Apple' => array(
				'id' => '3',
			),
			'SampleA' => array(
				'id' => '1',
				'apple_id' => '3',
				'name' => 'sample1',
				'Apple' => array(
					'id' => '1',
					'apple_id' => '2',
					'ParentApple' => array(
						'id' => '2',
						'name' => 'Bright Red Apple'
					)
				)
			),
			'Sample' => array( // ContainableBehavior will contain Sample wrongly
				array(
					'id' => '1',
					'apple_id' => '3',
					'name' => 'sample1',
					'Apple' => array(
						'id' => '1',
						'apple_id' => '2',
						'ParentApple' => array(
							'id' => '2',
							'name' => 'Bright Red Apple'
						)
					)
				)
			),
			// }}}
		);
		$result = $Apple->find('first', $options);
		$this->assertEquals($expected, $result);
	}

/**
 * Tests non-existent belongsTo association
 *
 * @return void
 */
	public function testNonExistentBelongsTo() {
		$this->loadFixtures('Category');

		$Category = ClassRegistry::init('Category');
		$result = $Category->find('first', array(
			'contain' => array(
				'ParentCategory'
			),
		));

		$expected = array(
			'Category' => array(
				'id' => '1',
				'parent_id' => '0',
				'name' => 'Category 1',
				'created' => '2007-03-18 15:30:23',
				'updated' => '2007-03-18 15:32:31'
			),
			'ParentCategory' => array(),
		);

		$this->assertEquals($expected, $result);
	}

/**
 * Tests duplicated aliases
 *
 * @return void
 */
	public function testDuplicatedAliases() {
		$this->loadFixtures('Comment', 'Article', 'User', 'Profile');

		$Comment = ClassRegistry::init('Comment');
		$Comment->Behaviors->load('QueryCounter');

		$result = $Comment->find('first', array(
			'contain' => array(
				'Article.User.Profile',
				'User',
			),
			'conditions' => array('Comment.id' => 5),
		));

		$expected = array(
			'Comment' => array(
				'id' => '5',
				'article_id' => '2',
				'user_id' => '1',
				'comment' => 'First Comment for Second Article',
				'published' => 'Y',
				'created' => '2007-03-18 10:53:23',
				'updated' => '2007-03-18 10:55:31'
			),
			'Article' => array(
				'id' => '2',
				'user_id' => '3',
				'title' => 'Second Article',
				'body' => 'Second Article Body',
				'published' => 'Y',
				'created' => '2007-03-18 10:41:23',
				'updated' => '2007-03-18 10:43:31',
				'User' => array(
					'password' => '5f4dcc3b5aa765d61d8327deb882cf99',
					'id' => '3',
					'user' => 'larry',
					'created' => '2007-03-17 01:20:23',
					'updated' => '2007-03-17 01:22:31',
					'Profile' => array(
						'id' => '1',
						'user_id' => '3',
						'nickname' => 'phpnut',
						'company' => 'Cake Software Foundation, Inc.'
					)
				)
			),
			'User' => array(
				'password' => '5f4dcc3b5aa765d61d8327deb882cf99',
				'id' => '1',
				'user' => 'mariano',
				'created' => '2007-03-17 01:16:23',
				'updated' => '2007-03-17 01:18:31'
			)
		);

		$this->assertEquals(2, $Comment->queryCount());
		$this->assertEquals($expected, $result);
	}
}
