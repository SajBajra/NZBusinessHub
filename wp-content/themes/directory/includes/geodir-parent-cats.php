<?php
/**
 * GeoDirectory helper: display only parent categories (no child categories).
 *
 * Usage:
 * - Call directly: display_geodir_parent_categories( $post_type, $taxonomy );
 * - Or use shortcode: [gd_parent_cats post_type="gd_place" taxonomy="gd_placecategory"]
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Echo an unordered list of top-level GeoDirectory categories for a post type or taxonomy.
 *
 * @param string $post_type GeoDirectory post type (e.g. 'gd_place'). Optional.
 * @param string $taxonomy  Specific taxonomy name. If empty, the first category taxonomy for the post type will be used.
 */
function display_geodir_parent_categories( $post_type = 'gd_place', $taxonomy = '' ) {
    if ( empty( $taxonomy ) ) {
        if ( class_exists( 'GeoDir_Taxonomies' ) ) {
            $taxes = GeoDir_Taxonomies::get_taxonomies( $post_type );
            if ( ! empty( $taxes ) && is_array( $taxes ) ) {
                // prefer category-like taxonomies (suffix 'category')
                foreach ( $taxes as $t ) {
                    if ( strpos( $t, 'category' ) !== false ) {
                        $taxonomy = $t;
                        break;
                    }
                }
                if ( empty( $taxonomy ) ) {
                    $taxonomy = $taxes[0];
                }
            }
        }
    }

    if ( empty( $taxonomy ) ) {
        return;
    }

    $terms = get_terms( array(
        'taxonomy'   => $taxonomy,
        'hide_empty' => false,
        'parent'     => 0,
    ) );

    if ( is_wp_error( $terms ) || empty( $terms ) ) {
        return;
    }

    echo '<ul class="geodir-parent-cats">';
    foreach ( $terms as $term ) {
        $link = get_term_link( $term );
        if ( is_wp_error( $link ) ) {
            $link = '#';
        }
        printf( '<li class="cat-item cat-item-%1$d"><a href="%2$s">%3$s</a></li>', esc_attr( $term->term_id ), esc_url( $link ), esc_html( $term->name ) );
    }
    echo '</ul>';
}

/**
 * Shortcode wrapper so theme editors can place parent categories in content.
 * Usage: [gd_parent_cats post_type="gd_place" taxonomy="gd_placecategory"]
 */
function display_geodir_parent_categories_shortcode( $atts ) {
    $atts = shortcode_atts( array(
        'post_type' => 'gd_place',
        'taxonomy'  => '',
    ), $atts, 'gd_parent_cats' );

    ob_start();
    display_geodir_parent_categories( $atts['post_type'], $atts['taxonomy'] );
    return ob_get_clean();
}
add_shortcode( 'gd_parent_cats', 'display_geodir_parent_categories_shortcode' );


/**
 * Get top-level parent term(s) for a given post and taxonomy.
 * Returns an array of WP_Term objects representing the highest ancestor(s).
 *
 * @param int|null $post_id Post ID. If null, uses global post.
 * @param string $taxonomy Taxonomy name. If empty, attempts to detect one for the post type.
 * @return array|false Array of WP_Term objects or false when none.
 */
function geodir_get_post_top_parent_terms( $post_id = null, $taxonomy = '' ) {
    global $post;

    if ( empty( $post_id ) ) {
        if ( ! empty( $post ) && isset( $post->ID ) ) {
            $post_id = $post->ID;
        } else {
            return false;
        }
    }

    $post_type = get_post_type( $post_id );

    if ( empty( $taxonomy ) ) {
        if ( class_exists( 'GeoDir_Taxonomies' ) ) {
            $taxes = GeoDir_Taxonomies::get_taxonomies( $post_type );
            if ( ! empty( $taxes ) && is_array( $taxes ) ) {
                foreach ( $taxes as $t ) {
                    if ( strpos( $t, 'category' ) !== false ) {
                        $taxonomy = $t;
                        break;
                    }
                }
                if ( empty( $taxonomy ) ) {
                    $taxonomy = $taxes[0];
                }
            }
        }
    }

    if ( empty( $taxonomy ) ) {
        return false;
    }

    $terms = wp_get_post_terms( $post_id, $taxonomy );

    if ( empty( $terms ) || is_wp_error( $terms ) ) {
        return false;
    }

    $top_parents = array();

    foreach ( $terms as $term ) {
        $current = $term;
        while ( $current && $current->parent && $current->parent != 0 ) {
            $parent = get_term( $current->parent, $current->taxonomy );
            if ( is_wp_error( $parent ) || ! $parent ) {
                break;
            }
            $current = $parent;
        }

        if ( $current && ! isset( $top_parents[ $current->term_id ] ) ) {
            $top_parents[ $current->term_id ] = $current;
        }
    }

    if ( empty( $top_parents ) ) {
        return false;
    }

    return array_values( $top_parents );
}


/**
 * Display top-level parent category(ies) for a post as an unordered list.
 * Shows only ancestor terms (parent == 0), child categories are not displayed.
 *
 * @param int|null $post_id Post ID. If null uses global post.
 * @param string $taxonomy Optional taxonomy name.
 */
function display_geodir_post_parent_categories( $post_id = null, $taxonomy = '' ) {
    $terms = geodir_get_post_top_parent_terms( $post_id, $taxonomy );

    if ( empty( $terms ) || is_wp_error( $terms ) ) {
        return;
    }

    echo '<ul class="geodir-post-parent-cats">';
    foreach ( $terms as $term ) {
        $link = get_term_link( $term );
        if ( is_wp_error( $link ) ) {
            $link = '#';
        }
        printf( '<li class="cat-item cat-item-%1$d"><a href="%2$s">%3$s</a></li>', esc_attr( $term->term_id ), esc_url( $link ), esc_html( $term->name ) );
    }
    echo '</ul>';
}


/**
 * Shortcode wrapper: [gd_post_parent_cats post_id="" taxonomy=""]
 */
function display_geodir_post_parent_categories_shortcode( $atts ) {
    $atts = shortcode_atts( array(
        'post_id'  => '',
        'taxonomy' => '',
    ), $atts, 'gd_post_parent_cats' );

    ob_start();
    $post_id = ! empty( $atts['post_id'] ) ? intval( $atts['post_id'] ) : null;
    display_geodir_post_parent_categories( $post_id, $atts['taxonomy'] );
    return ob_get_clean();
}
add_shortcode( 'gd_post_parent_cats', 'display_geodir_post_parent_categories_shortcode' );

/**
 * Ensure our CSS override is present in the page head to avoid caching/override issues.
 */
if ( ! function_exists( 'geodir_parent_cats_inline_css' ) ) {
    function geodir_parent_cats_inline_css() {
        if ( is_admin() ) {
            return;
        }

        // Inline CSS - high specificity and !important to override other styles or cached assets.
        echo "\n<!-- GeoDirectory parent-cats override -->\n<style>\n.geodir-parent-cats li::after, .geodir-post-parent-cats li::after { display: none !important; content: none !important; }\n.geodir-parent-cats li .caret, .geodir-post-parent-cats li .caret, .geodir-parent-cats li .dropdown-toggle, .geodir-post-parent-cats li .dropdown-toggle, .geodir-parent-cats li .fa-caret-down, .geodir-post-parent-cats li .fa-caret-down, .geodir-parent-cats li .fa-chevron-down, .geodir-post-parent-cats li .fa-chevron-down { display: none !important; }\n/* Hide GeoDirectory category dropdown chevrons & sub‑menus so only parent cards show */\n.gd-cptcat-li-sub-container, .gd-cptcat-li-sub-container > a.btn, .gd-cptcat-li-sub-container .dropdown-menu { display: none !important; }\n</style>\n";
    }
    add_action( 'wp_head', 'geodir_parent_cats_inline_css', 1 );
}

/**
 * Late inline CSS specifically for the Business Categories page.
 *
 * This is printed at a very high priority so it can override BlockStrap and
 * GeoDirectory defaults using both specificity and !important.
 */
if ( ! function_exists( 'directory_business_categories_inline_css' ) ) {
	function directory_business_categories_inline_css() {
		if ( is_admin() || ! is_page( 'business-categories' ) ) {
			return;
		}

		echo "\n<!-- Business Categories hard overrides -->\n<style id=\"directory-business-cats-inline\">\n";
		echo "body.page-business-categories .business-cats-list{margin:0!important;padding-left:1.2rem!important;column-count:3!important;column-gap:2.25rem!important;list-style:none!important;}\n";
		echo "body.page-business-categories .business-cats-list li{break-inside:avoid!important;margin-bottom:0.4rem!important;position:relative!important;padding-left:1.1rem!important;font-size:0.96rem!important;}\n";
		echo "body.page-business-categories .business-cats-list li::before{content:\"\\25B8\"!important;position:absolute!important;left:0!important;top:0.05rem!important;font-size:0.7rem!important;color:#111827!important;}\n";
		echo "body.page-business-categories .business-cats-list a{color:#111827!important;text-decoration:none!important;}\n";
		echo "body.page-business-categories .business-cats-list a:hover{color:#3993d5!important;text-decoration:underline!important;}\n";
		echo "body.page-business-categories .business-cats-panel{border-radius:0.85rem!important;overflow:hidden!important;border:1px solid #d4ddf5!important;background-color:#f9fbff!important;}\n";
		echo "body.page-business-categories .business-cats-panel-header{background:linear-gradient(135deg,#0b63ce 0%,#2d7ab8 100%)!important;color:#ffffff!important;font-weight:600!important;padding:0.7rem 1.1rem!important;display:flex!important;align-items:center!important;justify-content:center!important;gap:0.5rem!important;font-size:1.1rem!important;}\n";
		echo "body.page-business-categories .business-cats-panel-header a{color:#ffffff!important;text-decoration:none!important;}\n";
		echo "body.page-business-categories .business-cats-panel-header a:hover{color:#ffffff!important;text-decoration:none!important;}\n";
		echo "body.page-business-categories .business-cats-panel-body{padding:1rem 1.1rem 1.1rem!important;background-color:#f9fbff!important;display:flex!important;flex-wrap:wrap!important;gap:0.5rem 0.75rem!important;}\n";
		echo "body.page-business-categories .business-cats-chip{display:inline-flex!important;align-items:center!important;justify-content:center!important;padding:0.35rem 0.9rem!important;border-radius:999px!important;background-color:#ffffff!important;color:#0073aa!important;font-size:0.85rem!important;text-decoration:none!important;border:1px solid #d4ddf5!important;white-space:nowrap!important;}\n";
		echo "body.page-business-categories .business-cats-chip:hover{background-color:#0b63ce!important;color:#ffffff!important;box-shadow:0 4px 12px rgba(15,23,42,0.15)!important;transform:translateY(-1px)!important;text-decoration:none!important;}\n";
		echo "</style>\n";
	}
	add_action( 'wp_head', 'directory_business_categories_inline_css', 999 );
}

/**
 * Force GeoDirectory category listings to only show top-level (parent) terms on the front‑end.
 *
 * This affects all places that call get_terms() for GeoDirectory category taxonomies, such as
 * category dropdowns, filters, and widgets – child categories are not returned at all.
 */
if ( ! function_exists( 'geodir_filter_to_parent_categories_only' ) ) {
    /**
     * @param array        $args       Query arguments passed to get_terms().
     * @param string|array $taxonomies One or more taxonomy names.
     *
     * @return array
     */
    function geodir_filter_to_parent_categories_only( $args, $taxonomies ) {
        if ( is_admin() ) {
            return $args;
        }

        $taxonomies    = (array) $taxonomies;
        $is_geodir_cat = false;

        foreach ( $taxonomies as $taxonomy ) {
            // Match GeoDirectory taxonomies that behave like categories, e.g. gd_placecategory.
            if ( 0 === strpos( $taxonomy, 'gd_' ) && false !== strpos( $taxonomy, 'category' ) ) {
                $is_geodir_cat = true;
                break;
            }
        }

        if ( ! $is_geodir_cat ) {
            return $args;
        }

        // If no explicit parent was requested, restrict to top‑level terms only.
        if ( ! isset( $args['parent'] ) ) {
            $args['parent'] = 0;
        }

        return $args;
    }

    add_filter( 'get_terms_args', 'geodir_filter_to_parent_categories_only', 10, 2 );
}

/**
 * Disable GeoDirectory category dropdown sub-menus, so only parent categories are shown.
 *
 * This prevents `GD > Categories` widgets and category blocks from rendering
 * the clickable chevron + dropdown list of child categories under each parent.
 */
if ( ! function_exists( 'geodir_disable_category_dropdowns' ) ) {
	/**
	 * Force max_level to 1 and skip_childs to true for categories widgets.
	 *
	 * @param array $instance Widget/shortcode instance args.
	 *
	 * @return array
	 */
	function geodir_disable_category_dropdowns( $instance ) {
		// Only touch GeoDirectory category widgets / blocks.
		if ( empty( $instance['gd_widget'] ) || $instance['gd_widget'] !== 'categories' ) {
			return $instance;
		}

		$instance['max_level']  = 1;      // Only top-level terms.
		$instance['skip_childs'] = 1;     // Do not render child term lists.

		return $instance;
	}

	// When widget is rendered via PHP.
	add_filter( 'geodir_widget_categories_args', 'geodir_disable_category_dropdowns', 10, 1 );
	// When used via block / shortcode wrapper (defensive).
	add_filter( 'geodir_widget_args_categories', 'geodir_disable_category_dropdowns', 10, 1 );
}

/**
 * Shortcode to render the Business Categories page layout.
 *
 * This is separate from the generic GD archive layout so we can have:
 * - Hero + centred search bar
 * - Bullet-point list of all business categories
 * - Sections for parent categories that have child categories.
 *
 * Usage: [directory_category_page]
 */
if ( ! function_exists( 'directory_category_page_shortcode' ) ) {
	function directory_category_page_shortcode() {
		if ( function_exists( 'directory_business_categories_page_content' ) ) {
			return directory_business_categories_page_content();
		}

		return '';
	}

	add_shortcode( 'directory_category_page', 'directory_category_page_shortcode' );
}

/**
 * Ensure a "Business Categories" page exists and contains our shortcode so the layout
 * can be accessed via a normal page as well as archive views.
 *
 * If a legacy "Category" page already exists it will be converted to/published as
 * the Business Categories page to avoid 404s on that URL.
 */
if ( ! function_exists( 'directory_ensure_category_page' ) ) {
	function directory_ensure_category_page() {
		$shortcode = '[directory_category_page]';

		// Prefer an explicit Business Categories page if it exists.
		$page = get_page_by_path( 'business-categories' );
		if ( ! $page ) {
			$page = get_page_by_title( 'Business Categories' );
		}

		// Fallback: reuse any existing "Category" page as the Business Categories page.
		if ( ! $page ) {
			$page = get_page_by_path( 'category' );
		}
		if ( ! $page ) {
			$page = get_page_by_title( 'Category' );
		}

		if ( $page instanceof WP_Post ) {
			wp_update_post(
				array(
					'ID'           => $page->ID,
					'post_title'   => 'Business Categories',
					'post_name'    => 'business-categories',
					'post_status'  => 'publish',
					'post_type'    => 'page',
					'post_content' => $shortcode,
				)
			);

			return;
		}

		// Create the page if it does not exist.
		wp_insert_post(
			array(
				'post_title'   => 'Business Categories',
				'post_name'    => 'business-categories',
				'post_status'  => 'publish',
				'post_type'    => 'page',
				'post_content' => $shortcode,
			)
		);
	}

	add_action( 'init', 'directory_ensure_category_page' );
}

/**
 * Render the Business Categories page content.
 *
 * - Reuses the hero styling from the archive layout.
 * - Shows a centred GD search bar at 50% width.
 * - Lists all parent business categories as bullet links.
 * - For parents that have children, renders a separate section with child links.
 *
 * @return string
 */
function directory_business_categories_page_content() {
	ob_start();

	$site_name       = function_exists( 'directory_display_site_name' ) ? directory_display_site_name() : get_bloginfo( 'name' );
	$add_listing_url = function_exists( 'geodir_add_listing_page_url' ) ? geodir_add_listing_page_url() : '';
	if ( $add_listing_url && function_exists( 'directory_relative_url' ) ) {
		$add_listing_url = directory_relative_url( $add_listing_url );
	}
	$bc_home_url = function_exists( 'directory_relative_url' ) ? directory_relative_url( home_url( '/' ) ) : home_url( '/' );

	// Detect the main GeoDirectory category taxonomy for gd_place.
	$post_type = 'gd_place';
	$taxonomy  = '';
	if ( class_exists( 'GeoDir_Taxonomies' ) ) {
		$taxes = GeoDir_Taxonomies::get_taxonomies( $post_type );
		if ( ! empty( $taxes ) && is_array( $taxes ) ) {
			foreach ( $taxes as $t ) {
				if ( strpos( $t, 'category' ) !== false ) {
					$taxonomy = $t;
					break;
				}
			}
			if ( empty( $taxonomy ) ) {
				$taxonomy = $taxes[0];
			}
		}
	}

	if ( empty( $taxonomy ) ) {
		$taxonomy = 'gd_placecategory';
	}

	$parent_terms = get_terms(
		array(
			'taxonomy'   => $taxonomy,
			'hide_empty' => false,
			'parent'     => 0,
			'orderby'    => 'name',
			'order'      => 'ASC',
		)
	);

	// Map of parent term -> array of child terms (only those parents that have children).
	$parents_with_children = array();
	if ( ! empty( $parent_terms ) && ! is_wp_error( $parent_terms ) ) {
		foreach ( $parent_terms as $parent_term ) {
			$children = get_terms(
				array(
					'taxonomy'   => $taxonomy,
					'hide_empty' => false,
					'parent'     => $parent_term->term_id,
					'orderby'    => 'name',
					'order'      => 'ASC',
				)
			);

			if ( ! empty( $children ) && ! is_wp_error( $children ) ) {
				$parents_with_children[ $parent_term->term_id ] = $children;
			}
		}
	}
	?>

	<?php
	$bc_hero_bg = esc_url( get_stylesheet_directory_uri() . '/assets/images/home-bg.jpg' );
	?>
	<div class="bc-page">
		<section class="bc-hero bc-hero-with-bg" style="background-image: linear-gradient(135deg, rgba(57, 147, 213, 0.78), rgba(32, 99, 160, 0.82)), url('<?php echo $bc_hero_bg; ?>');">
			<div class="bc-hero-inner">
				<nav class="bc-hero-breadcrumb" aria-label="<?php esc_attr_e( 'Breadcrumb', 'directory' ); ?>">
					<a href="<?php echo esc_url( $bc_home_url ); ?>"><?php esc_html_e( 'Home', 'directory' ); ?></a>
					<span class="bc-hero-breadcrumb-sep" aria-hidden="true">›</span>
					<span class="bc-hero-breadcrumb-current"><?php esc_html_e( 'Business Categories', 'directory' ); ?></span>
				</nav>
				<h1 class="bc-hero-title"><?php esc_html_e( 'Business Categories', 'directory' ); ?></h1>
				<p class="bc-hero-description"><?php esc_html_e( 'Explore businesses organized by category to find exactly what you need.', 'directory' ); ?></p>
				<?php if ( ! empty( $add_listing_url ) ) : ?>
					<a class="cf-btn-add bc-hero-cta" href="<?php echo esc_url( $add_listing_url ); ?>"><?php esc_html_e( 'Add your business', 'directory' ); ?></a>
				<?php endif; ?>
			</div>
		</section>

		<section class="bc-search">
			<div class="bc-search-inner">
				<label for="bc-category-search" class="bc-search-label"><?php esc_html_e( 'Search categories', 'directory' ); ?></label>
				<input type="search" id="bc-category-search" class="bc-search-input" placeholder="<?php esc_attr_e( 'Type to filter categories…', 'directory' ); ?>" autocomplete="off" />
			</div>
		</section>

		<?php if ( ! empty( $parent_terms ) && ! is_wp_error( $parent_terms ) ) : ?>
			<section class="bc-browse">
				<div class="bc-browse-inner">
					<h2 class="bc-browse-title"><?php esc_html_e( 'Browse by Category', 'directory' ); ?></h2>
					<div class="bc-list-card">
						<ul class="bc-list">
							<?php foreach ( $parent_terms as $parent_term ) :
								$link = get_term_link( $parent_term );
								if ( is_wp_error( $link ) ) {
									$link = '#';
								} else {
									$link = function_exists( 'directory_relative_url' ) ? directory_relative_url( $link ) : $link;
								}
								$cat_icon = get_term_meta( $parent_term->term_id, 'ct_cat_font_icon', true );
								if ( empty( $cat_icon ) ) {
									$cat_icon = 'fas fa-globe';
								}
								?>
								<li class="bc-list-item">
									<a class="bc-list-link" href="<?php echo esc_url( $link ); ?>">
										<span class="bc-list-icon <?php echo esc_attr( $cat_icon ); ?>" aria-hidden="true"></span>
										<?php echo esc_html( $parent_term->name ); ?>
									</a>
								</li>
							<?php endforeach; ?>
						</ul>
					</div>
				</div>
			</section>
		<?php endif; ?>

		<?php if ( ! empty( $parents_with_children ) ) : ?>
			<?php
			usort(
				$parent_terms,
				function ( $a, $b ) use ( $parents_with_children ) {
					$has_children_a = isset( $parents_with_children[ $a->term_id ] );
					$has_children_b = isset( $parents_with_children[ $b->term_id ] );
					if ( $has_children_a && $has_children_b ) {
						return strcasecmp( $a->name, $b->name );
					}
					if ( $has_children_a ) {
						return -1;
					}
					if ( $has_children_b ) {
						return 1;
					}
					return 0;
				}
			);
			?>
			<section class="bc-sub">
				<div class="bc-sub-inner">
					<div class="bc-panels">
						<?php
						foreach ( $parent_terms as $parent_term ) :
							if ( empty( $parents_with_children[ $parent_term->term_id ] ) ) {
								continue;
							}
							$children    = $parents_with_children[ $parent_term->term_id ];
							$parent_link = get_term_link( $parent_term );
							if ( is_wp_error( $parent_link ) ) {
								$parent_link = '#';
							} else {
								$parent_link = function_exists( 'directory_relative_url' ) ? directory_relative_url( $parent_link ) : $parent_link;
							}
							$parent_icon = get_term_meta( $parent_term->term_id, 'ct_cat_font_icon', true );
							if ( empty( $parent_icon ) ) {
								$parent_icon = 'fas fa-globe';
							}
							?>
							<article class="bc-panel">
								<header class="bc-panel-header">
									<a href="<?php echo esc_url( $parent_link ); ?>">
										<span class="bc-panel-header-icon <?php echo esc_attr( $parent_icon ); ?>" aria-hidden="true"></span>
										<?php echo esc_html( $parent_term->name ); ?>
									</a>
								</header>
								<div class="bc-panel-body">
									<?php foreach ( $children as $child ) :
										$child_link = get_term_link( $child );
										if ( is_wp_error( $child_link ) ) {
											$child_link = '#';
										} else {
											$child_link = function_exists( 'directory_relative_url' ) ? directory_relative_url( $child_link ) : $child_link;
										}
										?>
										<a class="bc-chip" href="<?php echo esc_url( $child_link ); ?>"><?php echo esc_html( $child->name ); ?></a>
									<?php endforeach; ?>
								</div>
							</article>
						<?php endforeach; ?>
					</div>
				</div>
			</section>
		<?php endif; ?>

		<script>
		(function() {
			var input = document.getElementById('bc-category-search');
			if (!input) return;
			function filterCategories() {
				var q = (input.value || '').trim().toLowerCase();
				var listItems = document.querySelectorAll('.bc-page .bc-list-item');
				var panels = document.querySelectorAll('.bc-page .bc-panel');
				listItems.forEach(function(li) {
					var text = (li.textContent || '').toLowerCase();
					li.style.display = q === '' || text.indexOf(q) !== -1 ? '' : 'none';
				});
				panels.forEach(function(panel) {
					var header = panel.querySelector('.bc-panel-header');
					var body = panel.querySelector('.bc-panel-body');
					var headerText = (header ? header.textContent : '').toLowerCase();
					var bodyText = (body ? body.textContent : '').toLowerCase();
					var match = q === '' || headerText.indexOf(q) !== -1 || bodyText.indexOf(q) !== -1;
					panel.style.display = match ? '' : 'none';
				});
			}
			input.addEventListener('input', filterCategories);
			input.addEventListener('search', filterCategories);
		})();
		</script>
	</div>

	<?php
	return ob_get_clean();
}
