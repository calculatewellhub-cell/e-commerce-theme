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
	 */
	public function import() {
		$importer = new Demo_Importer();
		foreach ( Demo_Importer::steps() as $step ) {
			\WP_CLI::log( $step . ': ' . $importer->run( $step ) );
		}
		flush_rewrite_rules( false );
		\WP_CLI::success( 'Demo store imported.' );
	}

	/**
	 * Remove everything the demo import created.
	 */
	public function remove() {
		\WP_CLI::success( ( new Demo_Importer() )->remove() );
	}
}
