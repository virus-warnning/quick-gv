<?php
namespace MediaWiki\Extension\QuickGV\Tests;

use MediaWiki\Extension\QuickGV\Validator;
use PHPUnit\Framework\TestCase;

class ValidatorTest extends TestCase {

	public function testValidateDefaultValues() {
		$rawArgs = [
			'syntax' => 'A -> B',
		];
		$result = Validator::validate( $rawArgs );
		$this->assertTrue( $result['Passed'] );

		$fields = [];
		foreach ( $result['Fields'] as $fieldResult ) {
			$fields[$fieldResult->Field] = $fieldResult;
		}

		$this->assertSame( 'A -> B', $fields['syntax']->FilteredValue );
		$this->assertSame( 'G', $fields['name']->FilteredValue );
		$this->assertSame( 'default', $fields['theme']->FilteredValue );
		$this->assertSame( '', $fields['usage']->FilteredValue );
		$this->assertFalse( $fields['dump-env']->FilteredValue );
		$this->assertFalse( $fields['dump-gv']->FilteredValue );
	}

	public function testValidateSyntaxTooLong() {
		$longSyntax = str_repeat( 'a', Validator::MAX_SYNTAX_SIZE + 1 );
		$rawArgs = [
			'syntax' => $longSyntax,
		];
		$result = Validator::validate( $rawArgs );
		$this->assertFalse( $result['Passed'] );
	}

	public function testValidateNameTooShort() {
		$rawArgs = [
			'syntax' => 'A -> B',
			'name'   => '',
		];
		// Empty name with default should fall back to default with warning
		$result = Validator::validate( $rawArgs );
		// Empty string fails validation (length < 1), so it should use default with warning
		$fields = [];
		foreach ( $result['Fields'] as $fieldResult ) {
			$fields[$fieldResult->Field] = $fieldResult;
		}
		$this->assertSame( 'G', $fields['name']->FilteredValue );
		$this->assertNotSame( '', $fields['name']->WarningMessage );
	}

	public function testValidateNameTooLong() {
		$rawArgs = [
			'syntax' => 'A -> B',
			'name'   => str_repeat( 'a', 26 ),
		];
		$result = Validator::validate( $rawArgs );
		$fields = [];
		foreach ( $result['Fields'] as $fieldResult ) {
			$fields[$fieldResult->Field] = $fieldResult;
		}
		// Should fall back to default 'G' with warning
		$this->assertSame( 'G', $fields['name']->FilteredValue );
		$this->assertNotSame( '', $fields['name']->WarningMessage );
	}

	public function testValidateInvalidTheme() {
		$rawArgs = [
			'syntax' => 'A -> B',
			'theme'  => 'nonexistent',
		];
		$result = Validator::validate( $rawArgs );
		$fields = [];
		foreach ( $result['Fields'] as $fieldResult ) {
			$fields[$fieldResult->Field] = $fieldResult;
		}
		// Should fall back to default 'default' with warning
		$this->assertSame( 'default', $fields['theme']->FilteredValue );
		$this->assertNotSame( '', $fields['theme']->WarningMessage );
	}

	public function testValidateBooleanFields() {
		$rawArgs = [
			'syntax'   => 'A -> B',
			'dump-env' => 'true',
			'dump-gv'  => 'true',
		];
		$result = Validator::validate( $rawArgs );
		$this->assertTrue( $result['Passed'] );

		$fields = [];
		foreach ( $result['Fields'] as $fieldResult ) {
			$fields[$fieldResult->Field] = $fieldResult;
		}
		$this->assertTrue( $fields['dump-env']->FilteredValue );
		$this->assertTrue( $fields['dump-gv']->FilteredValue );
	}

	public function testValidateInvalidBoolean() {
		$rawArgs = [
			'syntax'   => 'A -> B',
			'dump-env' => 'notabool',
		];
		$result = Validator::validate( $rawArgs );
		$fields = [];
		foreach ( $result['Fields'] as $fieldResult ) {
			$fields[$fieldResult->Field] = $fieldResult;
		}
		// Should fall back to default false with warning
		$this->assertFalse( $fields['dump-env']->FilteredValue );
		$this->assertNotSame( '', $fields['dump-env']->WarningMessage );
	}

}
