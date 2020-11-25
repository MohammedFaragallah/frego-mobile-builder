<?php
/**
 * Tests for Sample class.
 *
 * @package FregoMobileBuilder
 */

namespace FregoMobileBuilder\BazBar;

use FregoMobileBuilder\Plugin;

/**
 * Tests for Sample class.
 */
class Test_Sample extends \WP_UnitTestCase {

	/**
	 * Test constructor.
	 *
	 * @see Sample::__construct()
	 */
	public function test_construct() {
		$this->assertInstanceOf( Sample::class, \FregoMobileBuilder\get_plugin_instance()->sample );
	}

	/**
	 * Test init.
	 *
	 * @see Sample::init()
	 */
	public function test_init() {
		$sample = new Sample( new Plugin() );
		$sample->init();
		$this->assertEquals( 99, has_filter( 'body_class', [ $sample, 'body_class' ] ) );
	}

	/**
	 * Test for body_class() method.
	 *
	 * @see Sample::body_class()
	 */
	public function test_body_class() {
		$this->assertEquals( [ 'custom-class-name' ], \FregoMobileBuilder\get_plugin_instance()->sample->body_class( [] ) );
	}
}
