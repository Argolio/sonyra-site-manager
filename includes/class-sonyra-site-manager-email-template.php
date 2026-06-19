<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Sonyra_Site_Manager_Email_Template {

	public static function get_otp_subject(): string {
		return 'Код входа в ПУЛЬТ САЙТА';
	}

	public static function render_otp_plain_text( string $code, array $context = array() ): string {
		if ( ! self::is_valid_code( $code ) ) {
			return '';
		}

		$safe_context = self::get_safe_context( $context );
		unset( $safe_context );

			return implode(
				"\n",
				array(
					'Ваш код входа в ПУЛЬТ САЙТА: ' . $code,
					'Код действует 5 минут.',
					'Если вы не запрашивали вход, просто проигнорируйте это письмо.',
					self::get_footer_text(),
				)
			);
	}

	public static function render_otp_html( string $code, array $context = array() ): string {
		if ( ! self::is_valid_code( $code ) ) {
			return '';
		}

		$safe_context = self::get_safe_context( $context );
		unset( $safe_context );

		$logo_url = self::get_logo_url();
		$logo_html = '';
		$code_cells = '';

		if ( '' !== $logo_url ) {
			$escaped_logo_url = function_exists( 'esc_url' ) ? esc_url( $logo_url ) : htmlspecialchars( $logo_url, ENT_QUOTES, 'UTF-8' );
			$logo_html = '<img src="' . $escaped_logo_url . '" width="44" height="44" alt="" style="display:block;width:44px;height:44px;object-fit:contain;border:0;">';
		}

		foreach ( str_split( $code ) as $digit ) {
			$escaped_digit = function_exists( 'esc_html' ) ? esc_html( $digit ) : htmlspecialchars( $digit, ENT_QUOTES, 'UTF-8' );
			$code_cells .= '<td align="center" style="padding:0 2px;"><div style="width:40px;max-width:40px;border:1px solid #dbe4ff;border-radius:12px;background:#f8fbff;color:#111827;font-size:26px;line-height:42px;font-weight:800;text-align:center;">' . $escaped_digit . '</div></td>';
		}

		return '<!doctype html><html><head><meta charset="UTF-8"><title>Код входа в ПУЛЬТ САЙТА</title></head><body style="margin:0;padding:0;background:#f5f7fb;font-family:Arial,Helvetica,sans-serif;color:#172033;">'
			. '<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="width:100%;background:#f5f7fb;margin:0;padding:24px 0;"><tr><td align="center" style="padding:0 12px;">'
			. '<table role="presentation" width="560" cellspacing="0" cellpadding="0" style="width:560px;max-width:100%;background:#ffffff;border-radius:24px;border:1px solid #e6ebf3;overflow:hidden;box-shadow:0 18px 54px rgba(15,23,42,0.08);">'
			. '<tr><td style="padding:26px 30px 18px 30px;background:linear-gradient(135deg,#f8fbff 0%,#eef2ff 58%,#faf5ff 100%);border-bottom:1px solid #e6ebf3;">'
			. '<table role="presentation" cellspacing="0" cellpadding="0" style="width:100%;"><tr><td width="56" style="width:56px;vertical-align:middle;">' . $logo_html . '</td><td style="vertical-align:middle;"><div style="font-size:18px;line-height:24px;font-weight:800;color:#111827;">ПУЛЬТ САЙТА</div><div style="font-size:12px;line-height:18px;font-weight:700;letter-spacing:0.14em;color:#64748b;">SONYRA STUDIO</div></td></tr></table>'
			. '</td></tr>'
			. '<tr><td style="padding:30px 30px 12px 30px;font-size:28px;line-height:34px;font-weight:800;color:#172033;">Код входа</td></tr>'
			. '<tr><td style="padding:0 30px 18px 30px;font-size:16px;line-height:24px;color:#3d4b63;">Введите этот код на странице входа.</td></tr>'
			. '<tr><td align="center" style="padding:0 24px 20px 24px;"><table role="presentation" cellspacing="0" cellpadding="0"><tr>' . $code_cells . '</tr></table></td></tr>'
			. '<tr><td style="padding:0 30px 10px 30px;font-size:16px;line-height:24px;color:#3d4b63;">Код действует 5 минут.</td></tr>'
			. '<tr><td style="padding:0 30px 30px 30px;font-size:14px;line-height:22px;color:#6b7280;">Если вы не запрашивали вход, просто проигнорируйте это письмо.</td></tr>'
			. '<tr><td style="padding:18px 30px;background:#f8fafc;font-size:12px;line-height:20px;color:#6b7280;">' . self::escape_html( self::get_footer_text() ) . '</td></tr>'
			. '</table></td></tr></table></body></html>';
	}

	public static function get_otp_headers(): array {
		return array(
			'Content-Type: text/html; charset=UTF-8',
		);
	}

	public static function get_safe_context( array $context ): array {
		$unsafe_keys = array(
			'code',
			'otp',
			'token',
			'secret',
			'password',
			'nonce',
			'cookie',
			'authorization',
			'api_key',
			'email',
		);

		foreach ( $unsafe_keys as $key ) {
			if ( array_key_exists( $key, $context ) ) {
				unset( $context[ $key ] );
			}
		}

		return $context;
	}

	private static function is_valid_code( string $code ): bool {
		return 1 === preg_match( '/^\d{6}$/', $code );
	}

	private static function get_logo_url(): string {
		if ( ! defined( 'SONYRA_SITE_MANAGER_FILE' ) || ! function_exists( 'plugin_dir_path' ) || ! function_exists( 'plugins_url' ) ) {
			return '';
		}

		$logo_path = plugin_dir_path( SONYRA_SITE_MANAGER_FILE ) . 'assets/images/brand/pult-site-logo.png';

		if ( ! file_exists( $logo_path ) ) {
			return '';
		}

		return plugins_url( 'assets/images/brand/pult-site-logo.png', SONYRA_SITE_MANAGER_FILE );
	}

	private static function get_footer_text(): string {
		$year = function_exists( 'date_i18n' ) ? date_i18n( 'Y' ) : date( 'Y' );
		$version = defined( 'SONYRA_SITE_MANAGER_VERSION' ) ? SONYRA_SITE_MANAGER_VERSION : '';

		return 'SONYRA STUDIO · © ' . $year . ' ИП Катаев С.А. · Версия ' . $version;
	}

	private static function escape_html( string $value ): string {
		return function_exists( 'esc_html' ) ? esc_html( $value ) : htmlspecialchars( $value, ENT_QUOTES, 'UTF-8' );
	}
}
