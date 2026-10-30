<?php
namespace MediaWiki\Extension\QuickGV;

class ExtensionConstants {

	/* Whether to enable debug process files */
	public const DUMP_DEBUG_FILES = true;

	/* Whether to enable SVG cache */
	public const ENABLE_SVG_CACHE = true;

	/* <embed> element styles for inserted SVG */
	public const IMG_STYLES = [
		'width: min-content;',
		'height: auto;',
		'border: 1px solid #aaa;',
		'border-radius: 5px;',
		'padding: 5px;',
	];

	/* <pre> element styles for warning and error messages */
	public const LOGGING_STYLES = [
		'display: inline-block;',
		'margin: 0;',
		'overflow: scroll;',
		'max-width: 60%;',
		'max-height: 250px;',
		'white-space: pre;',
		'border-radius: 5px;',
		'padding: 10px 15px;',
	];

}
