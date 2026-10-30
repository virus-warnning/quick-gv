<?php
namespace MediaWiki\Extension\QuickGV;

class FieldResult {
	public function __construct(
		public string $Field,
		public mixed $FilteredValue,
		public string $ErrorMessage,
		public string $WarningMessage,
	) {
	}

	/**
	 * Create a result for a passed validation
	 *
	 * @param string $field_name Field name
	 * @param mixed $value Filtered value
	 * @return self
	 */
	public static function createOkay( $field_name, $value ) {
		return new self( $field_name, $value, '', '' );
	}

	/**
	 * Create a result for a validation warning (using default value)
	 *
	 * @param string $field_name Field name
	 * @param mixed $value Default value
	 * @param string $warning_message Warning message
	 * @return self
	 */
	public static function createWarning( $field_name, $value, $warning_message ) {
		return new self( $field_name, $value, '', $warning_message );
	}

	/**
	 * Create a result for a failed validation
	 *
	 * @param string $field_name Field name
	 * @param string $error_message Error message
	 * @return self
	 */
	public static function createError( $field_name, $error_message ) {
		return new self( $field_name, null, $error_message, '' );
	}
}
