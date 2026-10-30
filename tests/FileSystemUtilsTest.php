<?php
namespace MediaWiki\Extension\QuickGV\Tests;

use MediaWiki\Extension\QuickGV\FileSystemUtils;
use PHPUnit\Framework\TestCase;

class FileSystemUtilsTest extends TestCase {

	public function testGetSafeName() {
		$this->assertSame( 'abc', FileSystemUtils::getSafeName( 'abc' ) );
		$this->assertSame( 'a_b_c', FileSystemUtils::getSafeName( 'a/b\\c' ) );
		$this->assertNotContains( '/', FileSystemUtils::getSafeName( 'test/file' ) );
	}

	public function testGetSafeNameNonAscii() {
		$result = FileSystemUtils::getSafeName( 'héllo' );
		// Non-ASCII chars should be escaped
		$this->assertNotSame( 'héllo', $result );
	}

	public function testGetFriendlySize() {
		// Create a temp file for testing
		$tmpfile = tempnam( sys_get_temp_dir(), 'quickgv_test' );
		file_put_contents( $tmpfile, str_repeat( 'a', 512 ) );

		$this->assertSame( '512 B', FileSystemUtils::getFriendlySize( $tmpfile ) );

		file_put_contents( $tmpfile, str_repeat( 'a', 2048 ) );
		$this->assertSame( '2.00 KB', FileSystemUtils::getFriendlySize( $tmpfile ) );

		unlink( $tmpfile );
	}

	public function testGetFriendlySizeNonExistent() {
		$this->assertSame( '0 B', FileSystemUtils::getFriendlySize( '/nonexistent/file.svg' ) );
	}

	public function testSaveAndLoadSummary() {
		$tmpfile = tempnam( sys_get_temp_dir(), 'quickgv_test' );

		$summary = [
			'md5'     => 'abc123',
			'elapsed' => 1.5,
		];
		FileSystemUtils::saveSummary( $tmpfile, $summary );

		$loaded = FileSystemUtils::loadSummary( $tmpfile );
		$this->assertSame( 'abc123', $loaded['md5'] );
		$this->assertEqualsWithDelta( 1.5, $loaded['elapsed'], 0.001 );

		unlink( $tmpfile );
	}

	public function testLoadSummaryNonExistent() {
		$summary = FileSystemUtils::loadSummary( '/nonexistent/file.json' );
		$this->assertSame( '', $summary['md5'] );
		$this->assertEquals( 0.0, $summary['elapsed'] );
	}

}
