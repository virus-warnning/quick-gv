<?php
namespace MediaWiki\Extension\QuickGV\Tests;

use MediaWiki\Extension\QuickGV\FieldResult;
use PHPUnit\Framework\TestCase;

class FieldResultTest extends TestCase {

	public function testCreateOkay() {
		$result = FieldResult::createOkay( 'name', 'test' );
		$this->assertSame( 'name', $result->Field );
		$this->assertSame( 'test', $result->FilteredValue );
		$this->assertSame( '', $result->ErrorMessage );
		$this->assertSame( '', $result->WarningMessage );
	}

	public function testCreateWarning() {
		$result = FieldResult::createWarning( 'theme', 'default', 'Unknown theme' );
		$this->assertSame( 'theme', $result->Field );
		$this->assertSame( 'default', $result->FilteredValue );
		$this->assertSame( '', $result->ErrorMessage );
		$this->assertSame( 'Unknown theme', $result->WarningMessage );
	}

	public function testCreateError() {
		$result = FieldResult::createError( 'syntax', 'Too long' );
		$this->assertSame( 'syntax', $result->Field );
		$this->assertNull( $result->FilteredValue );
		$this->assertSame( 'Too long', $result->ErrorMessage );
		$this->assertSame( '', $result->WarningMessage );
	}

}
