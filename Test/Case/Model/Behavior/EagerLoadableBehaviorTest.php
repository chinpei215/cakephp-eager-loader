<?php
require_once App::pluginPath('EagerLoadable') . 'Test' . DS . 'bootstrap.php';

class EagerLoadableBehaviorTest extends CakeTestCase {

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
	);

/**
 * 
 *
 * @return void
 *
 * @dataProvider dataProviderForTestEagerLoad
 */
	public function testEagerLoad($model, $options, $expectedQueryCount, $expectedResults) {
		$model = ClassRegistry::init($model);
		$db = $model->getDataSource();

		$log = $db->getLog();
		$before = $log['count'];

		$results = $model->find('all', $options);

		$log = $db->getLog();
		$after = $log['count'];

		$this->assertEquals($expectedQueryCount, $after - $before);
		$this->assertEquals($expectedResults, $results);
	}

/**
 * 
 *
 * @return array
 */
	public function dataProviderForTestEagerLoad() {
		return array(
			array(
				// {{{ #0
				'Article',
				array(
					'contain' => array('User', 'Comment'),
				),
				2,
				array(
					array(
						'Article' => array(
							'id' => 1, 'user_id' => 1, 'title' => 'First Article', 'body' => 'First Article Body',
							'published' => 'Y', 'created' => '2007-03-18 10:39:23', 'updated' => '2007-03-18 10:41:31'
						),
						'User' => array(
							'id' => 1, 'user' => 'mariano', 'password' => '5f4dcc3b5aa765d61d8327deb882cf99',
							'created' => '2007-03-17 01:16:23', 'updated' => '2007-03-17 01:18:31'
						),
						'Comment' => array(
							array(
								'id' => 1, 'article_id' => 1, 'user_id' => 2, 'comment' => 'First Comment for First Article',
								'published' => 'Y', 'created' => '2007-03-18 10:45:23', 'updated' => '2007-03-18 10:47:31'
							),
							array(
								'id' => 2, 'article_id' => 1, 'user_id' => 4, 'comment' => 'Second Comment for First Article',
								'published' => 'Y', 'created' => '2007-03-18 10:47:23', 'updated' => '2007-03-18 10:49:31'
							),
							array(
								'id' => 3, 'article_id' => 1, 'user_id' => 1, 'comment' => 'Third Comment for First Article',
								'published' => 'Y', 'created' => '2007-03-18 10:49:23', 'updated' => '2007-03-18 10:51:31'
							),
							array(
								'id' => 4, 'article_id' => 1, 'user_id' => 1, 'comment' => 'Fourth Comment for First Article',
								'published' => 'N', 'created' => '2007-03-18 10:51:23', 'updated' => '2007-03-18 10:53:31'
							)
						)
					),
					array(
						'Article' => array(
							'id' => 2, 'user_id' => 3, 'title' => 'Second Article', 'body' => 'Second Article Body',
							'published' => 'Y', 'created' => '2007-03-18 10:41:23', 'updated' => '2007-03-18 10:43:31'
						),
						'User' => array(
							'id' => 3, 'user' => 'larry', 'password' => '5f4dcc3b5aa765d61d8327deb882cf99',
							'created' => '2007-03-17 01:20:23', 'updated' => '2007-03-17 01:22:31'
						),
						'Comment' => array(
							array(
								'id' => 5, 'article_id' => 2, 'user_id' => 1, 'comment' => 'First Comment for Second Article',
								'published' => 'Y', 'created' => '2007-03-18 10:53:23', 'updated' => '2007-03-18 10:55:31'
							),
							array(
								'id' => 6, 'article_id' => 2, 'user_id' => 2, 'comment' => 'Second Comment for Second Article',
								'published' => 'Y', 'created' => '2007-03-18 10:55:23', 'updated' => '2007-03-18 10:57:31'
							)
						)
					),
					array(
						'Article' => array(
							'id' => 3, 'user_id' => 1, 'title' => 'Third Article', 'body' => 'Third Article Body',
							'published' => 'Y', 'created' => '2007-03-18 10:43:23', 'updated' => '2007-03-18 10:45:31'
						),
						'User' => array(
							'id' => 1, 'user' => 'mariano', 'password' => '5f4dcc3b5aa765d61d8327deb882cf99',
							'created' => '2007-03-17 01:16:23', 'updated' => '2007-03-17 01:18:31'
						),
						'Comment' => array()
					)
				)
				// }}}
			),
			array(
				// {{{ #1
				'Attachment',
				array(
					'fields' => 'Attachment.id',
					'contain' => array(
						'Comment' => array('fields' => 'Comment.id'),
						'Comment.Article' => array('fields' => 'Article.id'),
						'Comment.Article.User' => array('fields' => 'User.id'),
					),
				),
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
				// {{{ #2
				'Article',
				array(
					'fields' => array('Article.id'),
					'contain' => array(
						'User' => array('fields' => 'User.id'),
						'Comment' => array(
							'fields' => 'Comment.id',
							'User' => array('fields' => 'User.id'),
						),
					),
				),
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
				// {{{ #3
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
				// {{{ #4
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
		);
	}

}
