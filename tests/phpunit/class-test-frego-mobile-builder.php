<?php
/**
 * Test_Frego_Mobile_Builder
 *
 * @package FregoMobileBuilder
 */

namespace FregoMobileBuilder;

/**
 * Class Test_Frego_Mobile_Builder
 *
 * @package FregoMobileBuilder
 */
class Test_Frego_Mobile_Builder extends \WP_UnitTestCase {

	/**
	 * Test _frego_mobile_builder_php_version_error().
	 *
	 * @see _frego_mobile_builder_php_version_error()
	 */
	public function test_frego_mobile_builder_php_version_error() {
		ob_start();
		_frego_mobile_builder_php_version_error();
		$buffer = ob_get_clean();
		$this->assertContains( '<div class="error">', $buffer );
	}

	/**
	 * Test _frego_mobile_builder_php_version_text().
	 *
	 * @see _frego_mobile_builder_php_version_text()
	 */
	public function test_frego_mobile_builder_php_version_text() {
		$this->assertContains( 'Frego Mobile Builder plugin error:', _frego_mobile_builder_php_version_text() );
	}
}
