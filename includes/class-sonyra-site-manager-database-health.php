<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Sonyra_Site_Manager_Database_Health {

	public static function get_required_tables(): array {
		if ( ! class_exists( 'Sonyra_Site_Manager_Database' ) ) {
			return array();
		}

		return Sonyra_Site_Manager_Database::get_table_names();
	}

	public static function check_tables(): array {
		if ( ! class_exists( 'Sonyra_Site_Manager_Database' ) ) {
			return array(
				'required'       => array(),
				'existing'       => array(),
				'missing'        => array(),
				'total_required' => 0,
				'total_existing' => 0,
				'is_ready'       => false,
			);
		}

		$table_status = Sonyra_Site_Manager_Database::tables_exist();

		return array(
			'required'       => $table_status['required'],
			'existing'       => $table_status['existing'],
			'missing'        => $table_status['missing'],
			'total_required' => count( $table_status['required'] ),
			'total_existing' => count( $table_status['existing'] ),
			'is_ready'       => (bool) $table_status['is_ready'],
		);
	}

	public static function check_db_version(): array {
		$required_db_version  = defined( 'SONYRA_SITE_MANAGER_DB_VERSION' ) ? SONYRA_SITE_MANAGER_DB_VERSION : '';
		$installed_db_version = (string) get_option( 'sonyra_site_manager_db_version', '' );

		return array(
			'required_db_version'  => $required_db_version,
			'installed_db_version' => $installed_db_version,
			'matches'              => '' !== $required_db_version && $required_db_version === $installed_db_version,
		);
	}

	public static function get_required_columns(): array {
		return array(
			'sites'       => array(
				'id',
				'site_key',
				'title',
				'status',
				'settings',
				'created_by',
				'created_at',
				'updated_at',
			),
			'pages'       => array(
				'id',
				'site_id',
				'slug',
				'title',
				'status',
				'author_id',
				'settings',
				'published_at',
				'created_at',
				'updated_at',
			),
			'page_blocks' => array(
				'id',
				'page_id',
				'block_type',
				'sort_order',
				'settings',
				'content',
				'status',
				'created_at',
				'updated_at',
			),
			'audit_log'   => array(
				'id',
				'event_id',
				'event_type',
				'severity',
				'user_id',
				'source',
				'context',
				'created_at',
			),
			'version_log' => array(
				'id',
				'version_type',
				'previous_version',
				'new_version',
				'context',
				'created_at',
			),
			'auth_identities' => array(
				'id',
				'identity_uuid',
				'email_hash',
				'email_normalized',
				'wp_user_id',
				'role_key',
				'status',
				'provider_links',
				'metadata',
				'last_login_at',
				'created_at',
				'updated_at',
			),
			'auth_otp_challenges' => array(
				'id',
				'challenge_uuid',
				'identity_id',
				'email_hash',
				'code_hash',
				'channel',
				'purpose',
				'status',
				'attempts_count',
				'max_attempts',
				'sent_count',
				'expires_at',
				'used_at',
				'locked_until',
				'ip_hash',
				'user_agent_hash',
				'metadata',
				'created_at',
				'updated_at',
			),
			'auth_sessions' => array(
				'id',
				'session_uuid',
				'identity_id',
				'session_token_hash',
				'status',
				'ip_hash',
				'user_agent_hash',
				'created_at',
				'expires_at',
				'last_seen_at',
				'revoked_at',
				'metadata',
			),
			'auth_attempts' => array(
				'id',
				'attempt_type',
				'identity_id',
				'email_hash',
				'ip_hash',
				'user_agent_hash',
				'result',
				'reason',
				'metadata',
				'created_at',
			),
			'auth_trusted_devices' => array(
				'id',
				'device_uuid',
				'identity_id',
				'device_token_hash',
				'status',
				'ip_hash',
				'user_agent_hash',
				'created_at',
				'expires_at',
				'last_used_at',
				'revoked_at',
				'metadata',
			),
			'auth_providers' => array(
				'id',
				'provider_key',
				'title',
				'provider_type',
				'status',
				'settings',
				'created_at',
				'updated_at',
			),
		);
	}

	public static function check_columns(): array {
		global $wpdb;

		$required_columns = self::get_required_columns();
		$table_names      = self::get_required_tables();
		$tables           = array();
		$missing          = array();

		foreach ( $required_columns as $key => $columns ) {
			$tables[ $key ] = $columns;

			if ( ! isset( $table_names[ $key ] ) ) {
				$missing[ $key ] = $columns;
				continue;
			}

			$rows             = $wpdb->get_results( 'SHOW COLUMNS FROM ' . self::quote_identifier( $table_names[ $key ] ), ARRAY_A );
			$existing_columns = array();

			if ( is_array( $rows ) ) {
				foreach ( $rows as $row ) {
					if ( isset( $row['Field'] ) ) {
						$existing_columns[] = (string) $row['Field'];
					}
				}
			}

			$table_missing = array_values( array_diff( $columns, $existing_columns ) );

			if ( ! empty( $table_missing ) ) {
				$missing[ $key ] = $table_missing;
			}
		}

		return array(
			'tables'   => $tables,
			'missing'  => $missing,
			'is_ready' => empty( $missing ),
		);
	}

	public static function get_required_indexes(): array {
		return array(
			'sites'       => array(
				'PRIMARY',
				'site_key',
				'status',
			),
			'pages'       => array(
				'PRIMARY',
				'site_id',
				'slug',
				'status',
				'updated_at',
			),
			'page_blocks' => array(
				'PRIMARY',
				'page_id',
				'block_type',
				'sort_order',
				'status',
			),
			'audit_log'   => array(
				'PRIMARY',
				'event_id',
				'event_type',
				'severity',
				'user_id',
				'created_at',
			),
			'version_log' => array(
				'PRIMARY',
				'version_type',
				'new_version',
				'created_at',
			),
			'auth_identities' => array(
				'PRIMARY',
				'identity_uuid',
				'email_hash',
				'email_normalized',
				'wp_user_id',
				'role_key',
				'status',
			),
			'auth_otp_challenges' => array(
				'PRIMARY',
				'challenge_uuid',
				'identity_id',
				'email_hash',
				'channel',
				'purpose',
				'status',
				'expires_at',
				'locked_until',
				'created_at',
			),
			'auth_sessions' => array(
				'PRIMARY',
				'session_uuid',
				'identity_id',
				'session_token_hash',
				'status',
				'expires_at',
				'last_seen_at',
			),
			'auth_attempts' => array(
				'PRIMARY',
				'attempt_type',
				'identity_id',
				'email_hash',
				'ip_hash',
				'result',
				'created_at',
			),
			'auth_trusted_devices' => array(
				'PRIMARY',
				'device_uuid',
				'identity_id',
				'device_token_hash',
				'status',
				'expires_at',
				'last_used_at',
			),
			'auth_providers' => array(
				'PRIMARY',
				'provider_key',
				'provider_type',
				'status',
			),
		);
	}

	public static function check_indexes(): array {
		global $wpdb;

		$required_indexes = self::get_required_indexes();
		$table_names      = self::get_required_tables();
		$tables           = array();
		$missing          = array();

		foreach ( $required_indexes as $key => $indexes ) {
			$tables[ $key ] = $indexes;

			if ( ! isset( $table_names[ $key ] ) ) {
				$missing[ $key ] = $indexes;
				continue;
			}

			$rows             = $wpdb->get_results( 'SHOW INDEX FROM ' . self::quote_identifier( $table_names[ $key ] ), ARRAY_A );
			$existing_indexes = array();

			if ( is_array( $rows ) ) {
				foreach ( $rows as $row ) {
					if ( isset( $row['Key_name'] ) ) {
						$existing_indexes[] = (string) $row['Key_name'];
					}
				}
			}

			$existing_indexes = array_values( array_unique( $existing_indexes ) );
			$table_missing   = array_values( array_diff( $indexes, $existing_indexes ) );

			if ( ! empty( $table_missing ) ) {
				$missing[ $key ] = $table_missing;
			}
		}

		return array(
			'tables'   => $tables,
			'missing'  => $missing,
			'is_ready' => empty( $missing ),
		);
	}

	public static function get_health_report(): array {
		$db_version = self::check_db_version();
		$tables     = self::check_tables();
		$columns    = self::check_columns();
		$indexes    = self::check_indexes();

		return array(
			'db_version' => $db_version,
			'tables'     => $tables,
			'columns'    => $columns,
			'indexes'    => $indexes,
			'is_ready'   => ! empty( $db_version['matches'] ) && ! empty( $tables['is_ready'] ) && ! empty( $columns['is_ready'] ) && ! empty( $indexes['is_ready'] ),
			'checked_at' => self::get_current_time(),
		);
	}

	public static function maybe_log_health_problem(): void {
		$report = self::get_health_report();

		if ( ! empty( $report['is_ready'] ) ) {
			return;
		}

		$problem_context = array(
			'db_version'            => $report['db_version'],
			'missing_tables_count'  => count( $report['tables']['missing'] ),
			'missing_columns_count' => self::count_missing_items( $report['columns']['missing'] ),
			'missing_indexes_count' => self::count_missing_items( $report['indexes']['missing'] ),
		);
		$fingerprint     = md5( self::json_encode( $problem_context ) );

		if ( $fingerprint === (string) get_option( 'sonyra_site_manager_last_database_health_problem_hash', '' ) ) {
			return;
		}

		update_option( 'sonyra_site_manager_last_database_health_problem_hash', $fingerprint, false );

		if ( class_exists( 'Sonyra_Site_Manager_Audit_Log' ) ) {
			Sonyra_Site_Manager_Audit_Log::log_event(
				'core.database_health_problem',
				$problem_context,
				'warning'
			);
		}
	}

	private static function quote_identifier( string $identifier ): string {
		return '`' . str_replace( '`', '``', $identifier ) . '`';
	}

	private static function count_missing_items( array $missing ): int {
		$count = 0;

		foreach ( $missing as $items ) {
			if ( is_array( $items ) ) {
				$count += count( $items );
			}
		}

		return $count;
	}

	private static function json_encode( array $value ): string {
		if ( function_exists( 'wp_json_encode' ) ) {
			return (string) wp_json_encode( $value );
		}

		return (string) json_encode( $value );
	}

	private static function get_current_time(): string {
		if ( function_exists( 'current_time' ) ) {
			return current_time( 'mysql' );
		}

		return date( 'Y-m-d H:i:s' );
	}
}
