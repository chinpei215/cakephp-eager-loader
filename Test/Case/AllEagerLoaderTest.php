<?php
/**
 * All EagerLoader plugin tests
 */
class AllEagerLoaderTest extends PHPUnit_Framework_TestSuite {

/**
 * Assemble Test Suite
 * 
 * @return PHPUnit_Framework_TestSuite
 */
	public static function suite() {
		$suite = new CakeTestSuite('All Tests');
		$suite->addTestDirectoryRecursive(App::pluginPath('EagerLoader') . 'Test' . DS . 'Case' . DS);
		return $suite;
	}

}
