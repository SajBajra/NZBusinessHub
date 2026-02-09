<?php
/**
 * The template to display reviews
 *
 * This template can be overridden by copying it to yourtheme/geodir_buddypress/bootstrap/reviews.php.
 *
 * HOWEVER, on occasion GeoDirectory will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see     https://wpgeodirectory.com/docs-v2/faq/customizing/#templates
 * @package Geodir_BuddyPress/Templates
 * @version 2.2.1
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>
<div id="comments" class="geodir-comments-area gdbp-comments-area bsui">

  <?php if ( have_comments() ) : ?>
  <ul class="commentlist list-unstyled">
    <?php
    $callback = apply_filters('geodir_buddypress_comment_callback', array('GeoDir_BuddyPress_Template', 'geodir_buddypress_comment'));

    wp_list_comments( array( 'callback' => $callback, 'style' => 'ol' ) ); ?>
  </ul>

  <?php if (get_comment_pages_count() > 1 && get_option('page_comments')) : // are there comments to navigate through ?>
      <nav id="comment-nav-below" class="navigation" role="navigation">
          <h1 class="assistive-text section-heading"><?php _e('Review navigation', 'geodir-buddypress'); ?></h1>

          <div class="nav-previous"><?php previous_comments_link(__('&larr; Older Reviews', 'geodir-buddypress')); ?></div>
          <div class="nav-next"><?php next_comments_link(__('Newer Reviews &rarr;', 'geodir-buddypress')); ?></div>
      </nav>
  <?php endif; // check for comment navigation ?>

  <?php
  /* If there are no comments and comments are closed, let's leave a note.
   * But we only want the note on posts and pages that had comments in the first place.
   */
  if (!comments_open() && get_comments_number()) : ?>
      <p class="nocomments"><?php _e('Reviews are closed.', 'geodir-buddypress'); ?></p>
  <?php endif; ?>

  <?php endif; // have_comments() ?>
</div>
<!-- #comments .comments-area -->
