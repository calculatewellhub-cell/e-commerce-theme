<?php
/**
 * Variation swatches: colour, image and button attribute types.
 *
 * Store owners pick the type per attribute (Products → Attributes) and set a
 * colour or image per term. product.js turns the variation <select>s into
 * accessible radio-style swatches while keeping WooCommerce's own logic.
 *
 * @package Aurelia_Commerce
 */

namespace Aurelia\Commerce;

defined( 'ABSPATH' ) || exit;

/**
 * Swatches.
 */
class Swatches {

	/**
	 * Hooks.
	 */
	public function init() {
		add_filter( 'product_attributes_type_selector', array( $this, 'types' ) );
		add_action( 'admin_init', array( $this, 'term_hooks' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_assets' ) );
	}

	/**
	 * Attribute types.
	 *
	 * @param array $types Types.
	 * @return array
	 */
	public function types( $types ) {
		return array_merge(
			$types,
			array(
				'color'  => __( 'Colour swatch', 'aurelia-commerce' ),
				'image'  => __( 'Image swatch', 'aurelia-commerce' ),
				'button' => __( 'Button', 'aurelia-commerce' ),
			)
		);
	}

	/**
	 * Term fields for every product attribute taxonomy.
	 */
	public function term_hooks() {
		foreach ( wc_get_attribute_taxonomies() as $tax ) {
			$taxonomy = wc_attribute_taxonomy_name( $tax->attribute_name );
			add_action( $taxonomy . '_add_form_fields', array( $this, 'add_fields' ) );
			add_action( $taxonomy . '_edit_form_fields', array( $this, 'edit_fields' ) );
			add_action( 'created_' . $taxonomy, array( $this, 'save' ) );
			add_action( 'edited_' . $taxonomy, array( $this, 'save' ) );
		}
	}

	/**
	 * Media picker + colour picker on term screens.
	 *
	 * @param string $hook Hook.
	 */
	public function admin_assets( $hook ) {
		if ( in_array( $hook, array( 'edit-tags.php', 'term.php' ), true ) && isset( $_GET['taxonomy'] ) && str_starts_with( sanitize_key( wp_unslash( $_GET['taxonomy'] ) ), 'pa_' ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- screen detection only.
			wp_enqueue_media();
			wp_enqueue_style( 'wp-color-picker' );
			wp_enqueue_script( 'aurelia-swatch-admin', AURELIA_COMMERCE_URL . 'assets/js/admin-swatches.js', array( 'wp-color-picker' ), AURELIA_COMMERCE_VERSION, true );
		}
	}

	/**
	 * Add-term fields.
	 */
	public function add_fields() {
		wp_nonce_field( 'aurelia_swatch', 'aurelia_swatch_nonce' );
		?>
		<div class="form-field">
			<label for="aurelia-swatch-color"><?php esc_html_e( 'Swatch colour', 'aurelia-commerce' ); ?></label>
			<input type="text" id="aurelia-swatch-color" name="aurelia_swatch_color" class="aurelia-color" value="">
		</div>
		<div class="form-field">
			<label for="aurelia-swatch-image"><?php esc_html_e( 'Swatch image', 'aurelia-commerce' ); ?></label>
			<input type="hidden" id="aurelia-swatch-image" name="aurelia_swatch_image" value="">
			<button type="button" class="button aurelia-swatch-pick"><?php esc_html_e( 'Choose image', 'aurelia-commerce' ); ?></button>
			<span class="aurelia-swatch-preview"></span>
		</div>
		<?php
	}

	/**
	 * Edit-term fields.
	 *
	 * @param \WP_Term $term Term.
	 */
	public function edit_fields( $term ) {
		$color = (string) get_term_meta( $term->term_id, 'aurelia_swatch_color', true );
		$image = absint( get_term_meta( $term->term_id, 'aurelia_swatch_image', true ) );
		wp_nonce_field( 'aurelia_swatch', 'aurelia_swatch_nonce' );
		?>
		<tr class="form-field">
			<th scope="row"><label for="aurelia-swatch-color"><?php esc_html_e( 'Swatch colour', 'aurelia-commerce' ); ?></label></th>
			<td><input type="text" id="aurelia-swatch-color" name="aurelia_swatch_color" class="aurelia-color" value="<?php echo esc_attr( $color ); ?>"></td>
		</tr>
		<tr class="form-field">
			<th scope="row"><label for="aurelia-swatch-image"><?php esc_html_e( 'Swatch image', 'aurelia-commerce' ); ?></label></th>
			<td>
				<input type="hidden" id="aurelia-swatch-image" name="aurelia_swatch_image" value="<?php echo esc_attr( (string) $image ); ?>">
				<button type="button" class="button aurelia-swatch-pick"><?php esc_html_e( 'Choose image', 'aurelia-commerce' ); ?></button>
				<span class="aurelia-swatch-preview"><?php echo $image ? wp_get_attachment_image( $image, array( 40, 40 ) ) : ''; ?></span>
			</td>
		</tr>
		<?php
	}

	/**
	 * Save term meta.
	 *
	 * @param int $term_id Term ID.
	 */
	public function save( $term_id ) {
		if ( ! isset( $_POST['aurelia_swatch_nonce'] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['aurelia_swatch_nonce'] ) ), 'aurelia_swatch' ) || ! current_user_can( 'manage_product_terms' ) ) {
			return;
		}
		$color = sanitize_hex_color( wp_unslash( $_POST['aurelia_swatch_color'] ?? '' ) );
		update_term_meta( $term_id, 'aurelia_swatch_color', $color ? $color : '' );
		update_term_meta( $term_id, 'aurelia_swatch_image', absint( $_POST['aurelia_swatch_image'] ?? 0 ) );
	}

	/**
	 * Swatch data for a variable product: attribute => {type, options{slug => {name,color,image}}}.
	 *
	 * @param int $product_id Product ID.
	 * @return array|null
	 */
	public static function data_for( $product_id ) {
		$product = wc_get_product( $product_id );
		if ( ! $product || ! $product->is_type( 'variable' ) ) {
			return null;
		}
		$types = array();
		foreach ( wc_get_attribute_taxonomies() as $tax ) {
			$types[ wc_attribute_taxonomy_name( $tax->attribute_name ) ] = $tax->attribute_type;
		}
		$out = array();
		foreach ( $product->get_variation_attributes() as $attribute => $values ) {
			$type = $types[ $attribute ] ?? 'button';
			if ( 'select' === $type ) {
				$type = 'button';
			}
			$options = array();
			if ( taxonomy_exists( $attribute ) ) {
				foreach ( wc_get_product_terms( $product_id, $attribute, array( 'fields' => 'all' ) ) as $term ) {
					$image                   = absint( get_term_meta( $term->term_id, 'aurelia_swatch_image', true ) );
					$options[ $term->slug ] = array(
						'name'  => $term->name,
						'color' => (string) get_term_meta( $term->term_id, 'aurelia_swatch_color', true ),
						'image' => $image ? (string) wp_get_attachment_image_url( $image, 'thumbnail' ) : '',
					);
				}
			} else {
				foreach ( $values as $value ) {
					$options[ $value ] = array(
						'name'  => $value,
						'color' => '',
						'image' => '',
					);
				}
			}
			// A colour attribute without colours set falls back to buttons.
			if ( 'color' === $type && ! array_filter( array_column( $options, 'color' ) ) ) {
				$type = 'button';
			}
			$out[ 'attribute_' . sanitize_title( $attribute ) ] = array(
				'type'    => $type,
				'options' => $options,
			);
		}
		return $out;
	}
}
