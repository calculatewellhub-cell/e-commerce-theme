<?php
/**
 * 3D product viewer (three.js): built-in ring model with configurable metal
 * and gem colours, or a custom GLB/GLTF model per product. Also animates the
 * theme's hero ".au-3d-stage".
 *
 * Performance: a ~2 KB loader module is printed only where needed; the three.js
 * bundle is fetched on demand (hero visible or "View in 3D" clicked).
 *
 * @package Aurelia_Commerce
 */

namespace Aurelia\Commerce;

defined( 'ABSPATH' ) || exit;

/**
 * Viewer.
 */
class Viewer {

	const META = '_aurelia_3d';

	/**
	 * Whether the loader has been queued for this request.
	 *
	 * @var bool
	 */
	private $queued = false;

	/**
	 * Stage config for the current page.
	 *
	 * @var array
	 */
	private $config = array();

	/**
	 * Hooks.
	 */
	public function init() {
		add_action( 'add_meta_boxes_product', array( $this, 'meta_box' ) );
		add_action( 'woocommerce_process_product_meta', array( $this, 'save' ) );
		add_filter( 'upload_mimes', array( $this, 'mimes' ) );
		add_filter( 'wp_check_filetype_and_ext', array( $this, 'check_filetype' ), 10, 3 );
		add_action( 'init', array( $this, 'register_module' ) );

		if ( Settings::on( 'viewer_hero' ) ) {
			add_filter( 'render_block_core/group', array( $this, 'detect_stage' ), 10, 2 );
		}
		if ( Settings::on( 'viewer_enabled' ) ) {
			add_action( 'wp', array( $this, 'maybe_product' ) );
		}
		add_action( 'wp_footer', array( $this, 'print_config' ), 5 );
	}

	/**
	 * Register the loader script module.
	 */
	public function register_module() {
		if ( function_exists( 'wp_register_script_module' ) ) {
			wp_register_script_module( 'aurelia-viewer-loader', AURELIA_COMMERCE_URL . 'assets/js/viewer-loader.js', array(), AURELIA_COMMERCE_VERSION );
		}
	}

	/**
	 * Queue the loader once.
	 */
	private function queue() {
		if ( $this->queued || ! function_exists( 'wp_enqueue_script_module' ) ) {
			return;
		}
		$this->queued = true;
		wp_enqueue_script_module( 'aurelia-viewer-loader' );
	}

	/**
	 * Enqueue when a rendered group is the theme's 3D stage.
	 *
	 * @param string $content Block HTML.
	 * @param array  $block   Parsed block.
	 * @return string
	 */
	public function detect_stage( $content, $block ) {
		if ( ! empty( $block['attrs']['className'] ) && str_contains( $block['attrs']['className'], 'au-3d-stage' ) ) {
			$this->config['hero'] = array(
				'metal' => (string) Settings::get( 'viewer_metal', '#e6c068' ),
				'gem'   => (string) Settings::get( 'viewer_gem', '#dff3ff' ),
			);
			$this->queue();
		}
		return $content;
	}

	/**
	 * Enqueue on products with a 3D view.
	 */
	public function maybe_product() {
		if ( ! is_singular( 'product' ) ) {
			return;
		}
		$data = self::product_config( get_queried_object_id() );
		if ( $data ) {
			$this->config['product'] = $data;
			$this->queue();
		}
	}

	/**
	 * Product 3D settings, or null.
	 *
	 * @param int $product_id Product ID.
	 * @return array|null
	 */
	public static function product_config( $product_id ) {
		$meta = get_post_meta( $product_id, self::META, true );
		if ( ! is_array( $meta ) || empty( $meta['mode'] ) || 'none' === $meta['mode'] ) {
			return null;
		}
		$config = array(
			'mode'  => 'model' === $meta['mode'] ? 'model' : 'ring',
			'metal' => sanitize_hex_color( $meta['metal'] ?? '' ) ? $meta['metal'] : (string) Settings::get( 'viewer_metal', '#e6c068' ),
			'gem'   => ! empty( $meta['no_gem'] ) ? null : ( sanitize_hex_color( $meta['gem'] ?? '' ) ? $meta['gem'] : (string) Settings::get( 'viewer_gem', '#dff3ff' ) ),
		);
		if ( 'model' === $config['mode'] ) {
			$config['model'] = esc_url_raw( (string) ( $meta['model'] ?? '' ) );
			if ( '' === $config['model'] ) {
				return null;
			}
		}
		return $config;
	}

	/**
	 * Print the JSON config consumed by the loader module.
	 */
	public function print_config() {
		if ( ! $this->queued ) {
			return;
		}
		$config = $this->config + array(
			'bundle' => AURELIA_COMMERCE_URL . 'assets/js/viewer/viewer.js?ver=' . AURELIA_COMMERCE_VERSION,
			'i18n'   => array(
				'view'    => __( 'View in 3D', 'aurelia-commerce' ),
				'close'   => __( 'Close 3D view', 'aurelia-commerce' ),
				'hint'    => __( 'Drag to rotate', 'aurelia-commerce' ),
				'label'   => __( 'Interactive 3D model of the product', 'aurelia-commerce' ),
				'loading' => __( 'Loading 3D model…', 'aurelia-commerce' ),
				'failed'  => __( 'The 3D view could not be loaded on this device.', 'aurelia-commerce' ),
			),
		);
		wp_print_inline_script_tag(
			wp_json_encode( $config ),
			array(
				'type' => 'application/json',
				'id'   => 'aurelia-3d-config',
			)
		);
	}

	/**
	 * Allow GLB/GLTF uploads for store managers.
	 *
	 * @param array $mimes Mimes.
	 * @return array
	 */
	public function mimes( $mimes ) {
		if ( current_user_can( 'edit_products' ) ) {
			$mimes['glb']  = 'model/gltf-binary';
			$mimes['gltf'] = 'model/gltf+json';
		}
		return $mimes;
	}

	/**
	 * The finfo check reports GLB as octet-stream; trust the extension for store managers.
	 *
	 * @param array  $data     Detected data.
	 * @param string $file     Path.
	 * @param string $filename Name.
	 * @return array
	 */
	public function check_filetype( $data, $file, $filename ) {
		if ( empty( $data['ext'] ) && current_user_can( 'edit_products' ) ) {
			$ext = strtolower( pathinfo( (string) $filename, PATHINFO_EXTENSION ) );
			if ( 'glb' === $ext || 'gltf' === $ext ) {
				$data['ext']  = $ext;
				$data['type'] = 'glb' === $ext ? 'model/gltf-binary' : 'model/gltf+json';
			}
		}
		return $data;
	}

	/**
	 * Product meta box.
	 */
	public function meta_box() {
		add_meta_box( 'aurelia-3d', __( '3D view', 'aurelia-commerce' ), array( $this, 'render_box' ), 'product', 'side' );
	}

	/**
	 * Render the meta box.
	 *
	 * @param \WP_Post $post Post.
	 */
	public function render_box( $post ) {
		$meta = wp_parse_args(
			(array) get_post_meta( $post->ID, self::META, true ),
			array(
				'mode'   => 'none',
				'metal'  => (string) Settings::get( 'viewer_metal', '#e6c068' ),
				'gem'    => (string) Settings::get( 'viewer_gem', '#dff3ff' ),
				'no_gem' => 0,
				'model'  => '',
			)
		);
		wp_nonce_field( 'aurelia_3d_save', 'aurelia_3d_nonce' );
		wp_enqueue_media();
		?>
		<p>
			<label for="aurelia-3d-mode"><?php esc_html_e( 'Show', 'aurelia-commerce' ); ?></label><br>
			<select id="aurelia-3d-mode" name="aurelia_3d[mode]" class="widefat">
				<option value="none" <?php selected( $meta['mode'], 'none' ); ?>><?php esc_html_e( 'No 3D view', 'aurelia-commerce' ); ?></option>
				<option value="ring" <?php selected( $meta['mode'], 'ring' ); ?>><?php esc_html_e( 'Built-in ring model', 'aurelia-commerce' ); ?></option>
				<option value="model" <?php selected( $meta['mode'], 'model' ); ?>><?php esc_html_e( 'My GLB / GLTF model', 'aurelia-commerce' ); ?></option>
			</select>
		</p>
		<p>
			<label for="aurelia-3d-metal"><?php esc_html_e( 'Metal colour', 'aurelia-commerce' ); ?></label>
			<input type="color" id="aurelia-3d-metal" name="aurelia_3d[metal]" value="<?php echo esc_attr( $meta['metal'] ); ?>">
			<label for="aurelia-3d-gem"><?php esc_html_e( 'Gem', 'aurelia-commerce' ); ?></label>
			<input type="color" id="aurelia-3d-gem" name="aurelia_3d[gem]" value="<?php echo esc_attr( $meta['gem'] ); ?>">
		</p>
		<p><label><input type="checkbox" name="aurelia_3d[no_gem]" value="1" <?php checked( ! empty( $meta['no_gem'] ) ); ?>> <?php esc_html_e( 'Plain band (no stone)', 'aurelia-commerce' ); ?></label></p>
		<p>
			<label for="aurelia-3d-model"><?php esc_html_e( 'Model file URL (.glb / .gltf)', 'aurelia-commerce' ); ?></label>
			<input type="url" id="aurelia-3d-model" name="aurelia_3d[model]" value="<?php echo esc_attr( $meta['model'] ); ?>" class="widefat">
			<button type="button" class="button aurelia-pick-model" data-target="aurelia-3d-model"><?php esc_html_e( 'Upload / choose model', 'aurelia-commerce' ); ?></button>
		</p>
		<script>
		document.querySelector( '.aurelia-pick-model' )?.addEventListener( 'click', ( e ) => {
			const frame = wp.media( { title: <?php echo wp_json_encode( __( 'Choose a 3D model', 'aurelia-commerce' ) ); ?>, multiple: false } );
			frame.on( 'select', () => { document.getElementById( e.target.dataset.target ).value = frame.state().get( 'selection' ).first().toJSON().url; } );
			frame.open();
		} );
		</script>
		<?php
	}

	/**
	 * Save the meta box.
	 *
	 * @param int $post_id Product ID.
	 */
	public function save( $post_id ) {
		if ( ! isset( $_POST['aurelia_3d_nonce'] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['aurelia_3d_nonce'] ) ), 'aurelia_3d_save' ) || ! current_user_can( 'edit_product', $post_id ) ) {
			return;
		}
		$raw  = isset( $_POST['aurelia_3d'] ) && is_array( $_POST['aurelia_3d'] ) ? wp_unslash( $_POST['aurelia_3d'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- each key sanitized below.
		$mode = isset( $raw['mode'] ) && in_array( $raw['mode'], array( 'none', 'ring', 'model' ), true ) ? $raw['mode'] : 'none';
		update_post_meta(
			$post_id,
			self::META,
			array(
				'mode'   => $mode,
				'metal'  => sanitize_hex_color( (string) ( $raw['metal'] ?? '' ) ) ?? '',
				'gem'    => sanitize_hex_color( (string) ( $raw['gem'] ?? '' ) ) ?? '',
				'no_gem' => empty( $raw['no_gem'] ) ? 0 : 1,
				'model'  => esc_url_raw( (string) ( $raw['model'] ?? '' ) ),
			)
		);
	}
}
