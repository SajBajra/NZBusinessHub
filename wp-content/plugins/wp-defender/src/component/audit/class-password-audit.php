<?php
/**
 * Handles user password related audit events.
 *
 * @package WP_Defender\Component\Audit
 */

namespace WP_Defender\Component\Audit;

use WP_User;
use WP_Defender\Traits\User;
use WP_Defender\Model\Audit_Log;

/**
 * Handles user password related audit events, distinguishing between self and admin scenarios.
 *
 * @since 5.8.0
 */
class Password_Audit extends Audit_Event {
	use User;

	public const ACTION_PASSWORD_CHANGED = 'password-changed';
	public const CONTEXT_PASSWORD        = 'password';

	/**
	 * Returns the hooks to monitor password events.
	 *
	 * @return array
	 */
	public function get_hooks(): array {
		return array(
			'password_reset' => array(
				'args'        => array( 'user', 'new_pass' ),
				'event_type'  => Audit_Log::EVENT_TYPE_USER,
				'context'     => self::CONTEXT_PASSWORD,
				'action_type' => self::ACTION_PASSWORD_CHANGED,
				'callback'    => array( $this, 'password_reset' ),
			),
			'profile_update' => array(
				'args'        => array( 'user_id', 'old_user_data', 'userdata' ),
				'event_type'  => Audit_Log::EVENT_TYPE_USER,
				'context'     => self::CONTEXT_PASSWORD,
				'action_type' => self::ACTION_PASSWORD_CHANGED,
				'callback'    => array( $this, 'profile_update' ),
			),
		);
	}

	/**
	 * Callback for logging password changes via profile update.
	 *
	 * @param string $hook_name The name of the hook.
	 * @param array  $params The parameters passed to the hook.
	 *
	 * @return bool|array
	 */
	public function profile_update( string $hook_name, array $params ): bool|array {
		if ( User_Audit::is_create_user_action() ) {
			return false;
		}

		$user_id = $params['user_id'];
		$post    = defender_get_data_from_request( null, 'p' );
		// Password is not changed.
		if ( ! isset( $post['pass1'] ) || '' === $post['pass1'] ) {
			return false;
		}

		$user = get_user_by( 'id', $user_id );
		if ( ! $user ) {
			return false;
		}

		return $this->build_log( $user );
	}

	/**
	 * Helper to build the log message.
	 *
	 * @param WP_User $user The user object.
	 *
	 * @return array
	 */
	public function build_log( WP_User $user ): array {
		$username     = $user->user_login;
		$blog_name    = is_multisite() ? '[' . get_bloginfo( 'name' ) . ']' : '';
		$current_user = get_current_user_id();
		// User changed own password.
		if ( 0 === $current_user || $current_user === $user->ID ) {
			return array(
				sprintf(
				/* translators: 1: Blog name, 2: Username */
					esc_html__( '%1$s %2$s changed their password', 'wpdef' ),
					$blog_name,
					$username
				),
				self::CONTEXT_PASSWORD,
			);
		}
		// Admin changed another user's password.
		$current_display = $this->get_user_display( $current_user );

		return array(
			sprintf(
			/* translators: 1: Blog name, 2: Admin username, 3: Affected username */
				esc_html__( '%1$s %2$s changed the password for user: %3$s', 'wpdef' ),
				$blog_name,
				$current_display,
				$username
			),
			self::CONTEXT_PASSWORD,
		);
	}

	/**
	 * Callback for logging password reset events( via reset link )
	 *
	 * @param string $hook_name The name of the hook.
	 * @param array  $params The parameters passed to the hook.
	 *
	 * @return bool|array
	 */
	public function password_reset( string $hook_name, array $params ): bool|array {
		$user = $params['user'];
		if ( ! $user instanceof WP_User ) {
			return false;
		}

		return $this->build_log( $user );
	}
}