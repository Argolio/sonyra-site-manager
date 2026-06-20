<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Sonyra_Site_Manager_OTP_Policy {

	const CODE_LENGTH                 = 6;
	const CODE_TTL_SECONDS            = 300;
	const RESEND_COOLDOWN_SECONDS     = 60;
	const MAX_SENDS_PER_WINDOW        = 3;
	const SEND_WINDOW_SECONDS         = 900;
	const MAX_VERIFY_ATTEMPTS         = 5;
	const LOCKOUT_SECONDS             = 900;
	const LOCKOUT_ESCALATION_SECONDS  = 1800;
	const LOCKOUT_MAX_SECONDS         = 3600;

	public static function get_policy(): array {
		return array(
			'code_length'                => self::CODE_LENGTH,
			'code_ttl_seconds'           => self::CODE_TTL_SECONDS,
			'resend_cooldown_seconds'    => self::RESEND_COOLDOWN_SECONDS,
			'max_sends_per_window'       => self::MAX_SENDS_PER_WINDOW,
			'send_window_seconds'        => self::SEND_WINDOW_SECONDS,
			'max_verify_attempts'        => self::MAX_VERIFY_ATTEMPTS,
			'lockout_seconds'            => self::LOCKOUT_SECONDS,
			'lockout_escalation_seconds' => self::LOCKOUT_ESCALATION_SECONDS,
			'lockout_max_seconds'        => self::LOCKOUT_MAX_SECONDS,
		);
	}

	public static function get_code_length(): int {
		return self::CODE_LENGTH;
	}

	public static function get_code_ttl_seconds(): int {
		return self::CODE_TTL_SECONDS;
	}

	public static function get_max_verify_attempts(): int {
		return self::MAX_VERIFY_ATTEMPTS;
	}

	public static function get_lockout_seconds( int $previous_lockouts = 0 ): int {
		if ( $previous_lockouts <= 0 ) {
			return self::LOCKOUT_SECONDS;
		}

		if ( 1 === $previous_lockouts ) {
			return self::LOCKOUT_ESCALATION_SECONDS;
		}

		return self::LOCKOUT_MAX_SECONDS;
	}

	public static function normalize_purpose( string $purpose ): string {
		$purpose = trim( strtolower( $purpose ) );

		if ( in_array( $purpose, array( 'login', 'recovery', 'provider_link' ), true ) ) {
			return $purpose;
		}

		return 'login';
	}

	public static function normalize_channel( string $channel ): string {
		$channel = trim( strtolower( $channel ) );

		if ( 'email' === $channel ) {
			return 'email';
		}

		return 'email';
	}
}
