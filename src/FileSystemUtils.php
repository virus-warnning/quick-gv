<?php
namespace MediaWiki\Extension\QuickGV;

class FileSystemUtils {

	/**
	 * Write a debug file
	 *
	 * @param string $file_name File name
	 * @param string $file_content File content
	 * @param int $mode Write mode (e.g. FILE_APPEND)
	 */
	public static function dumpDebugFile( $file_name, $file_content, $mode = 0 ) {
		if ( !ExtensionConstants::DUMP_DEBUG_FILES ) {
			return;
		}

		$debug_dir = sprintf( '%s/../debug', __DIR__ );
		if ( !is_dir( $debug_dir ) ) {
			mkdir( $debug_dir );
		}

		$target_file = sprintf( '%s/%s', $debug_dir, $file_name );
		if ( $mode === FILE_APPEND ) {
			file_put_contents( $target_file, $file_content . "\n", $mode );
		} else {
			file_put_contents( $target_file, $file_content );
		}
	}

	/**
	 * Get a human-readable file size
	 *
	 * @param string $filepath File path
	 * @return string Human-readable file size
	 */
	public static function getFriendlySize( $filepath ) {
		static $unit_ch = [ 'B', 'KB', 'MB' ];

		$size = file_exists( $filepath ) ? filesize( $filepath ) : 0;
		$unit_lv = 0;
		while ( $size >= 1024 && $unit_lv <= 2 ) {
			$size /= 1024;
			$unit_lv++;
		}

		if ( $unit_lv == 0 ) {
			return sprintf( '%d %s', $size, $unit_ch[$unit_lv] );
		} else {
			return sprintf( '%.2f %s', $size, $unit_ch[$unit_lv] );
		}
	}

	/**
	 * Sanitize file name by escaping characters not accepted by Windows
	 *
	 * @param string $unsafename Unsafe file name
	 * @return string Safe file name
	 */
	public static function getSafeName( $unsafename ) {
		$safename = '';
		$slen = strlen( $unsafename );
		$escape_chars = [ '.', '/', '\\' ];

		// escape non-ascii chars and path separators
		for ( $i = 0;$i < $slen;$i++ ) {
			$ch = $unsafename[$i];
			$cc = ord( $ch );
			if ( $cc < 32 || $cc > 127 || in_array( $ch, $escape_chars, true ) ) {
				$safename .= '_' . bin2hex( $ch );
			} else {
				$safename .= $ch;
			}
		}

		return $safename;
	}

	/**
	 * Load SVG conversion summary info with error tolerance
	 *
	 * @param string $file_path
	 * @return array{md5:string, elapsed:float}
	 */
	public static function loadSummary( $file_path ) {
		if ( is_file( $file_path ) ) {
			$summary = json_decode( file_get_contents( $file_path ), true ) ?: [
				'md5' => '',
				'elapsed' => 0.0
			];
		} else {
			$summary = [
				'md5' => '',
				'elapsed' => 0.0
			];
		}
		return $summary;
	}

	/**
	 * Save SVG conversion summary info
	 * - md5     MD5 digest of the input value
	 * - elapsed Time spent converting to SVG
	 *
	 * @param string $file_path File path
	 * @param array $summary Summary data
	 */
	public static function saveSummary( $file_path, array $summary ) {
		$json_options = JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES;
		$summary_ser = json_encode( $summary, $json_options );
		file_put_contents( $file_path, $summary_ser );
	}

}
