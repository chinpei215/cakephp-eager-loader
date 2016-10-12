# Change Log
All notable changes to this project are documented in this file.
This project adheres to [Semantic Versioning](http://semver.org/).

## [Version 0.3.4](https://github.com/chinpei215/cakephp-eager-loader/releases/tag/0.3.4)
**2016-10-12**
- Fixed an `E_NOTICE` error occurred when parent record not found.

## [Version 0.3.3](https://github.com/chinpei215/cakephp-eager-loader/releases/tag/0.3.3)
**2016-09-10**
- Fixed incorrect propertyPath on deep associations. [#19](https://github.com/chinpei215/cakephp-eager-loader/issues/19)

## [Version 0.3.2](https://github.com/chinpei215/cakephp-eager-loader/releases/tag/0.3.2)
**2016-03-17**
- Removed superfluous tearDown(). [#16](https://github.com/chinpei215/cakephp-eager-loader/pull/16).
- Simplified isExternal(). [#15](https://github.com/chinpei215/cakephp-eager-loader/pull/15).
- Added a change log.
- Added a contribution guide. [#11](https://github.com/chinpei215/cakephp-eager-loader/pull/11).
- Added .gitattributes file. [#9](https://github.com/chinpei215/cakephp-eager-loader/pull/9).
- Added .gitignore file. [#8](https://github.com/chinpei215/cakephp-eager-loader/pull/8).
- Added .editorconfig file. [#7](https://github.com/chinpei215/cakephp-eager-loader/pull/7).
- Improved installation guide. [#4](https://github.com/chinpei215/cakephp-eager-loader/pull/4), [#10](https://github.com/chinpei215/cakephp-eager-loader/pull/10).
- Improved composer.json. [#3](https://github.com/chinpei215/cakephp-eager-loader/pull/3) (Add two keywords).
- Fixed API deprecation warning. [#2](https://github.com/chinpei215/cakephp-eager-loader/pull/2) (Use caret for composer/installers dependency).

## [Version 0.3.1](https://github.com/chinpei215/cakephp-eager-loader/releases/tag/0.3.1)
**2016-02-13**
- Fixed PHP7-incompatible test.
- Fixed invalid test cases.
- Updated .travis.yml (Added PHP7.0 to the matrix).

## [Version 0.3.0](https://github.com/chinpei215/cakephp-eager-loader/releases/tag/0.3.0)
**2016-02-13**
- Fixed bug [#1](https://github.com/chinpei215/cakephp-eager-loader/issues/1) (Virtual fields do not work with associations).
- Fixed an PDOException being thrown when using SQL Server.

## [Version 0.2.0](https://github.com/chinpei215/cakephp-eager-loader/releases/tag/0.2.0)
**2016-02-11**
- Fixed an PDOException being thrown when using table prefixes in a datasource.
- Updated .travis.yml (Added CakePHP2.8 to the matrix).

## [Version 0.1.0](https://github.com/chinpei215/cakephp-eager-loader/releases/tag/0.1.0)
**2016-01-12**
- Initial release.
