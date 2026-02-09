<?php
/**
 * Setup admin menus.
 *
 * @since 1.0.0
 * @package Advertising
 * @author AyeCode Ltd
 */

defined( 'ABSPATH' ) || exit;
/**
 * Adv_Admin_Menus class.
 */
class Adv_Admin_Menus {

	/**
	 * Class constructor.
	 */
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'admin_menu' ), 10 );
		add_action( 'admin_menu', array( $this, 'settings_menu' ), 18 );
        add_action( 'admin_head', array( $this, 'set_admin_menu_class' ) );
	}

	/**
	 * Register main menu.
	 */
	public function admin_menu() {

        add_menu_page(
            __( 'Advertising', 'advertising' ),
            __( 'Advertising', 'advertising' ),
            'manage_options',
            'advertising',
            null,
            'dashicons-chart-line',
            '54.0123480'
        );

	}

	/**
	 * Register settings menu.
	 */
	public function settings_menu() {

		add_submenu_page(
            'advertising',
            __( 'Settings', 'advertising' ),
            __( 'Settings', 'advertising' ),
            'manage_options',
            'advertising-settings',
            array( $this, 'options_page' )
        );

	}

	/**
	 * Displays settings page.
	 */
	public function options_page() {

        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        ?>
        <div class="wrap">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
            <div id="tab_container">
                <form method="post" action="options.php">
                    <table class="form-table">
                        <?php
							settings_fields( 'adv_settings' );
							do_action( 'adv_settings_top' );
							do_settings_sections( 'adv_settings_main' );
							do_action( 'adv_settings_bottom' );
                        ?>
                    </table>
                    <?php submit_button(); ?>
                </form>
            </div><!-- #tab_container-->
        </div><!-- .wrap -->
        <?php
    }

	/**
	 * Register settings menu.
	 */
	public function set_admin_menu_class() {
		global $current_screen, $parent_file, $submenu_file;

        if ( ! empty( $current_screen->id ) && in_array( $current_screen->id , array( 'adv_ad', 'adv_zone' ) ) ) {
			$parent_file = 'advertising';
			$submenu_file = 'edit.php?post_type=' . $current_screen->id;
        }

    }

}

// Init the class.
new Adv_Admin_Menus();
