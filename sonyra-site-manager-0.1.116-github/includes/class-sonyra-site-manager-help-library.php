<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Sonyra_Site_Manager_Help_Library {

	private static function normalize_help_key( $key ): string {
		$key = is_string( $key ) ? trim( $key ) : '';

		if ( '' === $key ) {
			return '';
		}

		$key = strtolower( $key );

		return (string) preg_replace( '/[^a-z0-9._-]/', '', $key );
	}

	private static function text( string $key ): string {
		if ( class_exists( 'Sonyra_Site_Manager_I18n' ) ) {
			return Sonyra_Site_Manager_I18n::t( $key );
		}

		return '';
	}

	public static function get_core_entries(): array {
		return array(
			array(
				'key'            => 'pages.sections.panel',
				'category'       => 'pages',
				'section'        => 'pages',
				'source'         => 'core',
				'module_key'     => '',
				'editable'       => true,
				'i18n_title_key' => 'manager.help.pages.sections.panel.title',
				'i18n_body_key'  => 'manager.help.pages.sections.panel.body',
			),
			array(
				'key'            => 'pages.sections.blocks',
				'category'       => 'pages',
				'section'        => 'pages',
				'source'         => 'core',
				'module_key'     => '',
				'editable'       => true,
				'i18n_title_key' => 'manager.help.pages.sections.blocks.title',
				'i18n_body_key'  => 'manager.help.pages.sections.blocks.body',
			),
			array(
				'key'            => 'pages.widgets.panel',
				'category'       => 'pages',
				'section'        => 'pages',
				'source'         => 'core',
				'module_key'     => '',
				'editable'       => true,
				'i18n_title_key' => 'manager.help.pages.widgets.panel.title',
				'i18n_body_key'  => 'manager.help.pages.widgets.panel.body',
			),
			array(
				'key'            => 'pages.popups.panel',
				'category'       => 'pages',
				'section'        => 'pages',
				'source'         => 'core',
				'module_key'     => '',
				'editable'       => true,
				'i18n_title_key' => 'manager.help.pages.popups.panel.title',
				'i18n_body_key'  => 'manager.help.pages.popups.panel.body',
			),
			array(
				'key'            => 'design.colors.controller',
				'category'       => 'design',
				'section'        => 'design',
				'source'         => 'core',
				'module_key'     => '',
				'editable'       => true,
				'i18n_title_key' => 'manager.help.design.colors.controller.title',
				'i18n_body_key'  => 'manager.help.design.colors.controller.body',
			),
		);
	}

	public static function get_entries(): array {
		$entries = self::get_core_entries();

		if ( function_exists( 'apply_filters' ) ) {
			$entries = apply_filters( 'sonyra_site_manager_help_entries', $entries );
		}

		if ( ! is_array( $entries ) ) {
			$entries = array();
		}

		$normalized = array();

		foreach ( $entries as $entry ) {
			if ( ! is_array( $entry ) ) {
				continue;
			}

			$next_entry = self::normalize_entry( $entry );

			if ( empty( $next_entry['key'] ) ) {
				continue;
			}

			$normalized[ $next_entry['key'] ] = $next_entry;
		}

		return array_values( $normalized );
	}

	public static function get_entry( $key ): array {
		$key = self::normalize_help_key( $key );

		if ( '' === $key ) {
			return array();
		}

		foreach ( self::get_entries() as $entry ) {
			if ( $key === (string) $entry['key'] ) {
				return $entry;
			}
		}

		return array();
	}

	public static function normalize_entry( array $entry ): array {
		$key            = isset( $entry['key'] ) ? self::normalize_help_key( $entry['key'] ) : '';
		$category       = isset( $entry['category'] ) ? sanitize_key( (string) $entry['category'] ) : 'general';
		$section        = isset( $entry['section'] ) ? sanitize_key( (string) $entry['section'] ) : '';
		$source        = isset( $entry['source'] ) ? sanitize_key( (string) $entry['source'] ) : 'core';
		$module_key    = isset( $entry['module_key'] ) ? sanitize_key( (string) $entry['module_key'] ) : '';
		$editable      = ! empty( $entry['editable'] );
		$i18n_title_key = isset( $entry['i18n_title_key'] ) ? sanitize_text_field( (string) $entry['i18n_title_key'] ) : '';
		$i18n_body_key  = isset( $entry['i18n_body_key'] ) ? sanitize_text_field( (string) $entry['i18n_body_key'] ) : '';
		$title         = isset( $entry['title'] ) ? sanitize_text_field( (string) $entry['title'] ) : '';
		$body          = isset( $entry['body'] ) ? sanitize_textarea_field( (string) $entry['body'] ) : '';

		if ( '' === $title && '' !== $i18n_title_key ) {
			$title = self::text( $i18n_title_key );
		}

		if ( '' === $body && '' !== $i18n_body_key ) {
			$body = self::text( $i18n_body_key );
		}

		if ( '' === $key || '' === $title || '' === $body ) {
			return array();
		}

		return array(
			'key'            => $key,
			'title'          => $title,
			'body'           => $body,
			'category'       => '' !== $category ? $category : 'general',
			'section'        => $section,
			'source'         => '' !== $source ? $source : 'core',
			'module_key'     => $module_key,
			'editable'       => $editable,
			'i18n_title_key' => $i18n_title_key,
			'i18n_body_key'  => $i18n_body_key,
		);
	}
}
