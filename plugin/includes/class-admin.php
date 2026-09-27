<?php
/**
 * Admin menu and settings screen.
 *
 * @package Aurelia_Commerce
 */

namespace Aurelia\Commerce;

defined( 'ABSPATH' ) || exit;

/**
 * Admin.
 */
class Admin {

	const CAP  = 'manage_woocommerce';
	const SLUG = 'aurelia-commerce';

	/**
	 * Hooks.
	 */
	public function init() {
		add_action( 'admin_menu', array( $this, 'menu' ), 9 );
		add_action( 'admin_menu', array( $this, 'settings_menu' ), 50 );
		add_action( 'admin_init', array( $this, 'register_setting' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'assets' ) );
		add_filter( 'plugin_action_links_' . plugin_basename( AURELIA_COMMERCE_FILE ), array( $this, 'action_links' ) );
		add_action( 'rest_api_init', array( $this, 'routes' ) );
	}

	/**
	 * Top-level menu. The first submenu (dashboard) is added by Analytics.
	 */
	public function menu() {
		$icon = 'data:image/svg+xml;base64,' . base64_encode( '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><path fill="#a7aaad" d="M10 1.5 12.3 7h5.9l-4.8 3.6 1.8 5.9L10 13l-5.2 3.5 1.8-5.9L1.8 7h5.9z"/></svg>' ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode -- inline SVG menu icon.
		add_menu_page( __( 'Aurelia Commerce', 'aurelia-commerce' ), __( 'Aurelia', 'aurelia-commerce' ), self::CAP, self::SLUG, '__return_null', $icon, 56 );
	}

	/**
	 * Settings submenu (added late so it appears last).
	 */
	public function settings_menu() {
		add_submenu_page( self::SLUG, __( 'Aurelia settings', 'aurelia-commerce' ), __( 'Settings', 'aurelia-commerce' ), self::CAP, 'aurelia-settings', array( $this, 'render_settings' ) );
	}

	/**
	 * Settings API registration.
	 */
	public function register_setting() {
		register_setting(
			'aurelia_commerce',
			Settings::OPTION,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( Settings::class, 'sanitize' ),
				'show_in_rest'      => false,
			)
		);
	}

	/**
	 * Admin assets on Aurelia screens.
	 *
	 * @param string $hook Screen hook.
	 */
	public function assets( $hook ) {
		wp_register_style( 'aurelia-admin', AURELIA_COMMERCE_URL . 'assets/css/admin.css', array(), AURELIA_COMMERCE_VERSION );
		if ( str_contains( (string) $hook, 'aurelia' ) ) {
			wp_enqueue_style( 'aurelia-admin' );
		}
		if ( str_contains( (string) $hook, 'aurelia-settings' ) ) {
			wp_enqueue_style( 'wp-color-picker' );
			wp_enqueue_script( 'aurelia-settings', AURELIA_COMMERCE_URL . 'assets/js/admin-settings.js', array( 'wp-color-picker', 'wp-api-fetch' ), AURELIA_COMMERCE_VERSION, true );
			wp_localize_script(
				'aurelia-settings',
				'aureliaSettings',
				array(
					'testing' => __( 'Testing…', 'aurelia-commerce' ),
					'ok'      => __( 'Connected ✓', 'aurelia-commerce' ),
					'failed'  => __( 'Failed: ', 'aurelia-commerce' ),
				)
			);
		}
	}

	/**
	 * Settings link on the Plugins screen.
	 *
	 * @param array $links Links.
	 * @return array
	 */
	public function action_links( $links ) {
		array_unshift(
			$links,
			'<a href="' . esc_url( admin_url( 'admin.php?page=aurelia-settings' ) ) . '">' . esc_html__( 'Settings', 'aurelia-commerce' ) . '</a>',
			'<a href="' . esc_url( admin_url( 'admin.php?page=aurelia-setup' ) ) . '">' . esc_html__( 'Setup wizard', 'aurelia-commerce' ) . '</a>'
		);
		return $links;
	}

	/**
	 * Admin-only REST routes.
	 */
	public function routes() {
		register_rest_route(
			'aurelia/v1',
			'/admin/test-ai',
			array(
				'methods'             => 'POST',
				'permission_callback' => static fn() => current_user_can( self::CAP ),
				'callback'            => static function () {
					$result = Claude_Client::create(
						array(
							'max_tokens' => 20,
							'messages'   => array(
								array(
									'role'    => 'user',
									'content' => 'Reply with the single word OK.',
								),
							),
						)
					);
					if ( is_wp_error( $result ) ) {
						return new \WP_REST_Response( array( 'ok' => false, 'message' => $result->get_error_message() ), 200 );
					}
					return array(
						'ok'    => true,
						'model' => $result['model'] ?? '',
					);
				},
			)
		);
	}

	/**
	 * Render the settings screen.
	 */
	public function render_settings() {
		if ( ! current_user_can( self::CAP ) ) {
			return;
		}
		$schema = Settings::schema();
		$tab    = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : 'general'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only tab switch.
		if ( ! isset( $schema[ $tab ] ) ) {
			$tab = 'general';
		}
		$values = Settings::all();
		?>
		<div class="wrap aurelia-admin">
			<h1><?php esc_html_e( 'Aurelia settings', 'aurelia-commerce' ); ?></h1>
			<nav class="nav-tab-wrapper" aria-label="<?php esc_attr_e( 'Settings sections', 'aurelia-commerce' ); ?>">
				<?php foreach ( $schema as $key => $section ) : ?>
					<a class="nav-tab <?php echo $key === $tab ? 'nav-tab-active' : ''; ?>" href="<?php echo esc_url( add_query_arg( array( 'page' => 'aurelia-settings', 'tab' => $key ), admin_url( 'admin.php' ) ) ); ?>" <?php echo $key === $tab ? 'aria-current="page"' : ''; ?>><?php echo esc_html( $section['label'] ); ?></a>
				<?php endforeach; ?>
				<a class="nav-tab" href="<?php echo esc_url( admin_url( 'admin.php?page=wc-settings&tab=checkout&section=aurelia_upi' ) ); ?>"><?php esc_html_e( 'UPI payments ↗', 'aurelia-commerce' ); ?></a>
			</nav>
			<?php settings_errors(); ?>
			<form method="post" action="options.php" class="aurelia-settings-form">
				<?php settings_fields( 'aurelia_commerce' ); ?>
				<input type="hidden" name="<?php echo esc_attr( Settings::OPTION ); ?>[_tab]" value="<?php echo esc_attr( $tab ); ?>">
				<table class="form-table" role="presentation">
					<?php foreach ( $schema[ $tab ]['fields'] as $key => $field ) : ?>
						<tr>
							<th scope="row"><label for="aurelia-<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $field['label'] ); ?></label></th>
							<td>
								<?php $this->field( $key, $field, $values[ $key ] ?? $field['default'] ); ?>
								<?php if ( ! empty( $field['description'] ) ) : ?>
									<p class="description" id="aurelia-<?php echo esc_attr( $key ); ?>-desc"><?php echo esc_html( $field['description'] ); ?></p>
								<?php endif; ?>
							</td>
						</tr>
					<?php endforeach; ?>
				</table>
				<?php if ( 'social' === $tab ) : ?>
					<div class="aurelia-callout">
						<h2><?php esc_html_e( 'Reliable scheduling with a real cron job', 'aurelia-commerce' ); ?></h2>
						<p><?php esc_html_e( 'WP-Cron only runs when someone visits your site. For posts to go out exactly on time, disable it and call WordPress from your server every five minutes:', 'aurelia-commerce' ); ?></p>
						<p><code>define( 'DISABLE_WP_CRON', true );</code> <?php esc_html_e( '(in wp-config.php)', 'aurelia-commerce' ); ?></p>
						<p><code>*/5 * * * * curl -s <?php echo esc_html( site_url( 'wp-cron.php?doing_wp_cron' ) ); ?> &gt; /dev/null</code></p>
						<p><?php esc_html_e( 'Or with WP-CLI:', 'aurelia-commerce' ); ?> <code>*/5 * * * * wp cron event run --due-now --path=<?php echo esc_html( untrailingslashit( ABSPATH ) ); ?></code></p>
					</div>
				<?php endif; ?>
				<?php submit_button(); ?>
			</form>
		</div>
		<?php
	}

	/**
	 * Render one field control.
	 *
	 * @param string $key   Key.
	 * @param array  $field Definition.
	 * @param mixed  $value Current value.
	 */
	private function field( $key, $field, $value ) {
		$name = Settings::OPTION . '[' . $key . ']';
		$id   = 'aurelia-' . $key;
		$desc = ! empty( $field['description'] ) ? ' aria-describedby="' . esc_attr( $id . '-desc' ) . '"' : '';
		switch ( $field['type'] ) {
			case 'checkbox':
				printf( '<input type="checkbox" id="%1$s" name="%2$s" value="1" %3$s%4$s>', esc_attr( $id ), esc_attr( $name ), checked( ! empty( $value ), true, false ), $desc ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- $desc is escaped above.
				break;
			case 'select':
				printf( '<select id="%1$s" name="%2$s"%3$s>', esc_attr( $id ), esc_attr( $name ), $desc ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- $desc is escaped above.
				foreach ( $field['options'] as $opt => $label ) {
					printf( '<option value="%1$s" %2$s>%3$s</option>', esc_attr( $opt ), selected( $value, $opt, false ), esc_html( $label ) );
				}
				echo '</select>';
				break;
			case 'textarea':
				printf( '<textarea id="%1$s" name="%2$s" rows="5" class="large-text"%3$s>%4$s</textarea>', esc_attr( $id ), esc_attr( $name ), $desc, esc_textarea( (string) $value ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- $desc is escaped above.
				break;
			case 'html':
				wp_editor(
					(string) $value,
					$id,
					array(
						'textarea_name' => $name,
						'textarea_rows' => 8,
						'media_buttons' => true,
					)
				);
				break;
			case 'number':
				printf(
					'<input type="number" id="%1$s" name="%2$s" value="%3$s" class="small-text" min="%4$s" max="%5$s" step="any"%6$s>',
					esc_attr( $id ),
					esc_attr( $name ),
					esc_attr( (string) $value ),
					esc_attr( (string) ( $field['min'] ?? '' ) ),
					esc_attr( (string) ( $field['max'] ?? '' ) ),
					$desc // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped above.
				);
				break;
			case 'color':
				printf( '<input type="text" id="%1$s" name="%2$s" value="%3$s" class="aurelia-color" data-default-color="%4$s">', esc_attr( $id ), esc_attr( $name ), esc_attr( (string) $value ), esc_attr( $field['default'] ) );
				break;
			case 'secret':
				$saved = '' !== Settings::secret( $key );
				printf(
					'<input type="password" id="%1$s" name="%2$s" value="" class="regular-text" autocomplete="new-password" placeholder="%3$s"%4$s>',
					esc_attr( $id ),
					esc_attr( $name ),
					esc_attr( $saved ? __( 'Saved — leave blank to keep', 'aurelia-commerce' ) : '' ),
					$desc // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped above.
				);
				if ( $saved ) {
					printf( ' <label><input type="checkbox" name="%1$s" value="1"> %2$s</label>', esc_attr( Settings::OPTION . '[' . $key . '_clear]' ), esc_html__( 'Remove saved value', 'aurelia-commerce' ) );
				}
				if ( 'anthropic_key' === $key ) {
					printf( ' <button type="button" class="button aurelia-test-ai">%s</button> <span class="aurelia-test-ai-result" role="status"></span>', esc_html__( 'Test connection', 'aurelia-commerce' ) );
				}
				break;
			default:
				$type = in_array( $field['type'], array( 'email', 'url' ), true ) ? $field['type'] : 'text';
				printf( '<input type="%1$s" id="%2$s" name="%3$s" value="%4$s" class="regular-text"%5$s>', esc_attr( $type ), esc_attr( $id ), esc_attr( $name ), esc_attr( (string) $value ), $desc ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- $desc is escaped above.
		}
	}
}
