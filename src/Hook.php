<?php
/**
 * Quick Graphviz diagram generation extension
 *
 * @author Raymond Wu https://github.com/virus-warnning
 */

namespace MediaWiki\Extension\QuickGV;

use ExtensionRegistry;

class Hook {

	/** @var array System environment error messages */
	public static $sys_errors = [];

	/** @var string Version string */
	private static $version = '0.0.0';

	/** @var string Path to php executable */
	private static $php_cmd = '';

	/** @var string Path to dot executable */
	private static $dot_cmd = '';

	/** @var string Graphviz version */
	private static $gvz_ver = '';

	/**
	 * Mount point setup (triggered by MediaWiki)
	 *
	 * @param \MediaWiki\Parser &$parser MediaWiki's syntax processor
	 *
	 * @return bool
	 */
	public static function init( &$parser ) {
		// Get version string
		self::$version = ExtensionRegistry::getInstance()->getAllThings()['QuickGV']['version'];

		// Get path to php executable
		self::$php_cmd = Command::findExecutable( 'php' );

		// Get path to dot executable
		self::$dot_cmd = Command::findExecutable( 'dot' );

		// Get Graphviz version info
		$result = Command::execute( self::$dot_cmd, '-V' );
		if ( $result->getExitCode() === 0 ) {
			$stderr = $result->getStderr();
			if ( preg_match( '/version (\d+\.\d+\.\d+)/', $stderr, $matches ) ) {
				self::$gvz_ver = $matches[1];
			}
		}

		// Register function hook
		$parser->setHook( 'quickgv', [ self::class, 'render' ] );

		return true;
	}

	/**
	 * Diagram generation (triggered by MediaWiki)
	 *
	 * @param string $dot_syntax Dot syntax content written in MediaWiki
	 * @param array $props Attributes within the tag
	 * @param null $parser MediaWiki's syntax parser
	 * @param object|null $frame PPFrame_Hash
	 *
	 * @return array{0:string, noparse:bool, isHTML:bool}
	 */
	public static function render( $dot_syntax, $props = [], $parser = null, $frame = null ) {
		if ( $parser === null ) {
			return [
				'<pre class="mw-message-box mw-message-box-error">QuickGV: parser is null</pre>',
				'noparse' => true,
				'isHTML' => true
			];
		}

		$hook = new Hook( $dot_syntax, $props, $parser, $frame );
		return [
			$hook->genFullHtml(),
			'noparse' => true,
			'isHTML' => true
		];
	}

	/**
	 * Generate Parser Hook <quickgv>
	 */
	public function __construct(
		// Content inside <quickgv>...</quickgv>
		private string $dot_syntax,
		// Attributes defined on <quickgv>
		private array $props,
		// MediaWiki parser
		private object $parser,
		// PPFrame_Hash
		private ?object $frame,
		// Graph name
		private string $gname = 'G',
		// Theme
		private string $theme = 'default',
		// Usage type (record, neato, etc.)
		private string $usage = '',
		// SVG file path
		private string $svg_file = '',
		// SVG file modification time
		private int $svg_mtime = 0,
		// SVG HTTP absolute path (without protocol, server name)
		private string $svg_uri = '',
		// SVG conversion elapsed time
		private float $elapsed = 0.0,
		// Whether SVG was generated successfully
		private bool $successful = false,
		// Warning messages during processing
		private array $warnings = [],
		// Error messages during processing
		private array $errors = [],
		// Whether to display environment table
		private bool $showMeta = false,
		// Whether to display dot source code
		private bool $showDot = false,
		// Whether cache was detected
		private bool $hitCache = false,
		// Current syntax MD5
		private string $md5_incoming = '',
		// Cached syntax MD5
		private string $md5_existed = '',
		// Summary file path
		private string $summary_file = '',
	) {
		global $wgScriptPath;

		// Skip subsequent operations when system environment is abnormal
		if ( count( self::$sys_errors ) > 0 ) {
			return;
		}

		// Input value validation
		$props['syntax'] = $dot_syntax;
		$results = Validator::validate( $props );
		if ( !$results['Passed'] ) {
			foreach ( $results['Fields'] as $fieldResult ) {
				if ( $fieldResult->ErrorMessage !== '' ) {
					$this->errors[] = $fieldResult->ErrorMessage;
				}
			}
			return;
		}

		// Read validated values and generate warning messages
		foreach ( $results['Fields'] as $fieldResult ) {
			switch ( $fieldResult->Field ) {
				case 'name':
					$this->gname = $fieldResult->FilteredValue;
					break;
				case 'theme':
					$this->theme = $fieldResult->FilteredValue;
					break;
				case 'usage':
					$this->usage = $fieldResult->FilteredValue;
					break;
				case 'dump-env':
					$this->showMeta = $fieldResult->FilteredValue;
					break;
				case 'dump-gv':
					$this->showDot = $fieldResult->FilteredValue;
					break;
			}
			if ( $fieldResult->WarningMessage !== '' ) {
				$this->warnings[] = $fieldResult->WarningMessage;
			}
		}

		// Pre-processing
		$this->md5_incoming = md5( json_encode( $props ) . $dot_syntax );
		$prefix = str_replace( [ '\\', '/', ' ' ], '_', $parser->getTitle()->getFullText() );
		$fn = FileSystemUtils::getSafeName( sprintf( '%s-%s', $prefix, $this->gname ) );
		$this->svg_file = sprintf( '%s/images/quickgv/%s.svg', MW_INSTALL_PATH, $fn );
		$this->svg_uri  = sprintf( '%s/images/quickgv/%s.svg', $wgScriptPath, $fn );
		$this->summary_file = sprintf( '%s/images/quickgv/%s.json', MW_INSTALL_PATH, $fn );

		// Cache operations
		$begin = microtime( true );
		if ( ExtensionConstants::ENABLE_SVG_CACHE ) {
			$summary = FileSystemUtils::loadSummary( $this->summary_file );
			$this->md5_existed = $summary['md5'];
			if ( $this->md5_incoming === $this->md5_existed ) {
				$this->hitCache = true;
				$this->successful = true;
				$this->svg_mtime = filemtime( $this->svg_file );
			}
		}

		// SVG generation
		if ( !$this->hitCache ) {
			$this->genSvg();
		}
		$this->elapsed = microtime( true ) - $begin;
	}

	/**
	 * Output HTML
	 *
	 * @return string
	 */
	public function genFullHtml() {
		// Display main content
		// - Conversion successful: warning messages + SVG
		// - Conversion failed: error messages
		if ( $this->successful ) {
			$html = $this->genHtmlOfWarnings();
			FileSystemUtils::dumpDebugFile( 'html-warnings.txt', $html );
			$html .= sprintf(
				'<img src="%s?t=%d" style="%s" />',
				$this->svg_uri,
				$this->svg_mtime,
				implode( '', ExtensionConstants::IMG_STYLES )
			);
		} else {
			$html = $this->genHtmlOfErrors();
			FileSystemUtils::dumpDebugFile( 'html-errors.txt', $html );
		}

		// Display environment info
		if ( $this->showMeta ) {
			$html .= $this->genHtmlOfEnv();
		}

		// Display source code
		if ( $this->showDot ) {
			$html .= $this->genHtmlOfSyntax();
		}

		return $html;
	}

	/**
	 * Generate SVG diagram
	 *
	 * - step 1: Execute php to compose dot syntax
	 * - step 2: Execute dot to convert syntax to SVG
	 * - step 3: Post-process SVG (links, colors, footer)
	 * - Any issues during the process are written to $this->errors[]
	 */
	private function genSvg() {
		$begin = microtime( true );

		// Execute php to generate dot syntax
		$arguments = [
			self::$php_cmd,
			sprintf( '%s/../templates/gv-builder.php', __DIR__ ),
			$this->gname,
			$this->theme,
			$this->usage
		];
		$retval = Command::pipeExec( $arguments, $this->dot_syntax, $done_syntax, $err );
		if ( $retval !== 0 ) {
			$this->errors[] = 'Cannot compose dot syntax.';
			$this->errors[] = $err;
			return;
		}

		// Write raw syntax debug file
		FileSystemUtils::dumpDebugFile( 'source.dot', $this->dot_syntax );

		// Write merged syntax debug file
		FileSystemUtils::dumpDebugFile( 'merged.dot', $done_syntax );

		// Execute dot to produce SVG file
		$arguments = [
			self::$dot_cmd,
			'-Tsvg',
			'-o', $this->svg_file
		];
		$retval = Command::pipeExec( $arguments, $done_syntax, $out, $err );

		if ( $retval !== 0 ) {
			$this->errors[] = sprintf( 'Cannot convert SVG by dot. (retval=%d)', $retval );
			$this->errors[] = sprintf( 'Shell command: %s', implode( ' ', $arguments ) );
			$this->errors[] = $err;
			return;
		}

		// Post-process SVG (Graphviz cannot handle these parts)
		$this->postProcessSvg();

		// Write summary file on success
		$summary = [
			'md5'     => $this->md5_incoming,
			'elapsed' => microtime( true ) - $begin,
		];
		FileSystemUtils::saveSummary( $this->summary_file, $summary );

		// Write dot execution result debug file
		FileSystemUtils::dumpDebugFile( 'dot-err.txt', $err );

		$this->svg_mtime = filemtime( $this->svg_file );
		$this->successful = true;
	}

	/**
	 * Post-process the generated SVG file
	 *
	 * - Add target="_blank" to all links
	 * - Color link text
	 * - Add "powered by QuickGV" footer
	 */
	private function postProcessSvg() {
		$svgxml = simplexml_load_file( $this->svg_file );
		if ( $svgxml === false ) {
			$this->errors[] = 'Cannot parse generated SVG file.';
			return;
		}

		$svgxml->registerXPathNamespace( 'svg', 'http://www.w3.org/2000/svg' );

		// Add target="_blank" to all links
		$links = $svgxml->xpath( '//svg:a' );
		foreach ( $links as $link ) {
			$link->addAttribute( 'target', '_blank' );
		}

		// Color link text
		$txtns = $svgxml->xpath( '//svg:a/svg:text' );
		foreach ( $txtns as $txt ) {
			$txt->attributes()->fill = '#0000ff';
		}

		// Add "powered by QuickGV" footer
		$w = (int)$svgxml->attributes()->width;
		$h = (int)$svgxml->attributes()->height;
		if ( $w > 200 && $h > 50 ) {
			$footer = $svgxml->addChild( 'svg:text' );
			$footer->addAttribute( 'text-anchor', 'end' );
			$footer->addAttribute( 'x', '100%' );
			$footer->addAttribute( 'y', '100%' );
			$footer->addAttribute( 'transform', 'translate(-5,-5)' );
			$footer->addAttribute( 'font-family', 'Times,serif' );
			$footer->addAttribute( 'font-size', '10' );

			$ftlink = $footer->addChild( 'svg:a', 'powered by QuickGV' );
			$ftlink->addAttribute( 'xlink:href', 'https://www.mediawiki.org/wiki/Extension:QuickGV', 'http://www.w3.org/1999/xlink' );
			$ftlink->addAttribute( 'target', '_blank' );
			$ftlink->addAttribute( 'fill', '#aaaaaa' );
		}

		file_put_contents( $this->svg_file, $svgxml->asXML() );
	}

	/**
	 * Generate system info table, used when dump-env="true"
	 *
	 * @return string HTML source code of the system info table
	 */
	private function genHtmlOfEnv() {
		// Conversion time obtained from summary file
		$summary = FileSystemUtils::loadSummary( $this->summary_file );
		$elapsed_conv = sprintf( '%.2f %s', $summary['elapsed'], wfMessage( 'quickgv-sec' )->plain() );

		// Data format processing
		$size = FileSystemUtils::getFriendlySize( $this->svg_file );
		$elapsed_load = sprintf( '%.2f %s', $this->elapsed, wfMessage( 'quickgv-sec' )->plain() );
		$syntax_ref = 'http://www.graphviz.org/doc/info/attrs.html';
		$about = sprintf(
			'%s - <a href="https://www.mediawiki.org/wiki/Extension:QuickGV">%s</a>',
			self::$version,
			wfMessage( 'quickgv-about' )->plain()
		);

		$mtime_str = 'N/A';
		if ( $this->svg_mtime > 0 ) {
			$time_diff = microtime( true ) - $this->svg_mtime;
			$mtime_str = ( $time_diff > 86400 ) ? date( 'Y-m-d', $this->svg_mtime ) : date( 'H:i:s', $this->svg_mtime );
		}

		// Table data
		$table_data = [
			[ 'label' => 'file-path', 'data' => $this->svg_uri ],
			[ 'label' => 'file-size', 'data' => $size ],
			[ 'label' => 'file-mtime', 'data' => $mtime_str ],
			[ 'label' => 'time-load', 'data' => $elapsed_load ],
			[ 'label' => 'time-conv', 'data' => $elapsed_conv ],
		];

		if ( $this->hitCache ) {
			$table_data[] = [ 'label' => 'cache-detected', 'data' => 'Yes' ];
		} else {
			$table_data[] = [ 'label' => 'cache-detected', 'data' => 'No' ];
			$table_data[] = [ 'label' => 'md5-incoming', 'data' => $this->md5_incoming ];
			$table_data[] = [ 'label' => 'md5-cached', 'data' => $this->md5_existed ];
		}

		$table_data[] = [ 'label' => 'graphviz-path', 'data' => self::$dot_cmd ];
		$table_data[] = [ 'label' => 'graphviz-ver', 'data' => self::$gvz_ver ];
		$table_data[] = [ 'label' => 'graphviz-ref', 'data' => $syntax_ref ];
		$table_data[] = [ 'label' => 'quickgv-ver', 'data' => $about ];

		// Generate each row
		$th_style = 'white-space:nowrap;';
		$td_style = 'text-align:left;';
		$trs = [];
		foreach ( $table_data as $entry ) {
			$trs[] = sprintf(
				'<tr><th style="%s">%s</th><td style="%s">%s</td></tr>',
				$th_style, wfMessage( $entry['label'] )->plain(),
				$td_style, htmlspecialchars( $entry['data'], ENT_QUOTES, 'UTF-8' )
			);
		}

		// Generate table
		$table_html = sprintf(
			'<table class="wikitable" style="width:600px; margin:5px 0 0 0;"><tbody>%s</tbody></table>',
			implode( "\n", $trs )
		);
		return $table_html;
	}

	/**
	 * Generate syntax display message, shows the complete dot syntax
	 *
	 * @return string HTML source code of the syntax display message
	 */
	private function genHtmlOfSyntax() {
		$trimmed = trim( $this->dot_syntax );
		if ( $trimmed !== '' ) {
			$wikiText = sprintf( '<syntaxhighlight lang="text" line>%s</syntaxhighlight>', $trimmed );
			return $this->parser->recursiveTagParseFully( $wikiText );
		} else {
			return '';
		}
	}

	/**
	 * Generate error messages, only appears when generation fails
	 *
	 * @return string HTML source code of error/warning messages
	 */
	private function genHtmlOfErrors() {
		$errors = array_merge( self::$sys_errors, $this->errors );
		return self::genHtmlOfLogs( 'error', $errors );
	}

	/**
	 * Generate warning messages, only appears above the SVG when generation succeeds
	 *
	 * @return string HTML source code of error/warning messages
	 */
	private function genHtmlOfWarnings() {
		return self::genHtmlOfLogs( 'warning', $this->warnings );
	}

	/**
	 * Shared part for generating error/warning messages
	 *
	 * @param string $level error | warning
	 * @param array $logs Error or warning messages
	 * @return string HTML source code of error/warning messages
	 */
	private static function genHtmlOfLogs( $level, $logs ) {
		if ( count( $logs ) === 0 ) {
			return '';
		}
		$classes = [
			"mw-message-box-$level",
			'mw-message-box'
		];
		return sprintf(
			'<pre class="%s" style="%s">%s</pre>',
			implode( ' ', $classes ),
			implode( ' ', ExtensionConstants::LOGGING_STYLES ),
			htmlspecialchars( implode( "\n", $logs ) )
		);
	}

}
