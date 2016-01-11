[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE.txt)
[![Build Status](https://img.shields.io/travis/chinpei215/cakephp-eager-loader/master.svg?style=flat-square)](https://travis-ci.org/chinpei215/cakephp-eager-loader) 
[![Coverage Status](https://img.shields.io/coveralls/chinpei215/cakephp-eager-loader.svg?style=flat-square)](https://coveralls.io/r/chinpei215/cakephp-eager-loader?branch=master) 

# EagerLoader Plugin for CakePHP 2.x

## Requirements

* CakePHP 2.x
* PHP 5.3+

## Installation

* Put `EagerLoader` directory into your plugin directory. You can also install via Composer.
* Enable `EagerLoader` plugin in your `app/Config/bootstrap.php` file.
* Enable `EagerLoader.EagerLoader` behavior in your model.

## Usage

```php
$Comment->find('first', [
	'contain' => array(
		'Article.User.Profile',
		'User.Profile',
	)
]);
```

`EagerLoaderBehavior` has a high compatibility with `ContainableBehavior`, but generates better queries.
In the above example, only 2 queries will be executed.
```sql
SELECT 
	Comment.id, ...
FROM 
	comments AS Comment
	LEFT JOIN articles AS Article ON (Comment.article_id = Article.id)
	LEFT JOIN users AS User ON (Article.user_id = User.id)
	LEFT JOIN profiles AS Profile ON (User.id = Profile.user_id)
WHERE 
	1 = 1
```
```sql
SELECT
	User.id, ...
FROM
	users AS User 
	LEFT JOIN profiles AS Profile ON (User.id = Profile.user_id) 
WHERE
	User.id IN (1, 2, 3)
```
If using `ContainableBehavior`, how many queries are executed? 10 or more?

## Incompatibility problems

`EagerLoaderBehavior` returns almost same results as `ContainableBehavior`, however you might encounter incompatibility problems between the 2 behaviors.
For example `agerLoaderBehavior::contain()` is not implemented yet.

Then disabling EagerLoader on the fly, you can use `ContainableBehavior::contain()` instead:
```php
$Comment->Behaviors->disable('EagerLoader');
$Comment->Behaviors->load('Containable');
$Comment->contain('Article'); 
$result = $Comment->find('first');
```

For your information, `EagerLoaderBehavior` can be coexistent with `ContainableBehavior`.
```php
$actsAs = [
	'EagerLoader.EagerLoader', // Requires higher priority than Containable
	'Containable'
]
```
Using this way, you need not to call `load('Containable')` in the above example.
