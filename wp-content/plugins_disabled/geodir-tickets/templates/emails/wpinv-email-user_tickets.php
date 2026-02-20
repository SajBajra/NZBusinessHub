<?php
/**
 * Template that generates the user tickets.
 *
 * This template can be overridden by copying it to yourtheme/invoicing/email/wpinv-email-user_tickets.php.
 *
 * @version 1.0.19
 */

defined( 'ABSPATH' ) || exit;

$base      = wpinv_get_option( 'email_base_color', '#557da2' );
$base_text = wpinv_light_or_dark( $base, '#202020', '#ffffff' );

// Print the email header.
do_action( 'wpinv_email_header', $email_heading, $invoice, $email_type, $sent_to_admin );

// Generate the custom message body.
echo $message_body;

?>

<table width="100%" border="0" cellspacing="0" cellpadding="0">
	<tr>
		<td align="center" style="padding: 12px;">
			<div style='text-align: center; padding: 20px;' align='center'>
				<?php
                    printf(
                        '<a href="%s" style="background: %s; border: none; text-decoration: none; padding: 15px 25px; color: %s; border-radius: 4px; display:inline-block; mso-padding-alt:0;text-underline-color:%s"><span style="mso-text-raise:15pt;">%s</span></a>',
                        esc_url( geodir_tickets_get_download_url( $invoice ) ),
                        esc_attr( $base ),
                        esc_attr( $base_text ),
                        esc_attr( $base ),
                        esc_html__( 'View Tickets', 'geodir-tickets' )
		            );
                ?>
			</div>
		</td>
	</tr>
</table>

<?php
// Print the email footer.
do_action( 'wpinv_email_footer', $invoice, $email_type, $sent_to_admin );
