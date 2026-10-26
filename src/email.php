<?php
/**
 * The branded email template.
 *
 * Each email describes its content once, as parts, and gets back both an
 * HTML version and a plain-text one built from the same parts, so the two
 * can never say different things. The HTML is table-based with inline
 * styles: that is what Gmail, Outlook and phone mail apps reliably render.
 */

defined( 'PORTAL' ) || exit;

/**
 * @param array $p {
 *     @type string $preheader  Inbox preview line.
 *     @type string $heading    Large title.
 *     @type string $greeting   "Dear ...".
 *     @type array  $paragraphs Plain sentences.
 *     @type array  $highlight  Optional array( label, value ): one big figure.
 *     @type string $details_title
 *     @type array  $details    Optional label => value rows.
 *     @type array  $button     Optional array( label, url ).
 *     @type array  $link       Optional array( label, url ): a link shown in full, to copy.
 *     @type array  $extra      Optional array( label, url ): a secondary button.
 *     @type string $note       Optional small print under the content.
 * }
 * @return array array( text, html )
 */
function email_content( array $p ) {
	return array( email_text( $p ), email_html( $p ) );
}

function email_text( array $p ) {
	$out = array( $p['greeting'], '' );

	foreach ( $p['paragraphs'] ?? array() as $line ) {
		$out[] = $line;
		$out[] = '';
	}
	if ( ! empty( $p['highlight'] ) ) {
		$out[] = $p['highlight'][0] . ': ' . $p['highlight'][1];
		$out[] = '';
	}
	if ( ! empty( $p['details'] ) ) {
		foreach ( $p['details'] as $label => $value ) {
			$out[] = $label . ': ' . $value;
		}
		$out[] = '';
	}
	foreach ( array( 'button', 'link', 'extra' ) as $key ) {
		if ( ! empty( $p[ $key ] ) ) {
			$out[] = $p[ $key ][0] . ': ' . $p[ $key ][1];
			$out[] = '';
		}
	}
	if ( ! empty( $p['note'] ) ) {
		$out[] = $p['note'];
		$out[] = '';
	}

	$out[] = '-- ';
	$out[] = config( 'site_name' ) . ' · ' . config( 'site_url' );

	return implode( "\n", $out );
}

function email_html( array $p ) {
	$font  = "font-family:Inter,'Segoe UI',Helvetica,Arial,sans-serif;";
	$navy  = '#0f172a';
	$muted = '#64748b';

	$body = '';

	$body .= '<h1 style="margin:0 0 20px;' . $font . 'font-size:24px;line-height:1.3;font-weight:700;color:' . $navy . ';">' . e( $p['heading'] ) . '</h1>';
	$body .= '<p style="margin:0 0 16px;' . $font . 'font-size:16px;line-height:1.6;color:#334155;">' . e( $p['greeting'] ) . '</p>';

	foreach ( $p['paragraphs'] ?? array() as $line ) {
		$body .= '<p style="margin:0 0 16px;' . $font . 'font-size:16px;line-height:1.6;color:#334155;">' . e( $line ) . '</p>';
	}

	if ( ! empty( $p['highlight'] ) ) {
		$body .= '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin:8px 0 24px;">'
			. '<tr><td align="center" style="background:#f0fdf4;border:1px solid #bbf7d0;border-radius:10px;padding:22px 16px;">'
			. '<div style="' . $font . 'font-size:13px;letter-spacing:0.06em;text-transform:uppercase;color:#15803d;font-weight:600;">' . e( $p['highlight'][0] ) . '</div>'
			. '<div style="' . $font . 'font-size:32px;line-height:1.2;font-weight:800;color:#14532d;margin-top:6px;">' . e( $p['highlight'][1] ) . '</div>'
			. '</td></tr></table>';
	}

	if ( ! empty( $p['details'] ) ) {
		$rows = '';
		$last = count( $p['details'] ) - 1;
		$i    = 0;
		foreach ( $p['details'] as $label => $value ) {
			$border = $i++ < $last ? 'border-bottom:1px solid #e2e8f0;' : '';
			$rows  .= '<tr>'
				. '<td style="' . $border . 'padding:12px 0;' . $font . 'font-size:14px;color:' . $muted . ';width:40%;vertical-align:top;">' . e( $label ) . '</td>'
				. '<td style="' . $border . 'padding:12px 0;' . $font . 'font-size:15px;color:' . $navy . ';font-weight:600;text-align:right;word-break:break-word;">' . e( $value ) . '</td>'
				. '</tr>';
		}

		$title = ! empty( $p['details_title'] )
			? '<div style="' . $font . 'font-size:12px;letter-spacing:0.08em;text-transform:uppercase;color:' . $muted . ';font-weight:700;margin-bottom:4px;">' . e( $p['details_title'] ) . '</div>'
			: '';

		$body .= '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin:8px 0 24px;">'
			. '<tr><td style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:10px;padding:16px 20px;">'
			. $title
			. '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">' . $rows . '</table>'
			. '</td></tr></table>';
	}

	if ( ! empty( $p['button'] ) ) {
		$body .= email_button( $p['button'][0], $p['button'][1], '#2563eb' );
	}

	if ( ! empty( $p['link'] ) ) {
		$body .= '<div style="' . $font . 'font-size:14px;color:' . $muted . ';margin:0 0 8px;">' . e( $p['link'][0] ) . '</div>'
			. '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin:0 0 24px;">'
			. '<tr><td style="background:#eff6ff;border:1px dashed #93c5fd;border-radius:8px;padding:14px 16px;' . $font . 'font-size:14px;word-break:break-all;">'
			. '<a href="' . e( $p['link'][1] ) . '" style="color:#1d4ed8;text-decoration:none;">' . e( $p['link'][1] ) . '</a>'
			. '</td></tr></table>';
	}

	if ( ! empty( $p['extra'] ) ) {
		$body .= email_button( $p['extra'][0], $p['extra'][1], '#16a34a' );
	}

	if ( ! empty( $p['note'] ) ) {
		$body .= '<p style="margin:8px 0 0;' . $font . 'font-size:13px;line-height:1.6;color:' . $muted . ';">' . e( $p['note'] ) . '</p>';
	}

	$logo = absolute_url( 'assets/logo-email.png' );
	$site = config( 'site_url' );

	return '<!doctype html><html lang="en"><head><meta charset="utf-8">'
		. '<meta name="viewport" content="width=device-width,initial-scale=1"><meta name="color-scheme" content="light">'
		. '<title>' . e( $p['heading'] ) . '</title></head>'
		. '<body style="margin:0;padding:0;background:#eef2f7;">'
		// Inbox preview text, hidden in the message itself.
		. '<div style="display:none;max-height:0;overflow:hidden;opacity:0;">' . e( $p['preheader'] ?? '' ) . '</div>'
		. '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background:#eef2f7;">'
		. '<tr><td align="center" style="padding:32px 12px;">'
		. '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="max-width:600px;background:#ffffff;border-radius:14px;overflow:hidden;border:1px solid #e2e8f0;">'
		. '<tr><td align="center" style="background:' . $navy . ';padding:26px 24px;">'
		. '<a href="' . e( $site ) . '"><img src="' . e( $logo ) . '" width="200" alt="' . e( config( 'site_name' ) ) . '" style="display:block;width:200px;max-width:100%;height:auto;border:0;"></a>'
		. '</td></tr>'
		. '<tr><td style="background:#e63946;height:4px;line-height:4px;font-size:0;">&nbsp;</td></tr>'
		. '<tr><td style="padding:36px 36px 32px;">' . $body . '</td></tr>'
		. '<tr><td style="background:#f8fafc;border-top:1px solid #e2e8f0;padding:22px 36px;' . $font . 'font-size:12px;line-height:1.6;color:#94a3b8;text-align:center;">'
		. '<strong style="color:' . $muted . ';">' . e( config( 'site_name' ) ) . '</strong><br>'
		. '<a href="' . e( $site ) . '" style="color:#94a3b8;">' . e( preg_replace( '#^https?://#', '', $site ) ) . '</a>'
		. ' &middot; <a href="' . e( absolute_url() ) . '" style="color:#94a3b8;">Realtor portal</a><br>'
		. 'You are receiving this because you have a realtor portal account.'
		. '</td></tr>'
		. '</table>'
		. '</td></tr></table></body></html>';
}

/** A button that renders as a button in every mail client, Outlook included. */
function email_button( $label, $url, $color ) {
	return '<table role="presentation" cellpadding="0" cellspacing="0" border="0" style="margin:4px 0 24px;">'
		. '<tr><td align="center" bgcolor="' . $color . '" style="border-radius:8px;">'
		. '<a href="' . e( $url ) . '" style="display:inline-block;padding:14px 30px;font-family:Inter,\'Segoe UI\',Helvetica,Arial,sans-serif;font-size:16px;font-weight:700;color:#ffffff;text-decoration:none;border-radius:8px;">' . e( $label ) . '</a>'
		. '</td></tr></table>';
}
