<?php
/**
 * Data validation
 *
 * Validation primarily relies on MediaWiki's built-in mechanisms,
 * but the following checks are not covered and must be implemented manually:
 *
 * - String length validation
 * - Boolean string validation
 * - Boolean string to bool conversion
 */

namespace MediaWiki\Extension\QuickGV;

class Validator {

	// Maximum size of dot syntax content (1M)
	public const MAX_SYNTAX_SIZE = 1048576;

	// Minimum name length
	public const NAME_LBOUND = 1;

	// Maximum name length
	public const NAME_UBOUND = 25;

	/**
	 * Field data constraint definitions
	 *
	 * @return array
	 */
	public static function getDescriptor() {
		$boolSpec = [
			'type' => 'select',
			'options' => [
				'true' => 'true',
				'false' => 'false',
			],
			'default' => 'false',
			'filter-callback' => static function ( $value ) {
				return filter_var( $value, FILTER_VALIDATE_BOOLEAN );
			}
		];
		return [
			'syntax' => [
				'type' => 'text',
				'validation-callback' => static function ( $value ) {
					if ( mb_strlen( $value, 'UTF-8' ) > self::MAX_SYNTAX_SIZE ) {
						return false;
					}
					return true;
				}
			],
			'name' => [
				'type' => 'text',
				'default' => 'G',
				'validation-callback' => static function ( $value ) {
					$slen = mb_strlen( $value, 'UTF-8' );
					if ( $slen < self::NAME_LBOUND || $slen > self::NAME_UBOUND ) {
						return false;
					}
					return true;
				}
			],
			'theme' => [
				'type' => 'select',
				'options' => [
					'Cold'    => 'cold',
					'Warm'    => 'warm',
					'Sakura'  => 'sakura',
					'Default' => 'default',
				],
				'default' => 'default',
			],
			'usage' => [
				'type' => 'text',
				'default' => '',
			],
			'dump-env' => $boolSpec,
			'dump-gv'  => $boolSpec,
		];
	}

	/**
	 * Build templated error/warning message
	 *
	 * @param string $fieldName Field name
	 * @param array $fieldSpec Field spec definition
	 * @return string Templated message
	 */
	public static function buildValidationMessage( $fieldName, $fieldSpec ) {
		$langKey = sprintf( 'validation-%s', $fieldName );
		$template = wfMessage( $langKey )->plain();
		$message = '';
		switch ( $langKey ) {
			case 'validation-syntax':
				$message = sprintf( $template, self::MAX_SYNTAX_SIZE );
				break;
			case 'validation-name':
				$message = sprintf( $template, self::NAME_LBOUND, self::NAME_UBOUND );
				break;
			case 'validation-theme':
				$values = array_values( $fieldSpec['options'] );
				$message = sprintf( $template, implode( ', ', $values ) );
				break;
			default:
				$message = $template;
		}
		return $message;
	}

	/**
	 * Validate all field data
	 *
	 * @param array $rawArgs Raw input arguments to validate
	 * @return array{Passed: bool, Fields: FieldResult[]}
	 */
	public static function validate( array $rawArgs ) {
		$descriptor = self::getDescriptor();
		$passed = true;
		$fieldResults = [];

		foreach ( $descriptor as $name => $fieldSpec ) {
			$rawValue = $rawArgs[$name] ?? null;

			// No value provided & has default → use default, skip validation
			if ( $rawValue === null && isset( $fieldSpec['default'] ) ) {
				$defaultValue = $fieldSpec['default'];
				if ( isset( $fieldSpec['filter-callback'] ) ) {
					$defaultValue = call_user_func( $fieldSpec['filter-callback'], $defaultValue );
				}
				$fieldResults[] = FieldResult::createOkay( $name, $defaultValue );
				continue;
			}

			// No value provided & no default → error
			if ( $rawValue === null ) {
				$message = self::buildValidationMessage( $name, $fieldSpec );
				$fieldResults[] = FieldResult::createError( $name, $message );
				$passed = false;
				continue;
			}

			$valid = true;

			// Run validation-callback if defined
			if ( isset( $fieldSpec['validation-callback'] ) ) {
				$valid = call_user_func( $fieldSpec['validation-callback'], $rawValue );
			}

			// For select type, ensure value is in allowed options
			if ( $valid && $fieldSpec['type'] === 'select' ) {
				$valid = in_array( $rawValue, array_values( $fieldSpec['options'] ), true );
			}

			if ( $valid !== true ) {
				$message = self::buildValidationMessage( $name, $fieldSpec );
				if ( isset( $fieldSpec['default'] ) ) {
					$defaultValue = $fieldSpec['default'];
					if ( isset( $fieldSpec['filter-callback'] ) ) {
						$defaultValue = call_user_func( $fieldSpec['filter-callback'], $defaultValue );
					}
					$fieldResults[] = FieldResult::createWarning( $name, $defaultValue, $message );
				} else {
					$fieldResults[] = FieldResult::createError( $name, $message );
					$passed = false;
				}
				continue;
			}

			// Apply filter-callback if defined (e.g. bool string → bool)
			$filtered = $rawValue;
			if ( isset( $fieldSpec['filter-callback'] ) ) {
				$filtered = call_user_func( $fieldSpec['filter-callback'], $rawValue );
			}

			$fieldResults[] = FieldResult::createOkay( $name, $filtered );
		}

		return [
			'Passed' => $passed,
			'Fields' => $fieldResults
		];
	}
}
