<?php
/**
 * Test bootstrap: stub MediaWiki globals and load autoloader
 */

// Stub for MediaWiki's wfMessage()
// Returns an object with a plain() method that returns a template string
function wfMessage( $key, $args = [] ) {
	$templates = [
		'validation-syntax'  => 'Syntax exceeds max size of %d characters.',
		'validation-name'    => 'Name must be between %d and %d characters.',
		'validation-theme'   => 'Theme must be one of: %s.',
		'validation-usage'   => 'Usage must be a valid value.',
		'validation-dump-env' => 'dump-env must be boolean value.',
		'validation-dump-gv'  => 'dump-gv must be boolean value.',
	];
	$template = $templates[$key] ?? $key;
	return new class( $template ) {
		public function __construct( private string $template ) {}
		public function plain() {
			return $this->template;
		}
	};
}

// Define MW_INSTALL_PATH if not set
if ( !defined( 'MW_INSTALL_PATH' ) ) {
	define( 'MW_INSTALL_PATH', __DIR__ . '/..' );
}

// Load Composer autoloader
require_once __DIR__ . '/../vendor/autoload.php';
