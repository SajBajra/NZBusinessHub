<?php
/**
 * Load frontend assets
 *
 * @since 1.0.0
 * @package Advertising
 * @author AyeCode Ltd
 */

defined( 'ABSPATH' ) || exit;

/**
 * Adv_Frontend_Assets Class.
 */
class Adv_Frontend_Assets {

	/**
	 * Hook in tabs.
	 */
	public function __construct() {
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'load_scripts' ) );
		add_action( 'wp_footer', array( __CLASS__, 'maybe_load_jquery' ), 1 );
        add_action( 'wp_footer', array( __CLASS__, 'maybe_inline_scripts' ), 100 );
	}

	/**
	 * Enqueue scripts.
	 */
	public static function register_scripts() {
		$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

		wp_register_script( 'adv-dashboard', ADVERTISING_PLUGIN_URL . 'assets/js/dashboard' . $suffix . '.js', array( 'jquery', 'jcrop', 'select2' ), filemtime( ADVERTISING_PLUGIN_DIR . 'assets/js/dashboard' . $suffix . '.js' ) );
	}

	/**
	 * Load scripts.
	 */
	public static function load_scripts() {
		$is_dashboard = adv_is_dashboard_page();

		// Register scripts
		self::register_scripts();

		wp_localize_script(
			'adv-dashboard',
			'adv_params',
			array(
				'ajax_url'        => admin_url( 'admin-ajax.php' ),
				'nonce'           => wp_create_nonce( 'adv-nonce' ),
				'ConfirmDeleteAd' => addslashes( __( 'Are you sure to delete this Ad?', 'advertising' ) ),
				'invalid_file'    => __( 'Unsupported file type.', 'advertising' ),
				'searching'       => __( 'Searching...', 'advertising' ),
				'search_listings' => __( 'Search Listings', 'advertising' ),
				'ajax_nonce'      => wp_create_nonce( 'adv-ajax-nonce' ),
			)
		);

		if ( $is_dashboard || is_active_widget( false, false, 'ads_dashboard', true ) ) {
			wp_enqueue_style( 'jcrop' );
			wp_enqueue_script( 'adv-dashboard' );
		}
	}

	/**
	 * Loads jquery.
	 */
	public static function maybe_load_jquery() {
		if ( ! empty( $GLOBALS['adv_displayed_zones'] ) || ! empty( $GLOBALS['adv_displayed_ads'] ) ) {
			wp_enqueue_script( 'jquery' );
		}
	}

    /**
	 * Inlines js scripts.
	 */
	public static function maybe_inline_scripts() {
        if ( empty( $GLOBALS['adv_displayed_zones'] ) && empty( $GLOBALS['adv_displayed_ads'] ) ) {
            return;
        }
        ?>

        <script type="text/javascript">
            (function($) {
                const GetPaid_Ads = {
                    ajaxUrl: '<?php echo admin_url( 'admin-ajax.php' ); ?>',
                    ajaxNonce: '<?php echo wp_create_nonce( 'adv-nonce' ); ?>',

                    init: function() {
                        this.trackImpressions();
                        this.adRotation();
                    },

                    trackImpressions: function() {
                        const impressions = {
                            _ajax_nonce: this.ajaxNonce,
                            action: 'adv_track_impressions',
                            ads: $('.adv-single').map((_, el) => $(el).data('id')).get(),
                            zones: $('.adv-single-zone').map((_, el) => $(el).data('id')).get()
                        };

                        $.post( this.ajaxUrl, impressions );
                    },

                    adRotation: function() {
                        const self = this;
                        
                        $(document).find( '.adv-single-zone[data-adr]' ).each(function(){
                            if ( ! $(this).data('adr') ) {
                                return;
                            }   
                            
                            const $zone = $(this);
                            const zoneId = parseInt($zone.data('id'), 10);
                            const adRotation = parseInt($zone.data('adr') || 60);
                            const interval = adRotation * 1000;

                            const rotate = () => {
                                const skipAds = $zone.find('.adv-single').map((_, el) => parseInt($(el).data('id'), 10)).get();

                                $.ajax({
                                    url: self.ajaxUrl,
                                    method: 'POST',
                                    data: {
                                        _ajax_nonce: self.ajaxNonce,
                                        action: 'adv_rotate_ads',
                                        zone_id: zoneId,
                                        skip_ads: skipAds
                                    },
                                    success(res) {
                                        $zone.fadeOut(200, () => {
                                            $zone.html(res.data).fadeIn(200, () => {
                                                if (typeof geodir_init_lazy_load === 'function') geodir_init_lazy_load($);
                                                if (typeof geodir_init_flexslider === 'function') geodir_init_flexslider();
                                            });
                                        });
                                    },
                                    complete() {
                                        self.trackImpressions();
                                    }
                                });
                            };

                            let timer = setInterval(rotate, interval);

                            $zone
                                .on('mouseover', () => clearInterval(timer))
                                .on('mouseout', () => (timer = setInterval(rotate, interval)));
                        });
                    }
                }
            
                $(document).ready(function(){
                    GetPaid_Ads.init();
                });
            })(jQuery);
        </script>

        <style>
            .adv-shadow-on-hover:hover {
                border: 1px solid #9e9e9e;
            }
        </style>
        <?php
	}
}

new Adv_Frontend_Assets();
