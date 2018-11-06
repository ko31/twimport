<?php
/*
Plugin Name: twimport
Plugin URI: https://github.com/ko31/twimport
Description: This is a plugin to import tweets.
Author: ko31
Version: 0.1
Author URI: https://go-sign.info
*/

if ( defined( 'WP_CLI' ) && WP_CLI ) {

	/**
	 * Import tweets from a file
	 *
	 * ## OPTIONS
	 *
	 * [<file>]
	 * : File path of tweet URL list
	 *
	 * ## EXAMPLES
	 *
	 *     $ wp twimport /path/to/tweet.txt
	 */
	function twimport_command( $args ) {

		if ( ! empty( $args[0] ) ) {
			$import_file = $args[0];
		} else {
			WP_CLI::error( 'Import file missing' );
		}

		if ( ! is_readable( $import_file ) ) {
			WP_CLI::error( sprintf( 'Import file not readable: %s', $import_file ) );
		}

		$i  = 0;
		$fp = fopen( $import_file, 'r' );
		while ( $line = fgets( $fp ) ) {
			$url  = trim( $line );
			$path = parse_url( $url );
			if ( empty( $path['path'] ) ) {
				continue;
			}
			$paths = explode( '/', $path['path'] );
			if ( empty( $paths[1] ) || empty( $paths[3] ) ) {
				continue;
			}
			$user_id  = $paths[1];
			$tweet_id = $paths[3];

			$options = array(
				'return'     => true,   // Return 'STDOUT'; use 'all' for full object.
				'launch'     => true,  // Reuse the current process.
				'exit_error' => true,   // Halt script execution on error.
			);

			$lists = WP_CLI::runcommand( "post list --title={$tweet_id} --field=ID --porcelain", $options );
			if ( ! empty( $lists ) ) {
				WP_CLI::warning( $url . " is already registered" );
				continue;
			}

			$post_id = WP_CLI::runcommand(
				"post create --post_type=work --post_status=draft --post_title='{$tweet_id}' --meta_input='{\"tweet\":\"" . $url . "\"}' --porcelain",
				$options
			);
			WP_CLI::runcommand( "post term set {$post_id} artist {$user_id}", $options );

			WP_CLI::line( $url );

			$i ++;
		}
		fclose( $fp );

		WP_CLI::success( $i . ' records were registered.' );
	}

	WP_CLI::add_command( 'twimport', 'twimport_command' );
}
