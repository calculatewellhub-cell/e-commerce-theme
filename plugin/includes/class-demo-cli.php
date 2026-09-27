<?php
/**
 * WP-CLI: wp aurelia demo import|remove
 *
 * @package Aurelia_Commerce
 */

namespace Aurelia\Commerce;

defined( 'ABSPATH' ) || exit;

/**
 * Manage Aurelia demo content.
 */
class Demo_Cli {

	/**
	 * Import the demo store (products, pages, menu, settings).
	 *
	 * ## EXAMPLES
	 *
	 *     wp aurelia demo import --user=admin
	 *
	 * @param array $args       Args.
	 * @param array $assoc_args Assoc args.
	 */
	public function import( $args, $assoc_args ) {
		$importer = new Demo_Importer();
		foreach ( Demo_Importer::steps() as $step ) {
			\WP_CLI::log( $step . ': ' . $importer->run( $step ) );
		}
		flush_rewrite_rules( false );
		\WP_CLI::success( 'Demo store imported.' );
	}

	/**
	 * Remove everything the demo import created.
	 *
	 * @param array $args       Args.
	 * @param array $assoc_args Assoc args.
	 */
	public function remove( $args, $assoc_args ) {
		\WP_CLI::success( ( new Demo_Importer() )->remove() );
	}
}
