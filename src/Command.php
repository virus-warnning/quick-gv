<?php
namespace MediaWiki\Extension\QuickGV;

use MediaWiki\MediaWikiServices;

class Command {

	/**
	 * Create a boxed shell command with common security configuration
	 *
	 * @param array $arguments Command and arguments
	 * @return mixed Configured command instance
	 */
	private static function createCommand( array $arguments ) {
		$routeName = sprintf( 'quickgv-%s', basename( $arguments[0] ) );
		return MediaWikiServices::getInstance()
			->getShellCommandFactory()
			->createBoxed( 'quickgv' )
			->disableNetwork()
			->firejailDefaultSeccomp()
			->routeName( $routeName )
			->params( $arguments );
	}

	/**
	 * Execute a program within ShellBox
	 *
	 * See:
	 * - https://www.mediawiki.org/wiki/Manual:BoxedCommand
	 * - https://doc.wikimedia.org/mediawiki-core/master/php/classMediaWiki_1_1MediaWikiServices.html
	 * - https://doc.wikimedia.org/mediawiki-core/master/php/classMediaWiki_1_1Shell_1_1ShellCommandFactory.html
	 *
	 * @param string ...$arguments Command and arguments
	 * @return \MediaWiki\Shell\Result Execution result
	 */
	public static function execute( ...$arguments ) {
		return self::createCommand( $arguments )->execute();
	}

	/**
	 * Execute a program via shell
	 *
	 * @param array $arguments Shell command and arguments
	 * @param string $stdin Content to pipe as stdin
	 * @param string &$stdout Command stdout content
	 * @param string &$stderr Command stderr content
	 * @param string $encoding Text encoding of stdout/stderr, auto-detect by default
	 * @return int Exit code, 0 indicates successful termination
	 */
	public static function pipeExec( array $arguments, $stdin = '', &$stdout = '', &$stderr = '', $encoding = 'sys' ) {
		FileSystemUtils::dumpDebugFile( 'stdin.txt', $stdin );
		$result = self::createCommand( $arguments )
			->stdin( $stdin )
			->execute();
		$stdout = $result->getStdout();
		$stderr = $result->getStderr();
		return $result->getExitCode();
	}

	/**
	 * Search for the full path of an executable
	 * - Only called during init()
	 * - Searches directories listed in the PATH environment variable
	 *
	 * @param string $exec_name Executable name
	 * @return string Full path to the executable, or empty string if not found
	 */
	public static function findExecutable( $exec_name ) {
		$exec_path = '';

		// Search all directories in $PATH
		$path_dirs = explode( PATH_SEPARATOR, getenv( 'PATH' ) );
		foreach ( $path_dirs as $dir ) {
			$p = $dir . '/' . $exec_name;
			if ( file_exists( $p ) && is_executable( $p ) ) {
				$exec_path = $p;
				break;
			}
		}

		if ( $exec_path === '' ) {
			$user = function_exists( 'posix_getpwuid' )
				? ( posix_getpwuid( posix_geteuid() )['name'] ?? 'unknown' )
				: get_current_user();
			Hook::$sys_errors[] = sprintf( '%s not found. (user: %s)', $exec_name, $user );
			return '';
		}

		return $exec_path;
	}

}
