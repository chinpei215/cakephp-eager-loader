[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE.txt)
[![Build Status](https://img.shields.io/travis/chinpei215/cakephp-eager-loader/master.svg?style=flat-square)](https://travis-ci.org/chinpei215/cakephp-eager-loader)
[![Coverage Status](https://img.shields.io/coveralls/chinpei215/cakephp-eager-loader.svg?style=flat-square)](https://coveralls.io/r/chinpei215/cakephp-eager-loader?branch=master)
[![Scrutinizer](https://scrutinizer-ci.com/g/chinpei215/cakephp-eager-loader/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/chinpei215/cakephp-eager-loader)

# EagerLoader Plugin for CakePHP 2.x

An eager loading beahavior plugin for CakePHP 2.x which is highly compatible to the
[Containable behavior](http://book.cakephp.org/2.0/en/core-libraries/behaviors/containable.html)
but generates better queries.

## Requirements

* CakePHP 2.6+
* PHP 5.3+

## Installation

See the
[How to Install Plugins](http://book.cakephp.org/2.0/en/plugins/how-to-install-plugins.html)
in the CakePHP documentation for general help.

* Put the `EagerLoader` directory into your plugin directory or
  install the plugin with [Composer](https://getcomposer.org/) from the directory
  where your **composer.json** file is located:

```sh
php composer.phar require chinpei215/cakephp-eager-loader
```

* Load the plugin in your **app/Config/bootstrap.php** file:

```php
CakePlugin::load('EagerLoader');
```

* And [enable the behavior](http://book.cakephp.org/2.0/en/models/behaviors.html#using-behaviors)
  in your models or in your **app/Model/AppModel.php**:

```` php
class Post extends AppModel {
    public $actsAs = array('EagerLoader.EagerLoader');
}
````

## Usage

```php
$Comment->find('first', [
	'contain' => [
		'Article.User.Profile',
		'User.Profile',
	]
]);
```

`EagerLoaderBehavior` has a high compatibility with `ContainableBehavior`, but generates better queries.
In the above example, only 2 queries will be executed such as the following:
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
For example `EagerLoaderBehavior::contain()` is not implemented yet.

Then disabling `EagerLoaderBehavior` on the fly, you can use `ContainableBehavior::contain()` instead:
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
