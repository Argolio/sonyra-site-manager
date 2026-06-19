<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Sonyra_Site_Manager_Login_Renderer {

	private static function text( string $key ): string {
		if ( class_exists( 'Sonyra_Site_Manager_I18n' ) ) {
			return Sonyra_Site_Manager_I18n::t( $key );
		}

		return '';
	}

	private static function versioned_asset_url( string $url, string $path, string $query_arg = 'ver' ): string {
		if ( ! file_exists( $path ) ) {
			return '';
		}

		$asset_mtime = filemtime( $path );
		$asset_version = SONYRA_SITE_MANAGER_VERSION;

		if ( is_int( $asset_mtime ) ) {
			$asset_version .= '-' . $asset_mtime;
		}

		if ( function_exists( 'add_query_arg' ) ) {
			return add_query_arg( $query_arg, $asset_version, $url );
		}

		return $url . '?' . rawurlencode( $query_arg ) . '=' . rawurlencode( $asset_version );
	}

	private static function render_word_spans( string $text ): void {
		$words = preg_split( '/\s+/u', trim( $text ) );

		if ( ! is_array( $words ) || empty( $words ) ) {
			echo esc_html( $text );
			return;
		}

		$is_first_word = true;

		foreach ( $words as $word ) {
			if ( '' === $word ) {
				continue;
			}

			if ( ! $is_first_word ) {
				echo ' ';
			}

			echo '<span class="sonyra-login-title-word">' . esc_html( $word ) . '</span>';
			$is_first_word = false;
		}
	}

	private static function render_icon( string $name, string $class_name = 'sonyra-login-icon' ): void {
		$icon_keys = array(
			'mail-check'       => 'mail-check',
			'key-round'        => 'key',
			'check-check'      => 'checks',
			'lock-keyhole'     => 'lock',
			'circle-check-big' => 'circle-check',
			'check'            => 'check',
			'circle-alert'     => 'alert-circle',
			'clock-3'          => 'clock-hour-3',
			'info'             => 'info-circle',
			'close'            => 'x',
		);

		$icon_key = isset( $icon_keys[ $name ] ) ? $icon_keys[ $name ] : 'info-circle';

		if ( class_exists( 'Sonyra_Site_Manager_Icon_Renderer' ) ) {
			Sonyra_Site_Manager_Icon_Renderer::display(
				$icon_key,
				array(
					'class'       => $class_name,
					'aria_hidden' => true,
				)
			);
		}
	}

	private static function render_step_head( string $tag_name, string $title_key, string $subtitle_key, string $icon_name, string $id = '', string $extra_class = '' ): void {
		$tag_name = in_array( $tag_name, array( 'h1', 'h2' ), true ) ? $tag_name : 'h2';
		$id_attribute = '' !== $id ? ' id="' . esc_attr( $id ) . '"' : '';
		$class_name = trim( 'sonyra-login-title ' . $extra_class );
		?>
		<div class="sonyra-login-step-head">
			<div class="sonyra-login-step-icon-tile" aria-hidden="true">
				<?php self::render_icon( $icon_name, 'sonyra-login-icon sonyra-login-heading-icon' ); ?>
			</div>
			<div class="sonyra-login-step-copy">
				<p class="sonyra-login-kicker"><?php echo esc_html( self::text( 'login.kicker' ) ); ?></p>
				<<?php echo esc_html( $tag_name ); ?><?php echo $id_attribute; ?> class="<?php echo esc_attr( $class_name ); ?>"><?php echo esc_html( self::text( $title_key ) ); ?></<?php echo esc_html( $tag_name ); ?>>
				<p class="sonyra-login-lead"><?php echo esc_html( self::text( $subtitle_key ) ); ?></p>
			</div>
		</div>
		<?php
	}

	private static function get_request_code_config(): array {
		$rest_url = function_exists( 'rest_url' ) ? rest_url( 'sonyra-site-manager/v1/auth/request-code' ) : '';
		$verify_url = function_exists( 'rest_url' ) ? rest_url( 'sonyra-site-manager/v1/auth/verify-code' ) : '';
		$setup_device_code_url = function_exists( 'rest_url' ) ? rest_url( 'sonyra-site-manager/v1/auth/setup-device-code' ) : '';
		$device_code_login_url = function_exists( 'rest_url' ) ? rest_url( 'sonyra-site-manager/v1/auth/device-code-login' ) : '';
		$manager_url = function_exists( 'home_url' ) ? home_url( '/manager' ) : '/manager';
		$nonce = function_exists( 'wp_create_nonce' ) ? wp_create_nonce( 'wp_rest' ) : '';
		$initial_step = self::should_show_quick_login() ? 'quick-login' : 'identifier';

		return array(
			'restUrl'            => $rest_url,
			'verifyUrl'          => $verify_url,
			'setupDeviceCodeUrl' => $setup_device_code_url,
			'deviceCodeLoginUrl' => $device_code_login_url,
			'managerUrl'         => $manager_url,
			'nonce'              => $nonce,
			'initialStep'        => $initial_step,
			'messages'           => array(
				'loading'        => self::text( 'login.request_loading' ),
				'success'        => self::text( 'login.request_success' ),
				'error'          => self::text( 'login.request_error' ),
				'empty'          => self::text( 'login.request_empty' ),
				'verifyLoading'  => self::text( 'login.verify_loading' ),
				'verifySuccess'  => self::text( 'login.verify_success' ),
				'verifyError'    => self::text( 'login.verify_error' ),
				'verifyErrorHint' => self::text( 'login.verify_error_hint' ),
				'verifyEmpty'    => self::text( 'login.verify_empty' ),
				'verifyInvalid'  => self::text( 'login.verify_invalid' ),
				'verifyNotReady' => self::text( 'login.verify_not_ready' ),
				'verifyNotReadyHint' => self::text( 'login.verify_not_ready_hint' ),
				'verifyInvalidCode' => self::text( 'login.verify_invalid_code' ),
				'verifyInvalidCodeHint' => self::text( 'login.verify_invalid_code_hint' ),
				'verifyExpiredCode' => self::text( 'login.verify_expired_code' ),
				'verifyExpiredCodeHint' => self::text( 'login.verify_expired_code_hint' ),
				'verifyTooManyAttempts' => self::text( 'login.verify_too_many_attempts' ),
				'verifyTooManyAttemptsHint' => self::text( 'login.verify_too_many_attempts_hint' ),
				'successStatus'  => self::text( 'login.success_status' ),
				'requestSuccessHint' => self::text( 'login.request_success_hint' ),
				'requestErrorHint' => self::text( 'login.request_error_hint' ),
				'requestNeutralTitle' => self::text( 'login.request_neutral_title' ),
				'requestNeutralHint' => self::text( 'login.request_neutral_hint' ),
				'requestCooldownTitle' => self::text( 'login.request_cooldown_title' ),
				'requestCooldownHint' => self::text( 'login.request_cooldown_hint' ),
				'requestLimitTitle' => self::text( 'login.request_limit_title' ),
				'requestLimitHint' => self::text( 'login.request_limit_hint' ),
				'requestLockedTitle' => self::text( 'login.request_locked_title' ),
				'requestLockedHint' => self::text( 'login.request_locked_hint' ),
				'rememberDeviceFailedTitle' => self::text( 'login.remember_device_failed_title' ),
				'rememberDeviceFailedHint' => self::text( 'login.remember_device_failed_hint' ),
				'deviceCodeInvalidFormat' => self::text( 'login.device_code_invalid_format' ),
				'deviceCodeInvalidFormatHint' => self::text( 'login.device_code_invalid_format_hint' ),
				'deviceCodeMissing3' => self::text( 'login.device_code_missing_3' ),
				'deviceCodeMissing2' => self::text( 'login.device_code_missing_2' ),
				'deviceCodeMissing1' => self::text( 'login.device_code_missing_1' ),
				'deviceCodeMissingHint' => self::text( 'login.device_code_missing_hint' ),
				'deviceCodeEmptyHint' => self::text( 'login.device_code_empty_hint' ),
				'deviceCodeMismatch' => self::text( 'login.device_code_mismatch' ),
				'deviceCodeMismatchHint' => self::text( 'login.device_code_mismatch_hint' ),
				'deviceCodeInvalid' => self::text( 'login.device_code_invalid' ),
				'deviceCodeInvalidHint' => self::text( 'login.device_code_invalid_hint' ),
				'deviceCodeLocked' => self::text( 'login.device_code_locked' ),
				'deviceCodeLockedHint' => self::text( 'login.device_code_locked_hint' ),
				'deviceCodeUnavailable' => self::text( 'login.device_code_unavailable' ),
				'deviceCodeUnavailableHint' => self::text( 'login.device_code_unavailable_hint' ),
				'deviceCodeSetupFailed' => self::text( 'login.device_code_setup_failed' ),
				'deviceCodeSetupFailedHint' => self::text( 'login.device_code_setup_failed_hint' ),
				'deviceCodeSetupSavedStatus' => self::text( 'login.device_code_setup_success_status' ),
				'deviceCodeSetupSavedLead' => self::text( 'login.device_code_setup_success_lead' ),
				'deviceCodeSetupResultTitle' => self::text( 'login.device_code_setup_result_title' ),
				'deviceCodeSetupResultHint' => self::text( 'login.device_code_setup_result_hint' ),
				'deviceCodeSetupContinue' => self::text( 'login.device_code_setup_continue' ),
				'quickLoginLoading' => self::text( 'login.device_code_login_loading' ),
				'quickLoginSuccess' => self::text( 'login.device_code_login_success' ),
				'quickLoginSuccessHint' => self::text( 'login.device_code_login_success_hint' ),
				'sessionLockedTitle' => self::text( 'login.session_locked_title' ),
				'sessionLockedHint' => self::text( 'login.session_locked_hint' ),
				'setupLoading' => self::text( 'login.device_code_setup_loading' ),
				'managerOpenError' => self::text( 'login.manager_open_error' ),
				'managerOpenErrorHint' => self::text( 'login.manager_open_error_hint' ),
				'neutralStatusLine1' => self::text( 'login.neutral_status_line_1' ),
				'neutralStatusLine2' => self::text( 'login.neutral_status_line_2' ),
			),
		);
	}

	private static function should_show_quick_login(): bool {
		if ( ! class_exists( 'Sonyra_Site_Manager_Auth_Cookie' ) || ! class_exists( 'Sonyra_Site_Manager_Auth_Trusted_Devices' ) ) {
			return false;
		}

		$session_result = Sonyra_Site_Manager_Auth_Cookie::validate_cookie();

		if ( ! empty( $session_result['valid'] ) ) {
			return false;
		}

		$device_status = Sonyra_Site_Manager_Auth_Trusted_Devices::get_current_device_login_status();

		return ! empty( $device_status['available'] ) && empty( $device_status['locked'] );
	}

	private static function get_initial_login_step(): string {
		if ( isset( $_GET['sonyra_login_preview'] ) ) {
			return self::get_preview_state();
		}

		return self::should_show_quick_login() ? 'quick-login' : 'identifier';
	}

	private static function render_request_code_config(): void {
		$config = self::get_request_code_config();
		$json_options = 0;

		if ( defined( 'JSON_HEX_TAG' ) ) {
			$json_options = JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT;
		}

		$encoded = function_exists( 'wp_json_encode' ) ? wp_json_encode( $config, $json_options ) : json_encode( $config, $json_options );

		if ( ! is_string( $encoded ) ) {
			$encoded = '{}';
		}
		?>
		<script type="application/json" id="sonyra-login-request-code-config"><?php echo $encoded; ?></script>
		<?php
	}

	private static function render_runtime_icon_templates(): void {
		$icons = array(
			'neutral' => 'info-circle',
			'success' => 'circle-check',
			'warning' => 'clock-hour-3',
			'error'   => 'alert-circle',
		);
		?>
		<div class="sonyra-login-icon-templates" hidden aria-hidden="true">
			<?php foreach ( $icons as $state => $icon_key ) : ?>
				<template id="sonyra-login-status-icon-template-<?php echo esc_attr( $state ); ?>">
					<?php
					if ( class_exists( 'Sonyra_Site_Manager_Icon_Renderer' ) ) {
						Sonyra_Site_Manager_Icon_Renderer::display(
							$icon_key,
							array(
								'class'       => 'sonyra-login-icon sonyra-login-status-icon',
								'aria_hidden' => true,
							)
						);
					}
					?>
				</template>
			<?php endforeach; ?>
		</div>
		<?php
	}

	private static function render_request_code_script(): void {
		?>
		<script>
			(function () {
				'use strict';

				function setStatus(statusElement, message, hint, state) {
					if (!statusElement) {
						return;
					}

					if (!message && !hint) {
						statusElement.hidden = true;
						statusElement.textContent = '';
						return;
					}

					statusElement.hidden = false;
					statusElement.classList.remove('sonyra-login-status-neutral', 'sonyra-login-status-success', 'sonyra-login-status-warning', 'sonyra-login-status-error');
					statusElement.classList.add('sonyra-login-status-' + (state === 'success' || state === 'warning' || state === 'error' ? state : 'neutral'));
					statusElement.textContent = '';

					if (message || hint) {
						statusElement.appendChild(createStatusIcon(state));
					}

					if (message) {
						var messageElement = document.createElement('span');
						messageElement.className = 'sonyra-login-status-message';
						messageElement.textContent = message;
						statusElement.appendChild(messageElement);
					}

					if (hint) {
						var hintElement = document.createElement('span');
						hintElement.className = 'sonyra-login-status-hint';
						hintElement.textContent = hint;
						statusElement.appendChild(hintElement);
					}
				}

				function setEmailInfoStatus(statusElement, messages) {
					setStatus(statusElement, messages.neutralStatusLine1 || '', messages.neutralStatusLine2 || '', 'neutral');
				}

				function createStatusIcon(state) {
					var normalizedState = state === 'success' || state === 'warning' || state === 'error' ? state : 'neutral';
					var template = document.getElementById('sonyra-login-status-icon-template-' + normalizedState) || document.getElementById('sonyra-login-status-icon-template-neutral');
					var icon = null;

					if (template && template.content && template.content.firstElementChild) {
						icon = template.content.firstElementChild.cloneNode(true);
					}

					if (!icon) {
						icon = document.createElement('span');
						icon.hidden = true;
					}

					return icon;
				}

				function getConfig() {
					var configElement = document.getElementById('sonyra-login-request-code-config');

					if (!configElement) {
						return {};
					}

					try {
						return JSON.parse(configElement.textContent || '{}');
					} catch (error) {
						return {};
					}
				}

				function readPath(data, key) {
					if (!data) {
						return '';
					}

					if (data[key]) {
						return String(data[key]);
					}

					if (data.data && data.data[key]) {
						return String(data.data[key]);
					}

					if (data.result && data.result[key]) {
						return String(data.result[key]);
					}

					return '';
				}

				function getChallengeUuid(data) {
					return readPath(data, 'challenge_uuid');
				}

				function getErrorCode(data) {
					return readPath(data, 'reason_code') || readPath(data, 'error_code') || readPath(data, 'code');
				}

				function getRequestStatusMessage(data, messages, hasChallenge) {
					var reasonCode = getErrorCode(data);
					var title = readPath(data, 'message');
					var hint = readPath(data, 'hint');

					if (hasChallenge) {
						return { title: title || messages.success || '', hint: hint || messages.requestSuccessHint || '', state: 'success' };
					}

					if (reasonCode === 'cooldown') {
						return { title: title || messages.requestCooldownTitle || '', hint: hint || messages.requestCooldownHint || '', state: 'warning' };
					}

					if (reasonCode === 'send_window_limit') {
						return { title: title || messages.requestLimitTitle || '', hint: hint || messages.requestLimitHint || '', state: 'warning' };
					}

					if (reasonCode === 'locked') {
						return { title: title || messages.requestLockedTitle || '', hint: hint || messages.requestLockedHint || '', state: 'warning' };
					}

					if (reasonCode === 'technical') {
						return { title: title || messages.error || '', hint: hint || messages.requestErrorHint || '', state: 'error' };
					}

					return { title: title || messages.requestNeutralTitle || '', hint: hint || messages.requestNeutralHint || '', state: 'neutral' };
				}

				function getVerifyErrorMessage(errorData, messages) {
					var errorCode = getErrorCode(errorData);

					if (errorCode === 'invalid_code') {
						return { title: messages.verifyInvalidCode || '', hint: messages.verifyInvalidCodeHint || '' };
					}

					if (errorCode === 'expired_code') {
						return { title: messages.verifyExpiredCode || '', hint: messages.verifyExpiredCodeHint || '' };
					}

					if (errorCode === 'too_many_attempts') {
						return { title: messages.verifyTooManyAttempts || '', hint: messages.verifyTooManyAttemptsHint || '' };
					}

					return { title: messages.verifyError || '', hint: messages.verifyErrorHint || '' };
				}

				function getDeviceCodeErrorMessage(errorData, messages) {
					var errorCode = getErrorCode(errorData);
					var title = readPath(errorData, 'message');
					var hint = readPath(errorData, 'hint');

					if (errorCode === 'invalid_format') {
						return { title: title || messages.deviceCodeInvalidFormat || '', hint: hint || messages.deviceCodeInvalidFormatHint || '' };
					}

					if (errorCode === 'mismatch') {
						return { title: title || messages.deviceCodeMismatch || '', hint: hint || messages.deviceCodeMismatchHint || '' };
					}

					if (errorCode === 'invalid_code') {
						return { title: title || messages.deviceCodeInvalid || '', hint: hint || messages.deviceCodeInvalidHint || '' };
					}

					if (errorCode === 'locked') {
						return { title: title || messages.deviceCodeLocked || '', hint: hint || messages.deviceCodeLockedHint || '' };
					}

					if (errorCode === 'device_unavailable') {
						return { title: title || messages.deviceCodeUnavailable || '', hint: hint || messages.deviceCodeUnavailableHint || '' };
					}

					return { title: title || messages.deviceCodeSetupFailed || '', hint: hint || messages.deviceCodeSetupFailedHint || '' };
				}

				function getIncompleteDeviceCodeMessage(code, messages) {
					var digitsCount = (code || '').replace(/\D+/g, '').slice(0, 4).length;
					var missingCount = Math.max(0, 4 - digitsCount);

					if (missingCount === 3) {
						return { title: messages.deviceCodeMissing3 || '', hint: messages.deviceCodeMissingHint || '' };
					}

					if (missingCount === 2) {
						return { title: messages.deviceCodeMissing2 || '', hint: messages.deviceCodeMissingHint || '' };
					}

					if (missingCount === 1) {
						return { title: messages.deviceCodeMissing1 || '', hint: messages.deviceCodeMissingHint || '' };
					}

					return { title: messages.deviceCodeInvalidFormat || '', hint: messages.deviceCodeEmptyHint || '' };
				}

				function showStep(steps, name) {
					steps.forEach(function (step) {
						step.hidden = step.getAttribute('data-sonyra-login-step') !== name;
					});
				}

				function getCodeFromCells(codeCells) {
					return getDigitsValue(codeCells);
				}

				function getDigitsValue(codeCells) {
					return codeCells.map(function (cell) {
						return (cell.value || '').replace(/\D+/g, '').slice(0, 1);
					}).join('');
				}

				function submitForm(form) {
					if (!form) {
						return;
					}

					if (typeof form.requestSubmit === 'function') {
						form.requestSubmit();
						return;
					}

					form.dispatchEvent(new Event('submit', { bubbles: true, cancelable: true }));
				}

				function clearCells(codeCells) {
					codeCells.forEach(function (cell) {
						cell.value = '';
						cell.classList.remove('sonyra-login-code-cell-filled');
					});
					setCodeCellsState(codeCells, '');
				}

				function setCodeCellsState(codeCells, state) {
					if (!codeCells[0]) {
						return;
					}

					var group = codeCells[0].closest('.sonyra-login-code-cells');

					if (!group) {
						return;
					}

					group.classList.remove('sonyra-login-code-cells-error', 'sonyra-login-code-cells-success');

					if (state === 'error' || state === 'success') {
						group.classList.add('sonyra-login-code-cells-' + state);
					}
				}

				function fillCodeCells(codeCells, value, startIndex) {
					var digits = (value || '').replace(/\D+/g, '').slice(0, codeCells.length);
					var index = Math.max(0, startIndex || 0);

					digits.split('').forEach(function (digit) {
						if (codeCells[index]) {
							codeCells[index].value = digit;
							codeCells[index].classList.toggle('sonyra-login-code-cell-filled', digit !== '');
							index += 1;
						}
					});

					if (codeCells[Math.min(index, codeCells.length - 1)]) {
						codeCells[Math.min(index, codeCells.length - 1)].focus();
					}
				}

				function bindDigitCells(codeCells) {
					codeCells.forEach(function (cell, index) {
						cell.addEventListener('input', function () {
							var digits = (cell.value || '').replace(/\D+/g, '');
							cell.value = '';
							cell.classList.remove('sonyra-login-code-cell-filled');
							setCodeCellsState(codeCells, '');
							fillCodeCells(codeCells, digits, index);
						});

						cell.addEventListener('paste', function (event) {
							event.preventDefault();
							fillCodeCells(codeCells, event.clipboardData ? event.clipboardData.getData('text') : '', index);
						});

						cell.addEventListener('keydown', function (event) {
							if (event.key === 'Backspace' && !cell.value && codeCells[index - 1]) {
								event.preventDefault();
								codeCells[index - 1].value = '';
								codeCells[index - 1].classList.remove('sonyra-login-code-cell-filled');
								setCodeCellsState(codeCells, '');
								codeCells[index - 1].focus();
							}

							if (event.key === 'ArrowLeft' && codeCells[index - 1]) {
								event.preventDefault();
								codeCells[index - 1].focus();
							}

							if (event.key === 'ArrowRight' && codeCells[index + 1]) {
								event.preventDefault();
								codeCells[index + 1].focus();
							}
						});
					});
				}

				function submitWhenComplete(codeCells, expectedLength, form, isSubmitting) {
					var expectedDigitsPattern = new RegExp('^\\d{' + expectedLength + '}$');

					if (!form || !expectedDigitsPattern.test(getDigitsValue(codeCells))) {
						return;
					}

					if (typeof isSubmitting === 'function' && isSubmitting()) {
						return;
					}

					window.setTimeout(function () {
						if (typeof isSubmitting === 'function' && isSubmitting()) {
							return;
						}

						if (!expectedDigitsPattern.test(getDigitsValue(codeCells))) {
							return;
						}

						submitForm(form);
					}, 0);
				}

				function bindAutoSubmit(codeCells, expectedLength, form, isSubmitting) {
					if (!form || !codeCells.length) {
						return;
					}

					codeCells.forEach(function (cell) {
						['input', 'paste', 'change'].forEach(function (eventName) {
							cell.addEventListener(eventName, function () {
								submitWhenComplete(codeCells, expectedLength, form, isSubmitting);
							});
						});
					});
				}

				function updateSuccess(successTitle, successLead, title, lead) {
					if (successTitle && title) {
						successTitle.textContent = title;
					}

					if (successLead && lead) {
						successLead.textContent = lead;
					}
				}

				function redirectToManager(loginConfig, statusElement, messages) {
					var managerUrl = loginConfig && typeof loginConfig.managerUrl === 'string' ? loginConfig.managerUrl : '';
					var parsedUrl;

					if (!managerUrl) {
						managerUrl = '/manager';
					}

					try {
						parsedUrl = new URL(managerUrl, window.location.origin);
						managerUrl = parsedUrl.origin === window.location.origin ? parsedUrl.href : '/manager';
					} catch (error) {
						managerUrl = '/manager';
					}

					window.setTimeout(function () {
						try {
							window.location.assign(managerUrl);
						} catch (error) {
							setStatus(statusElement, messages.managerOpenError || '', messages.managerOpenErrorHint || '', 'error');
						}
					}, 900);
				}

				document.addEventListener('DOMContentLoaded', function () {
					var steps = Array.prototype.slice.call(document.querySelectorAll('[data-sonyra-login-step]'));
					var form = document.querySelector('[data-sonyra-login-request-form]');
					var identifierInput = document.querySelector('[data-sonyra-login-identifier]');
					var submitButton = document.querySelector('[data-sonyra-login-request-button]');
					var statusElement = document.querySelector('[data-sonyra-login-status]');
					var verifyForm = document.querySelector('[data-sonyra-login-verify-form]');
					var codeCells = Array.prototype.slice.call(document.querySelectorAll('[data-sonyra-login-code-cell]'));
					var setupForm = document.querySelector('[data-sonyra-login-setup-code-form]');
					var setupConfirmForm = document.querySelector('[data-sonyra-login-setup-confirm-form]');
					var setupCodeCells = Array.prototype.slice.call(document.querySelectorAll('[data-sonyra-login-setup-code-cell]'));
					var setupConfirmCells = Array.prototype.slice.call(document.querySelectorAll('[data-sonyra-login-setup-confirm-cell]'));
					var setupButton = document.querySelector('[data-sonyra-login-setup-code-button]');
					var setupConfirmButton = document.querySelector('[data-sonyra-login-setup-confirm-button]');
					var setupSkipButtons = Array.prototype.slice.call(document.querySelectorAll('[data-sonyra-login-setup-skip]'));
					var setupChangeFirstButton = document.querySelector('[data-sonyra-login-setup-change-first]');
					var quickForm = document.querySelector('[data-sonyra-login-quick-form]');
					var quickCodeCells = Array.prototype.slice.call(document.querySelectorAll('[data-sonyra-login-quick-code-cell]'));
					var quickButton = document.querySelector('[data-sonyra-login-quick-button]');
					var quickAlternativeButton = document.querySelector('[data-sonyra-login-quick-alternative]');
					var verifyButton = document.querySelector('[data-sonyra-login-verify-button]');
					var rememberDeviceSwitch = document.querySelector('[data-sonyra-login-remember-switch]');
					var rememberDevicePanel = document.querySelector('[data-sonyra-login-remember-panel]');
					var rememberDeviceInfoButton = document.querySelector('[data-sonyra-login-remember-info]');
					var rememberDeviceTooltip = document.querySelector('[data-sonyra-login-remember-tooltip]');
					var rememberDeviceCloseButton = document.querySelector('[data-sonyra-login-remember-close]');
					var rememberDeviceBackdrop = document.querySelector('[data-sonyra-login-remember-backdrop]');
					var changeIdentifierButton = document.querySelector('[data-sonyra-login-change-identifier]');
					var successTitle = document.querySelector('[data-sonyra-login-success-title]');
					var successLead = document.querySelector('[data-sonyra-login-success-lead]');
					var loginConfig = getConfig();
					var messages = loginConfig.messages || {};
					var buttonText = submitButton ? submitButton.textContent : '';
					var verifyButtonText = verifyButton ? verifyButton.textContent : '';
					var setupButtonText = setupButton ? setupButton.textContent : '';
					var setupConfirmButtonText = setupConfirmButton ? setupConfirmButton.textContent : '';
					var quickButtonText = quickButton ? quickButton.textContent : '';
					var defaultSuccessTitle = successTitle ? successTitle.textContent : '';
					var defaultSuccessLead = successLead ? successLead.textContent : '';
					var requestChallengeUuid = '';
					var rememberDevice = false;
					var setupFirstCode = '';
					var rememberTooltipCloseTimer = null;
					var verifySubmitting = false;
					var quickSubmitting = false;

					if (!form || !identifierInput || !submitButton || !statusElement || !loginConfig.restUrl) {
						return;
					}

					bindDigitCells(codeCells);
					bindDigitCells(setupCodeCells);
					bindDigitCells(setupConfirmCells);
					bindDigitCells(quickCodeCells);
					bindAutoSubmit(codeCells, 6, verifyForm, function () { return verifySubmitting; });
					bindAutoSubmit(quickCodeCells, 4, quickForm, function () { return quickSubmitting; });

					try {
						var loginNotice = new URLSearchParams(window.location.search || '').get('sonyra_login_notice') || '';

						if (loginNotice === 'session_locked') {
							setStatus(statusElement, messages.sessionLockedTitle || '', messages.sessionLockedHint || '', 'warning');
						}
					} catch (error) {}

					function setRememberDeviceState(isChecked) {
						rememberDevice = isChecked === true;

						if (rememberDeviceSwitch) {
							rememberDeviceSwitch.setAttribute('aria-checked', rememberDevice ? 'true' : 'false');
							rememberDeviceSwitch.classList.toggle('sonyra-login-switch-checked', rememberDevice);
						}

						if (rememberDevicePanel) {
							rememberDevicePanel.classList.toggle('sonyra-login-switch-panel-checked', rememberDevice);
						}
					}

					function setRememberTooltipOpen(isOpen) {
						if (!rememberDeviceInfoButton || !rememberDeviceTooltip) {
							return;
						}

						rememberDeviceInfoButton.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
						rememberDeviceTooltip.hidden = isOpen !== true;

						if (rememberDeviceBackdrop) {
							rememberDeviceBackdrop.hidden = isOpen !== true;
						}

						if (rememberDevicePanel) {
							rememberDevicePanel.classList.toggle('sonyra-login-switch-panel-tooltip-open', isOpen === true);
						}
					}

					function closeRememberTooltip() {
						setRememberTooltipOpen(false);
					}

					function cancelRememberTooltipClose() {
						if (rememberTooltipCloseTimer) {
							window.clearTimeout(rememberTooltipCloseTimer);
							rememberTooltipCloseTimer = null;
						}
					}

					function scheduleRememberTooltipClose() {
						cancelRememberTooltipClose();
						rememberTooltipCloseTimer = window.setTimeout(closeRememberTooltip, 150);
					}

					if (rememberDeviceSwitch) {
						setRememberDeviceState(false);
						rememberDeviceSwitch.addEventListener('click', function () { setRememberDeviceState(!rememberDevice); });
						rememberDeviceSwitch.addEventListener('keydown', function (event) {
							if (event.key === ' ' || event.key === 'Enter') {
								event.preventDefault();
								setRememberDeviceState(!rememberDevice);
							}
						});
					}

					if (rememberDeviceInfoButton && rememberDeviceTooltip) {
						setRememberTooltipOpen(false);
						rememberDeviceInfoButton.addEventListener('mouseenter', function () {
							cancelRememberTooltipClose();
							setRememberTooltipOpen(true);
						});
						rememberDeviceInfoButton.addEventListener('focus', function () {
							cancelRememberTooltipClose();
							setRememberTooltipOpen(true);
						});
						rememberDeviceInfoButton.addEventListener('mouseleave', scheduleRememberTooltipClose);
						rememberDeviceInfoButton.addEventListener('click', function (event) {
							event.preventDefault();
							event.stopPropagation();
							cancelRememberTooltipClose();
							setRememberTooltipOpen(rememberDeviceTooltip.hidden === true);
						});
					}

					if (rememberDevicePanel && rememberDeviceTooltip) {
						rememberDeviceTooltip.addEventListener('mouseenter', cancelRememberTooltipClose);
						rememberDeviceTooltip.addEventListener('mouseleave', scheduleRememberTooltipClose);
						rememberDevicePanel.addEventListener('focusout', function (event) {
							if (!rememberDevicePanel.contains(event.relatedTarget)) {
								scheduleRememberTooltipClose();
							}
						});
					}

					if (rememberDeviceCloseButton) {
						rememberDeviceCloseButton.addEventListener('click', function (event) {
							event.preventDefault();
							event.stopPropagation();
							closeRememberTooltip();
							if (rememberDeviceInfoButton) {
								rememberDeviceInfoButton.focus();
							}
						});
					}

					if (rememberDeviceBackdrop) {
						rememberDeviceBackdrop.addEventListener('click', closeRememberTooltip);
					}

					document.addEventListener('keydown', function (event) {
						if (event.key === 'Escape') {
							closeRememberTooltip();
						}
					});

					document.addEventListener('click', function (event) {
						if (rememberDevicePanel && !rememberDevicePanel.contains(event.target)) {
							closeRememberTooltip();
						}
					});

					if (loginConfig.initialStep === 'quick-login' && quickCodeCells[0]) {
						showStep(steps, 'quick-login');
						setStatus(statusElement, '', '', 'neutral');
						quickCodeCells[0].focus();
					} else {
						setEmailInfoStatus(statusElement, messages);
					}

					form.addEventListener('submit', function (event) {
						event.preventDefault();
						requestChallengeUuid = '';
						clearCells(codeCells);

						var identifier = (identifierInput.value || '').trim();

						if (!identifier) {
							setStatus(statusElement, messages.empty || '');
							identifierInput.focus();
							return;
						}

						submitButton.disabled = true;
						submitButton.textContent = messages.loading || buttonText;
						setStatus(statusElement, messages.loading || '', '', 'neutral');

						fetch(loginConfig.restUrl, {
							method: 'POST',
							credentials: 'same-origin',
							headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': loginConfig.nonce || '' },
							body: JSON.stringify({ email: identifier, identifier: identifier })
						}).then(function (response) {
							if (!response.ok) {
								throw new Error('request_failed');
							}

							return response.json();
						}).then(function (data) {
							if (!data || data.success === false) {
								throw new Error('request_failed');
							}

							requestChallengeUuid = getChallengeUuid(data);
							var requestStatus = getRequestStatusMessage(data, messages, !!requestChallengeUuid);
							setStatus(statusElement, requestStatus.title, requestStatus.hint, requestStatus.state);

							if (requestChallengeUuid) {
								showStep(steps, 'verify');
								if (codeCells[0]) {
									codeCells[0].focus();
								}
							}
						}).catch(function () {
							setStatus(statusElement, messages.error || '', messages.requestErrorHint || '', 'error');
						}).finally(function () {
							submitButton.disabled = false;
							submitButton.textContent = buttonText;
						});
					});

					if (changeIdentifierButton) {
						changeIdentifierButton.addEventListener('click', function () {
							requestChallengeUuid = '';
							clearCells(codeCells);
							showStep(steps, 'identifier');
							setEmailInfoStatus(statusElement, messages);
							identifierInput.focus();
						});
					}

					if (quickAlternativeButton) {
						quickAlternativeButton.addEventListener('click', function () {
							clearCells(quickCodeCells);
							showStep(steps, 'identifier');
							setEmailInfoStatus(statusElement, messages);
							identifierInput.focus();
						});
					}

					if (verifyForm && codeCells.length === 6 && verifyButton && loginConfig.verifyUrl) {
						verifyForm.addEventListener('submit', function (event) {
							event.preventDefault();

							if (verifySubmitting) {
								return;
							}

							var code = getCodeFromCells(codeCells);

							if (!code) {
								setStatus(statusElement, messages.verifyEmpty || '');
								codeCells[0].focus();
								return;
							}

							if (!/^\d{6}$/.test(code)) {
								setStatus(statusElement, messages.verifyInvalid || '');
								(codeCells.find(function (cell) { return !cell.value; }) || codeCells[0]).focus();
								return;
							}

							if (!requestChallengeUuid) {
								setStatus(statusElement, messages.verifyNotReady || '', messages.verifyNotReadyHint || '', 'warning');
								return;
							}

							verifySubmitting = true;
							verifyButton.disabled = true;
							verifyButton.textContent = messages.verifyLoading || verifyButtonText;
							setStatus(statusElement, messages.verifyLoading || '', '', 'neutral');

							fetch(loginConfig.verifyUrl, {
								method: 'POST',
								credentials: 'same-origin',
								headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': loginConfig.nonce || '' },
								body: JSON.stringify({ challenge_uuid: requestChallengeUuid, code: code, remember_device: rememberDevice === true })
							}).then(function (response) {
								return response.json().catch(function () { return {}; }).then(function (data) {
									data = data || {};
									data.responseOk = response.ok;
									return data;
								});
							}).then(function (data) {
								if (!data || data.responseOk !== true || data.authenticated !== true) {
									throw data || {};
								}

								if (data.next_step === 'setup_device_code' || data.device_code_setup_required === true) {
									setStatus(statusElement, readPath(data, 'message') || messages.verifySuccess || '', '', 'success');
									showStep(steps, 'setup-device-code');
									if (setupCodeCells[0]) {
										setupCodeCells[0].focus();
									}
									return;
								}

								if (data.device_remember_failed === true) {
									setStatus(statusElement, readPath(data, 'message') || messages.rememberDeviceFailedTitle || '', readPath(data, 'hint') || messages.rememberDeviceFailedHint || '', 'warning');
								} else {
								setStatus(statusElement, messages.successStatus || '', '', 'success');
								}

								updateSuccess(successTitle, successLead, defaultSuccessTitle, defaultSuccessLead);
								showStep(steps, 'success');
								redirectToManager(loginConfig, statusElement, messages);
							}).catch(function (errorData) {
								var errorMessage = getVerifyErrorMessage(errorData, messages);
								setStatus(statusElement, errorMessage.title, errorMessage.hint, 'error');
							}).finally(function () {
								verifySubmitting = false;
								verifyButton.disabled = false;
								verifyButton.textContent = verifyButtonText;
							});
						});
					}

					if (setupForm && setupButton) {
						setupForm.addEventListener('submit', function (event) {
							event.preventDefault();

							var setupCode = getCodeFromCells(setupCodeCells);

							if (!/^\d{4}$/.test(setupCode)) {
								var setupCodeMessage = getIncompleteDeviceCodeMessage(setupCode, messages);
								setCodeCellsState(setupCodeCells, 'error');
								setStatus(statusElement, setupCodeMessage.title, setupCodeMessage.hint, 'error');
								(setupCodeCells.find(function (cell) { return !cell.value; }) || setupCodeCells[0]).focus();
								return;
							}

							setupFirstCode = setupCode;
							clearCells(setupConfirmCells);
							setCodeCellsState(setupCodeCells, 'success');
							setStatus(statusElement, messages.deviceCodeSetupContinue || '', '', 'success');
							showStep(steps, 'setup-device-confirm');

							if (setupConfirmCells[0]) {
								setupConfirmCells[0].focus();
							}
						});
					}

					if (setupConfirmForm && setupConfirmButton && loginConfig.setupDeviceCodeUrl) {
						setupConfirmForm.addEventListener('submit', function (event) {
							event.preventDefault();

							var setupConfirm = getCodeFromCells(setupConfirmCells);

							if (!/^\d{4}$/.test(setupFirstCode)) {
								clearCells(setupCodeCells);
								clearCells(setupConfirmCells);
								showStep(steps, 'setup-device-code');
								if (setupCodeCells[0]) {
									setupCodeCells[0].focus();
								}
								return;
							}

							if (!/^\d{4}$/.test(setupConfirm)) {
								var setupConfirmMessage = getIncompleteDeviceCodeMessage(setupConfirm, messages);
								setCodeCellsState(setupConfirmCells, 'error');
								setStatus(statusElement, setupConfirmMessage.title, setupConfirmMessage.hint, 'error');
								(setupConfirmCells.find(function (cell) { return !cell.value; }) || setupConfirmCells[0]).focus();
								return;
							}

							if (setupFirstCode !== setupConfirm) {
								setStatus(statusElement, messages.deviceCodeMismatch || '', messages.deviceCodeMismatchHint || '', 'error');
								setupConfirmCells.forEach(function (cell) {
									cell.value = '';
									cell.classList.remove('sonyra-login-code-cell-filled');
								});
								setCodeCellsState(setupConfirmCells, 'error');
								if (setupConfirmCells[0]) {
									setupConfirmCells[0].focus();
								}
								return;
							}

							setupConfirmButton.disabled = true;
							setupConfirmButton.textContent = messages.setupLoading || setupConfirmButtonText;
							setStatus(statusElement, messages.setupLoading || '', '', 'neutral');

							fetch(loginConfig.setupDeviceCodeUrl, {
								method: 'POST',
								credentials: 'same-origin',
								headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': loginConfig.nonce || '' },
								body: JSON.stringify({ code: setupFirstCode, code_confirm: setupConfirm })
							}).then(function (response) {
								return response.json().catch(function () { return {}; }).then(function (data) {
									data = data || {};
									data.responseOk = response.ok;
									return data;
								});
							}).then(function (data) {
								if (!data || data.responseOk !== true || data.success !== true) {
									throw data || {};
								}

								setupFirstCode = '';
								updateSuccess(successTitle, successLead, messages.deviceCodeSetupSavedStatus || '', messages.deviceCodeSetupSavedLead || '');
								setCodeCellsState(setupConfirmCells, 'success');
								setStatus(statusElement, messages.deviceCodeSetupResultTitle || '', messages.deviceCodeSetupResultHint || '', 'success');
								showStep(steps, 'success');
								redirectToManager(loginConfig, statusElement, messages);
							}).catch(function (errorData) {
								var errorMessage = getDeviceCodeErrorMessage(errorData, messages);
								setCodeCellsState(setupConfirmCells, 'error');
								setStatus(statusElement, errorMessage.title, errorMessage.hint, 'error');
							}).finally(function () {
								setupConfirmButton.disabled = false;
								setupConfirmButton.textContent = setupConfirmButtonText;
							});
						});
					}

					if (setupChangeFirstButton) {
						setupChangeFirstButton.addEventListener('click', function () {
							setupFirstCode = '';
							clearCells(setupCodeCells);
							clearCells(setupConfirmCells);
							setStatus(statusElement, '', '', 'neutral');
							showStep(steps, 'setup-device-code');
							if (setupCodeCells[0]) {
								setupCodeCells[0].focus();
							}
						});
					}

					setupSkipButtons.forEach(function (setupSkipButton) {
						setupSkipButton.addEventListener('click', function () {
							setupFirstCode = '';
							clearCells(setupCodeCells);
							clearCells(setupConfirmCells);
							updateSuccess(successTitle, successLead, defaultSuccessTitle, defaultSuccessLead);
							setStatus(statusElement, messages.successStatus || '', '', 'success');
							showStep(steps, 'success');
							redirectToManager(loginConfig, statusElement, messages);
						});
					});

					if (quickForm && quickButton && loginConfig.deviceCodeLoginUrl) {
						quickForm.addEventListener('submit', function (event) {
							event.preventDefault();

							if (quickSubmitting) {
								return;
							}

							var quickCode = getCodeFromCells(quickCodeCells);

							if (!/^\d{4}$/.test(quickCode)) {
								var quickCodeMessage = getIncompleteDeviceCodeMessage(quickCode, messages);
								setCodeCellsState(quickCodeCells, 'error');
								setStatus(statusElement, quickCodeMessage.title, quickCodeMessage.hint, 'error');
								(quickCodeCells.find(function (cell) { return !cell.value; }) || quickCodeCells[0]).focus();
								return;
							}

							quickSubmitting = true;
							quickButton.disabled = true;
							quickButton.textContent = messages.quickLoginLoading || quickButtonText;
							setStatus(statusElement, messages.quickLoginLoading || '', '', 'neutral');

							fetch(loginConfig.deviceCodeLoginUrl, {
								method: 'POST',
								credentials: 'same-origin',
								headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': loginConfig.nonce || '' },
								body: JSON.stringify({ code: quickCode })
							}).then(function (response) {
								return response.json().catch(function () { return {}; }).then(function (data) {
									data = data || {};
									data.responseOk = response.ok;
									return data;
								});
							}).then(function (data) {
								if (!data || data.responseOk !== true || data.authenticated !== true) {
									throw data || {};
								}

								updateSuccess(successTitle, successLead, messages.quickLoginSuccess || '', messages.quickLoginSuccessHint || '');
								setCodeCellsState(quickCodeCells, 'success');
								setStatus(statusElement, readPath(data, 'message') || messages.quickLoginSuccess || '', readPath(data, 'hint') || messages.quickLoginSuccessHint || '', 'success');
								showStep(steps, 'success');
								redirectToManager(loginConfig, statusElement, messages);
							}).catch(function (errorData) {
								var errorMessage = getDeviceCodeErrorMessage(errorData, messages);
								setCodeCellsState(quickCodeCells, 'error');
								setStatus(statusElement, errorMessage.title, errorMessage.hint, 'error');
							}).finally(function () {
								quickSubmitting = false;
								quickButton.disabled = false;
								quickButton.textContent = quickButtonText;
							});
						});
					}
				});
			}());
		</script>
		<?php
	}

	public static function get_preview_state(): string {
		$state = 'identifier';

		if ( isset( $_GET['sonyra_login_preview'] ) ) {
			$state = sanitize_key( wp_unslash( $_GET['sonyra_login_preview'] ) );
		}

		$allowed_states = array( 'identifier', 'code', 'success', 'error', 'quick-login', 'verify', 'setup-device-code', 'setup-device-confirm' );

		if ( ! in_array( $state, $allowed_states, true ) ) {
			return 'identifier';
		}

		return $state;
	}

	public static function render_placeholder(): void {
		self::render_login_shell();
	}

	public static function render_manager_placeholder(): void {
		$title = self::text( 'manager.placeholder_title' );
		$css_url  = plugins_url( 'assets/css/sonyra-login.css', SONYRA_SITE_MANAGER_FILE );
		$logo_path = plugin_dir_path( SONYRA_SITE_MANAGER_FILE ) . 'assets/images/brand/pult-site-logo.png';
		$logo_url  = plugins_url( 'assets/images/brand/pult-site-logo.png', SONYRA_SITE_MANAGER_FILE );
		$favicon_ico_path = plugin_dir_path( SONYRA_SITE_MANAGER_FILE ) . 'assets/images/brand/pult-site-favicon.ico';
		$favicon_ico_url  = plugins_url( 'assets/images/brand/pult-site-favicon.ico', SONYRA_SITE_MANAGER_FILE );
		$favicon_32_path = plugin_dir_path( SONYRA_SITE_MANAGER_FILE ) . 'assets/images/brand/pult-site-favicon-32.png';
		$favicon_32_url  = plugins_url( 'assets/images/brand/pult-site-favicon-32.png', SONYRA_SITE_MANAGER_FILE );
		$favicon_192_path = plugin_dir_path( SONYRA_SITE_MANAGER_FILE ) . 'assets/images/brand/pult-site-favicon-192.png';
		$favicon_192_url  = plugins_url( 'assets/images/brand/pult-site-favicon-192.png', SONYRA_SITE_MANAGER_FILE );
		$apple_touch_icon_path = plugin_dir_path( SONYRA_SITE_MANAGER_FILE ) . 'assets/images/brand/pult-site-apple-touch-icon-180.png';
		$apple_touch_icon_url  = plugins_url( 'assets/images/brand/pult-site-apple-touch-icon-180.png', SONYRA_SITE_MANAGER_FILE );
		$year = function_exists( 'date_i18n' ) ? date_i18n( 'Y' ) : date( 'Y' );
		$logo_url_with_ver = self::versioned_asset_url( $logo_url, $logo_path );
		$favicon_ico_url_with_ver = self::versioned_asset_url( $favicon_ico_url, $favicon_ico_path, 'sonyra_favicon' );
		$favicon_32_url_with_ver = self::versioned_asset_url( $favicon_32_url, $favicon_32_path, 'sonyra_favicon' );
		$favicon_192_url_with_ver = self::versioned_asset_url( $favicon_192_url, $favicon_192_path, 'sonyra_favicon' );
		$apple_touch_icon_url_with_ver = self::versioned_asset_url( $apple_touch_icon_url, $apple_touch_icon_path, 'sonyra_favicon' );
		?>
<!doctype html>
<html lang="ru">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<meta name="robots" content="noindex,nofollow">
	<title><?php echo esc_html( $title ); ?></title>
	<?php if ( '' !== $favicon_ico_url_with_ver ) : ?>
	<link rel="icon" href="<?php echo esc_url( $favicon_ico_url_with_ver ); ?>" sizes="any">
	<link rel="shortcut icon" href="<?php echo esc_url( $favicon_ico_url_with_ver ); ?>" type="image/x-icon">
	<?php endif; ?>
	<?php if ( '' !== $favicon_32_url_with_ver ) : ?>
	<link rel="icon" type="image/png" sizes="32x32" href="<?php echo esc_url( $favicon_32_url_with_ver ); ?>">
	<?php endif; ?>
	<?php if ( '' !== $favicon_192_url_with_ver ) : ?>
	<link rel="icon" type="image/png" sizes="192x192" href="<?php echo esc_url( $favicon_192_url_with_ver ); ?>">
	<?php endif; ?>
	<?php if ( '' !== $apple_touch_icon_url_with_ver ) : ?>
	<link rel="apple-touch-icon" sizes="180x180" href="<?php echo esc_url( $apple_touch_icon_url_with_ver ); ?>">
	<?php endif; ?>
	<link rel="stylesheet" href="<?php echo esc_url( $css_url ); ?>?ver=<?php echo esc_attr( SONYRA_SITE_MANAGER_VERSION ); ?>">
</head>
<body class="sonyra-login-page sonyra-manager-placeholder-page">
	<div id="sonyra-login-root">
		<div class="sonyra-login-shell">
			<div class="sonyra-login-brand" aria-label="<?php echo esc_attr( self::text( 'login.product_name' ) ); ?>">
				<?php if ( '' !== $logo_url_with_ver ) : ?>
					<img src="<?php echo esc_url( $logo_url_with_ver ); ?>" alt="" class="sonyra-login-brand-icon" aria-hidden="true" decoding="async">
				<?php endif; ?>
				<div class="sonyra-login-brand-copy">
					<div class="sonyra-login-brand-title"><?php echo esc_html( self::text( 'login.product_name' ) ); ?></div>
					<div class="sonyra-login-brand-meta"><?php echo esc_html( self::text( 'login.publisher' ) ); ?></div>
				</div>
			</div>

			<main class="sonyra-login-card">
				<section class="sonyra-manager-placeholder" aria-labelledby="sonyra-manager-placeholder-title">
					<div class="sonyra-login-card-header">
						<p class="sonyra-login-kicker"><?php echo esc_html( self::text( 'login.kicker' ) ); ?></p>
						<h1 id="sonyra-manager-placeholder-title" class="sonyra-login-title"><?php echo esc_html( self::text( 'manager.placeholder_title' ) ); ?></h1>
						<p class="sonyra-login-lead"><?php echo esc_html( self::text( 'manager.placeholder_lead' ) ); ?></p>
					</div>

					<div class="sonyra-login-status">
						<span><?php echo esc_html( self::text( 'manager.placeholder_status' ) ); ?></span>
						<span><?php echo esc_html( self::text( 'manager.placeholder_next' ) ); ?></span>
					</div>
				</section>
			</main>

			<footer class="sonyra-login-footer">
				<span class="sonyra-login-footer-item"><?php echo esc_html( self::text( 'login.publisher' ) ); ?></span>
				<span class="sonyra-login-footer-separator" aria-hidden="true">·</span>
				<span class="sonyra-login-footer-item"><?php echo esc_html( '© ' . $year . ' ' . self::text( 'login.footer_rights_holder' ) ); ?></span>
				<span class="sonyra-login-footer-separator" aria-hidden="true">·</span>
				<span class="sonyra-login-footer-item sonyra-login-footer-version"><?php echo esc_html( self::text( 'login.version_label' ) . ' ' . SONYRA_SITE_MANAGER_VERSION ); ?></span>
			</footer>
		</div>
	</div>
</body>
</html>
		<?php
	}

	public static function render_login_shell(): void {
		$state    = self::get_preview_state();
		$title    = self::text( 'login.title_identifier' );
		$css_url  = plugins_url( 'assets/css/sonyra-login.css', SONYRA_SITE_MANAGER_FILE );
		$logo_path = plugin_dir_path( SONYRA_SITE_MANAGER_FILE ) . 'assets/images/brand/pult-site-logo.png';
		$logo_url  = plugins_url( 'assets/images/brand/pult-site-logo.png', SONYRA_SITE_MANAGER_FILE );
		$favicon_ico_path = plugin_dir_path( SONYRA_SITE_MANAGER_FILE ) . 'assets/images/brand/pult-site-favicon.ico';
		$favicon_ico_url  = plugins_url( 'assets/images/brand/pult-site-favicon.ico', SONYRA_SITE_MANAGER_FILE );
		$favicon_32_path = plugin_dir_path( SONYRA_SITE_MANAGER_FILE ) . 'assets/images/brand/pult-site-favicon-32.png';
		$favicon_32_url  = plugins_url( 'assets/images/brand/pult-site-favicon-32.png', SONYRA_SITE_MANAGER_FILE );
		$favicon_192_path = plugin_dir_path( SONYRA_SITE_MANAGER_FILE ) . 'assets/images/brand/pult-site-favicon-192.png';
		$favicon_192_url  = plugins_url( 'assets/images/brand/pult-site-favicon-192.png', SONYRA_SITE_MANAGER_FILE );
		$apple_touch_icon_path = plugin_dir_path( SONYRA_SITE_MANAGER_FILE ) . 'assets/images/brand/pult-site-apple-touch-icon-180.png';
		$apple_touch_icon_url  = plugins_url( 'assets/images/brand/pult-site-apple-touch-icon-180.png', SONYRA_SITE_MANAGER_FILE );
		$year     = function_exists( 'date_i18n' ) ? date_i18n( 'Y' ) : date( 'Y' );
		$logo_url_with_ver = self::versioned_asset_url( $logo_url, $logo_path );
		$favicon_ico_url_with_ver = self::versioned_asset_url( $favicon_ico_url, $favicon_ico_path, 'sonyra_favicon' );
		$favicon_32_url_with_ver = self::versioned_asset_url( $favicon_32_url, $favicon_32_path, 'sonyra_favicon' );
		$favicon_192_url_with_ver = self::versioned_asset_url( $favicon_192_url, $favicon_192_path, 'sonyra_favicon' );
		$apple_touch_icon_url_with_ver = self::versioned_asset_url( $apple_touch_icon_url, $apple_touch_icon_path, 'sonyra_favicon' );
		?>
<!doctype html>
<html lang="ru">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<meta name="robots" content="noindex,nofollow">
	<title><?php echo esc_html( $title ); ?></title>
	<!-- SONYRA Site Manager favicon build: 0.1.28 -->
	<?php if ( '' !== $favicon_ico_url_with_ver ) : ?>
	<link rel="icon" href="<?php echo esc_url( $favicon_ico_url_with_ver ); ?>" sizes="any">
	<link rel="shortcut icon" href="<?php echo esc_url( $favicon_ico_url_with_ver ); ?>" type="image/x-icon">
	<?php endif; ?>
	<?php if ( '' !== $favicon_32_url_with_ver ) : ?>
	<link rel="icon" type="image/png" sizes="32x32" href="<?php echo esc_url( $favicon_32_url_with_ver ); ?>">
	<?php endif; ?>
	<?php if ( '' !== $favicon_192_url_with_ver ) : ?>
	<link rel="icon" type="image/png" sizes="192x192" href="<?php echo esc_url( $favicon_192_url_with_ver ); ?>">
	<?php endif; ?>
	<?php if ( '' !== $apple_touch_icon_url_with_ver ) : ?>
	<link rel="apple-touch-icon" sizes="180x180" href="<?php echo esc_url( $apple_touch_icon_url_with_ver ); ?>">
	<?php endif; ?>
	<link rel="stylesheet" href="<?php echo esc_url( $css_url ); ?>?ver=<?php echo esc_attr( SONYRA_SITE_MANAGER_VERSION ); ?>">
	<?php self::render_request_code_config(); ?>
</head>
<body class="sonyra-login-page">
	<div id="sonyra-login-root">
		<div class="sonyra-login-shell">
			<div class="sonyra-login-brand" aria-label="<?php echo esc_attr( self::text( 'login.product_name' ) ); ?>">
				<?php if ( '' !== $logo_url_with_ver ) : ?>
					<img src="<?php echo esc_url( $logo_url_with_ver ); ?>" alt="" class="sonyra-login-brand-icon" aria-hidden="true" decoding="async">
				<?php endif; ?>
				<div class="sonyra-login-brand-copy">
					<div class="sonyra-login-brand-title"><?php echo esc_html( self::text( 'login.product_name' ) ); ?></div>
					<div class="sonyra-login-brand-meta"><?php echo esc_html( self::text( 'login.publisher' ) ); ?></div>
				</div>
			</div>

			<main class="sonyra-login-card">
				<?php if ( 'code' === $state ) : ?>
					<?php self::render_code_state(); ?>
				<?php elseif ( 'success' === $state ) : ?>
					<?php self::render_success_state(); ?>
				<?php elseif ( 'error' === $state ) : ?>
					<?php self::render_error_state(); ?>
				<?php else : ?>
					<?php self::render_identifier_state(); ?>
				<?php endif; ?>
			</main>

			<footer class="sonyra-login-footer">
				<span class="sonyra-login-footer-item"><?php echo esc_html( self::text( 'login.publisher' ) ); ?></span>
				<span class="sonyra-login-footer-separator" aria-hidden="true">·</span>
				<span class="sonyra-login-footer-item"><?php echo esc_html( '© ' . $year . ' ' . self::text( 'login.footer_rights_holder' ) ); ?></span>
				<span class="sonyra-login-footer-separator" aria-hidden="true">·</span>
				<span class="sonyra-login-footer-item sonyra-login-footer-version"><?php echo esc_html( self::text( 'login.version_label' ) . ' ' . SONYRA_SITE_MANAGER_VERSION ); ?></span>
			</footer>
		</div>
	</div>
	<?php self::render_runtime_icon_templates(); ?>
	<?php self::render_request_code_script(); ?>
</body>
</html>
		<?php
	}

	private static function render_identifier_state(): void {
		$state = self::get_initial_login_step();
		?>
			<section class="sonyra-login-section sonyra-login-section-identifier" aria-labelledby="sonyra-login-title">
			<div class="sonyra-login-step" data-sonyra-login-step="quick-login" <?php echo 'quick-login' === $state ? '' : 'hidden'; ?>>
				<div class="sonyra-login-card-header">
					<?php self::render_step_head( 'h1', 'login.quick_login_title', 'login.quick_login_hint', 'lock-keyhole' ); ?>
				</div>

				<form class="sonyra-login-form sonyra-login-device-code-form sonyra-login-quick-form" data-sonyra-login-quick-form>
					<div class="sonyra-login-device-code-group">
						<div class="sonyra-login-code-cells sonyra-login-code-cells-four" aria-label="<?php echo esc_attr( self::text( 'login.quick_login_label' ) ); ?>">
							<?php for ( $index = 0; $index < 4; $index++ ) : ?>
								<input class="sonyra-login-code-cell" type="text" name="sonyra_login_quick_code_<?php echo esc_attr( (string) $index ); ?>" inputmode="numeric" pattern="[0-9]*" autocomplete="off" maxlength="1" aria-label="<?php echo esc_attr( self::text( 'login.quick_login_label' ) . ' ' . ( $index + 1 ) ); ?>" data-sonyra-login-quick-code-cell>
							<?php endfor; ?>
						</div>
					</div>

					<button class="sonyra-login-button sonyra-login-button-primary sonyra-login-button-quick" type="submit" data-sonyra-login-quick-button><?php echo esc_html( self::text( 'login.quick_login_button' ) ); ?></button>
					<button class="sonyra-login-text-button" type="button" data-sonyra-login-quick-alternative><?php echo esc_html( self::text( 'login.quick_login_alternative' ) ); ?></button>
				</form>
			</div>

			<div class="sonyra-login-step" data-sonyra-login-step="identifier" <?php echo 'identifier' === $state ? '' : 'hidden'; ?>>
				<div class="sonyra-login-card-header">
				<?php self::render_step_head( 'h1', 'login.title_identifier', 'login.description_identifier', 'mail-check', 'sonyra-login-title' ); ?>
				</div>

				<form class="sonyra-login-form" aria-describedby="sonyra-login-status" data-sonyra-login-request-form>
					<div class="sonyra-login-field">
						<label class="sonyra-login-label" for="sonyra-login-identifier"><?php echo esc_html( self::text( 'login.identifier_label' ) ); ?></label>
						<input class="sonyra-login-input" id="sonyra-login-identifier" type="text" name="sonyra_login_identifier" autocomplete="username" inputmode="email" placeholder="<?php echo esc_attr( self::text( 'login.identifier_placeholder' ) ); ?>" data-sonyra-login-identifier>
					</div>

					<button class="sonyra-login-button sonyra-login-button-primary" type="submit" data-sonyra-login-request-button><?php echo esc_html( self::text( 'login.request_button' ) ); ?></button>
				</form>
			</div>

				<div class="sonyra-login-step" data-sonyra-login-step="verify" <?php echo 'verify' === $state ? '' : 'hidden'; ?>>
					<div class="sonyra-login-card-header">
					<?php self::render_step_head( 'h2', 'login.verify_panel_title', 'login.verify_panel_lead', 'mail-check' ); ?>
				</div>

				<form class="sonyra-login-verify-form" data-sonyra-login-verify-form>
					<div class="sonyra-login-code-cells" aria-label="<?php echo esc_attr( self::text( 'login.code_label' ) ); ?>">
						<?php for ( $index = 0; $index < 6; $index++ ) : ?>
							<input class="sonyra-login-code-cell" type="text" name="sonyra_login_verify_code_<?php echo esc_attr( (string) $index ); ?>" inputmode="numeric" autocomplete="<?php echo 0 === $index ? 'one-time-code' : 'off'; ?>" maxlength="1" aria-label="<?php echo esc_attr( self::text( 'login.code_label' ) . ' ' . ( $index + 1 ) ); ?>" data-sonyra-login-code-cell data-sonyra-login-code-index="<?php echo esc_attr( (string) $index ); ?>">
						<?php endfor; ?>
					</div>

					<button class="sonyra-login-button sonyra-login-button-primary" type="submit" data-sonyra-login-verify-button><?php echo esc_html( self::text( 'login.verify_code_button' ) ); ?></button>
					<div class="sonyra-login-switch-panel" data-sonyra-login-remember-panel>
						<button class="sonyra-login-switch" type="button" role="switch" aria-checked="false" data-sonyra-login-remember-switch>
							<span class="sonyra-login-switch-control" aria-hidden="true">
								<span class="sonyra-login-switch-thumb"></span>
							</span>
							<span class="sonyra-login-switch-copy">
								<span class="sonyra-login-switch-title"><?php echo esc_html( self::text( 'login.remember_device_label' ) ); ?></span>
								<span class="sonyra-login-switch-hint"><?php echo esc_html( self::text( 'login.remember_device_hint' ) ); ?></span>
							</span>
						</button>
						<button class="sonyra-login-switch-info" type="button" aria-label="<?php echo esc_attr( self::text( 'login.remember_device_info_label' ) ); ?>" aria-expanded="false" aria-controls="sonyra-login-remember-tooltip" data-sonyra-login-remember-info>
							<?php self::render_icon( 'info', 'sonyra-login-icon sonyra-login-info-icon' ); ?>
						</button>
						<div id="sonyra-login-remember-tooltip" class="sonyra-login-switch-tooltip" role="tooltip" data-sonyra-login-remember-tooltip hidden>
							<strong class="sonyra-login-switch-tooltip-title"><?php self::render_icon( 'info', 'sonyra-login-icon sonyra-login-tooltip-icon' ); ?><span><?php echo esc_html( self::text( 'login.remember_device_tooltip_title' ) ); ?></span></strong>
							<button class="sonyra-login-tooltip-close" type="button" aria-label="<?php echo esc_attr( self::text( 'login.tooltip_close' ) ); ?>" data-sonyra-login-remember-close>
								<?php self::render_icon( 'close', 'sonyra-login-icon sonyra-login-tooltip-close-icon' ); ?>
							</button>
							<div class="sonyra-login-switch-tooltip-body">
								<p><?php echo esc_html( self::text( 'login.remember_device_tooltip_body_1' ) ); ?></p>
								<p><?php echo esc_html( self::text( 'login.remember_device_tooltip_body_2' ) ); ?></p>
								<p><?php echo esc_html( self::text( 'login.remember_device_tooltip_body_3' ) ); ?></p>
							</div>
						</div>
					</div>
					<div class="sonyra-login-tooltip-backdrop" data-sonyra-login-remember-backdrop hidden></div>
					<button class="sonyra-login-text-button" type="button" data-sonyra-login-change-identifier><?php echo esc_html( self::text( 'login.verify_change' ) ); ?></button>
				</form>
			</div>

				<div class="sonyra-login-step" data-sonyra-login-step="setup-device-code" <?php echo 'setup-device-code' === $state ? '' : 'hidden'; ?>>
					<div class="sonyra-login-card-header">
					<?php self::render_step_head( 'h2', 'login.device_code_setup_title', 'login.device_code_setup_hint', 'key-round', '', 'sonyra-login-title-compact' ); ?>
				</div>

				<form class="sonyra-login-form sonyra-login-device-code-form" data-sonyra-login-setup-code-form>
					<div class="sonyra-login-device-code-group">
						<div class="sonyra-login-label"><?php echo esc_html( self::text( 'login.device_code_setup_first_label' ) ); ?></div>
						<div class="sonyra-login-code-cells sonyra-login-code-cells-four" aria-label="<?php echo esc_attr( self::text( 'login.device_code_setup_first_label' ) ); ?>">
							<?php for ( $index = 0; $index < 4; $index++ ) : ?>
								<input class="sonyra-login-code-cell" type="text" name="sonyra_login_setup_code_<?php echo esc_attr( (string) $index ); ?>" inputmode="numeric" pattern="[0-9]*" autocomplete="off" maxlength="1" aria-label="<?php echo esc_attr( self::text( 'login.device_code_setup_first_label' ) . ' ' . ( $index + 1 ) ); ?>" data-sonyra-login-setup-code-cell>
							<?php endfor; ?>
						</div>
					</div>

					<button class="sonyra-login-button sonyra-login-button-primary" type="submit" data-sonyra-login-setup-code-button><?php echo esc_html( self::text( 'login.device_code_setup_continue' ) ); ?></button>
					<button class="sonyra-login-text-button" type="button" data-sonyra-login-setup-skip><?php echo esc_html( self::text( 'login.device_code_setup_skip' ) ); ?></button>
				</form>
			</div>

				<div class="sonyra-login-step" data-sonyra-login-step="setup-device-confirm" <?php echo 'setup-device-confirm' === $state ? '' : 'hidden'; ?>>
					<div class="sonyra-login-card-header">
					<?php self::render_step_head( 'h2', 'login.device_code_confirm_title', 'login.device_code_confirm_hint', 'check-check', '', 'sonyra-login-title-compact' ); ?>
				</div>

				<form class="sonyra-login-form sonyra-login-device-code-form" data-sonyra-login-setup-confirm-form>
					<div class="sonyra-login-device-code-group">
						<div class="sonyra-login-label"><?php echo esc_html( self::text( 'login.device_code_setup_second_label' ) ); ?></div>
						<div class="sonyra-login-code-cells sonyra-login-code-cells-four" aria-label="<?php echo esc_attr( self::text( 'login.device_code_setup_second_label' ) ); ?>">
							<?php for ( $index = 0; $index < 4; $index++ ) : ?>
								<input class="sonyra-login-code-cell" type="text" name="sonyra_login_setup_confirm_<?php echo esc_attr( (string) $index ); ?>" inputmode="numeric" pattern="[0-9]*" autocomplete="off" maxlength="1" aria-label="<?php echo esc_attr( self::text( 'login.device_code_setup_second_label' ) . ' ' . ( $index + 1 ) ); ?>" data-sonyra-login-setup-confirm-cell>
							<?php endfor; ?>
						</div>
					</div>

					<button class="sonyra-login-button sonyra-login-button-primary" type="submit" data-sonyra-login-setup-confirm-button><?php echo esc_html( self::text( 'login.device_code_setup_button' ) ); ?></button>
					<button class="sonyra-login-text-button" type="button" data-sonyra-login-setup-change-first><?php echo esc_html( self::text( 'login.device_code_change_first' ) ); ?></button>
					<button class="sonyra-login-text-button" type="button" data-sonyra-login-setup-skip><?php echo esc_html( self::text( 'login.device_code_setup_skip' ) ); ?></button>
				</form>
			</div>

				<div class="sonyra-login-step" data-sonyra-login-step="success" hidden>
					<div class="sonyra-login-card-header">
					<div class="sonyra-login-step-head sonyra-login-result-head">
						<div class="sonyra-login-step-icon-tile sonyra-login-step-icon-tile-success" aria-hidden="true">
							<?php self::render_icon( 'circle-check-big', 'sonyra-login-icon sonyra-login-heading-icon' ); ?>
						</div>
						<div class="sonyra-login-step-copy">
							<p class="sonyra-login-kicker"><?php echo esc_html( self::text( 'login.kicker' ) ); ?></p>
							<h2 class="sonyra-login-title sonyra-login-title-word-gap" data-sonyra-login-success-title><?php self::render_word_spans( self::text( 'login.success_ready_title' ) ); ?></h2>
							<p class="sonyra-login-lead" data-sonyra-login-success-lead><?php echo esc_html( self::text( 'login.success_ready_lead' ) ); ?></p>
						</div>
					</div>
				</div>
			</div>

			<div id="sonyra-login-status" class="sonyra-login-status" aria-live="polite" data-sonyra-login-status <?php echo 'quick-login' === $state ? 'hidden' : ''; ?>>
				<?php self::render_icon( 'info', 'sonyra-login-icon sonyra-login-status-icon' ); ?>
				<span class="sonyra-login-status-message"><?php echo esc_html( self::text( 'login.neutral_status_line_1' ) ); ?></span>
				<span class="sonyra-login-status-hint"><?php echo esc_html( self::text( 'login.neutral_status_line_2' ) ); ?></span>
			</div>
		</section>
		<?php
	}

	private static function render_code_state(): void {
		?>
		<section class="sonyra-login-section sonyra-login-section-code" aria-labelledby="sonyra-login-title">
			<div class="sonyra-login-card-header">
				<?php self::render_step_head( 'h1', 'login.title_code', 'login.description_code', 'mail-check', 'sonyra-login-title' ); ?>
			</div>

			<form class="sonyra-login-form">
				<div class="sonyra-login-code-cells" aria-label="<?php echo esc_attr( self::text( 'login.code_label' ) ); ?>">
					<?php for ( $index = 0; $index < 6; $index++ ) : ?>
						<input class="sonyra-login-code-cell" type="text" name="sonyra_login_code_<?php echo esc_attr( (string) $index ); ?>" inputmode="numeric" autocomplete="<?php echo 0 === $index ? 'one-time-code' : 'off'; ?>" maxlength="1" aria-label="<?php echo esc_attr( self::text( 'login.code_label' ) . ' ' . ( $index + 1 ) ); ?>">
					<?php endfor; ?>
				</div>

				<button class="sonyra-login-button sonyra-login-button-primary" type="button"><?php echo esc_html( self::text( 'login.verify_code_button' ) ); ?></button>

				<div class="sonyra-login-actions">
					<button class="sonyra-login-text-button" type="button"><?php echo esc_html( self::text( 'login.resend' ) ); ?></button>
					<button class="sonyra-login-text-button" type="button"><?php echo esc_html( self::text( 'login.change_identifier' ) ); ?></button>
				</div>
			</form>
		</section>
		<?php
	}

	private static function render_success_state(): void {
		?>
		<section class="sonyra-login-section sonyra-login-section-success" aria-labelledby="sonyra-login-title">
			<div class="sonyra-login-card-header">
				<?php self::render_step_head( 'h1', 'login.title_success', 'login.description_success', 'circle-check-big', 'sonyra-login-title' ); ?>
			</div>
		</section>
		<?php
	}

	private static function render_error_state(): void {
		?>
		<section class="sonyra-login-section sonyra-login-section-error" aria-labelledby="sonyra-login-title">
			<div class="sonyra-login-card-header">
				<?php self::render_step_head( 'h1', 'login.title_error', 'login.description_error', 'circle-alert', 'sonyra-login-title' ); ?>
			</div>

			<div class="sonyra-login-form">
				<button class="sonyra-login-button sonyra-login-button-primary" type="button"><?php echo esc_html( self::text( 'login.retry_button' ) ); ?></button>
				<div class="sonyra-login-actions">
					<button class="sonyra-login-text-button" type="button"><?php echo esc_html( self::text( 'login.change_identifier' ) ); ?></button>
				</div>
			</div>
		</section>
		<?php
	}
}
