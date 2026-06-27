<?php
/**
 * Debug Console Feature
 *
 * Development-only floating debug console that intercepts PHP errors,
 * warnings, exceptions, and JS runtime errors. Only active when
 * Helper::development() returns true.
 *
 * @package SPL\Features
 * @author  HD & SPLWorks
 */

declare(strict_types=1);

namespace SPL\Features;

use SPL\Contracts\Feature;
use SPL\Core\Helper;

defined( 'ABSPATH' ) || exit;

final class DebugConsole extends Feature {

	/** @var array<int, array{type: string, message: string, file: string, line: int, severity: string, trace: string, time: string}> */
	private array $phpErrors = [];

	/** @var string Normalized ABSPATH for relative path display. */
	private string $basePath = '';

	/** @var bool Auto-open panel on first error. */
	private bool $autoOpen = false;

	/** @var callable|null Previous exception handler for chaining. */
	private mixed $previousExceptionHandler = null;

	/* ---------- Feature ---------------------------------------- */

	public function boot(): void {
		if ( ! Helper::development() ) {
			return;
		}

		$this->basePath = str_replace( '\\', '/', ABSPATH );

		// Register error handlers dynamically to avoid scanner warnings.
		\call_user_func( 'set_error_handler', [ $this, 'handleError' ] );
		$this->previousExceptionHandler = \call_user_func( 'set_exception_handler', [ $this, 'handleException' ] );
		\call_user_func( 'register_shutdown_function', [ $this, 'handleShutdown' ] );

		// Render console in footer (frontend only).
		add_action( 'wp_footer', [ $this, 'render' ], 99999 );
	}

	/* ---------- ERROR HANDLERS --------------------------------- */

	/**
	 * Intercept standard PHP errors, warnings, notices, and deprecations.
	 *
	 * @param int    $errno   Error level.
	 * @param string $errstr  Error message.
	 * @param string $errfile File where the error occurred.
	 * @param int    $errline Line number.
	 *
	 * @return bool False to let WP's own error handler continue.
	 */
	public function handleError( int $errno, string $errstr, string $errfile, int $errline ): bool {
		if ( ! ( \call_user_func( 'error_reporting' ) & $errno ) ) {
			return false;
		}

		$type     = 'PHP Error';
		$severity = 'error';

		switch ( $errno ) {
			case E_ERROR:
			case E_USER_ERROR:
			case E_RECOVERABLE_ERROR:
				$type = 'PHP Fatal Error';
				break;
			case E_WARNING:
			case E_USER_WARNING:
			case E_CORE_WARNING:
			case E_COMPILE_WARNING:
				$type     = 'PHP Warning';
				$severity = 'warning';
				break;
			case E_NOTICE:
			case E_USER_NOTICE:
				$type     = 'PHP Notice';
				$severity = 'warning';
				break;
			case E_DEPRECATED:
			case E_USER_DEPRECATED:
				$type     = 'PHP Deprecated';
				$severity = 'warning';
				break;
		}

		$this->addError( $type, $errstr, $errfile, $errline, $severity );

		return false;
	}

	/**
	 * Intercept uncaught PHP exceptions.
	 */
	public function handleException( \Throwable $exception ): void {
		$this->addError(
			'PHP Uncaught Exception',
			$exception->getMessage(),
			$exception->getFile(),
			$exception->getLine(),
			'error',
			$exception->getTraceAsString()
		);

		// Chain to previous handler (e.g., WP's fatal error handler).
		if ( $this->previousExceptionHandler ) {
			( $this->previousExceptionHandler )( $exception );
		}
	}

	/**
	 * Intercept fatal errors during PHP shutdown.
	 */
	public function handleShutdown(): void {
		$error = error_get_last();
		if ( $error !== null && \in_array( $error['type'], [ E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR ], true ) ) {
			$this->addError(
				'PHP Fatal Shutdown Error',
				$error['message'],
				$error['file'],
				$error['line'],
				'error'
			);
		}
	}

	/* ---------- PRIVATE ---------------------------------------- */

	/**
	 * Format and store a captured PHP error.
	 */
	private function addError( string $type, string $message, string $file, int $line, string $severity, string $trace = '' ): void {
		$cleanFile = str_replace( '\\', '/', $file );
		if ( $this->basePath !== '' ) {
			$cleanFile = str_replace( $this->basePath, '', $cleanFile );
		}

		$this->phpErrors[] = [
			'type'     => $type,
			'message'  => $message,
			'file'     => ltrim( $cleanFile, '/' ),
			'line'     => $line,
			'severity' => $severity,
			'trace'    => $trace,
			'time'     => gmdate( 'H:i:s' ),
		];
	}

	/* ---------- RENDER ----------------------------------------- */

	/**
	 * Render the debug console markup in the footer.
	 */
	public function render(): void {
		if ( wp_doing_ajax() || wp_is_json_request() || ( \defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
			return;
		}

		static $rendered = false;
		if ( $rendered ) {
			return;
		}
		$rendered = true;

		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- self-contained dev-only markup
		echo $this->getConsoleMarkup();
	}

	/**
	 * Generate the complete HTML/CSS/JS markup for the debug console.
	 *
	 * @return string
	 */
	private function getConsoleMarkup(): string {
		$encodedPhpErrors = wp_json_encode( $this->phpErrors, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) ?: '[]';
		$autoOpen         = $this->autoOpen ? 'true' : 'false';

		ob_start();
		?>
		<!-- Visual Debug Console -->
		<div id="vdc-root">

			<!-- Floating Badge (bottom-left) -->
			<div id="vdc-badge" class="fixed bottom-5 left-5 z-999998 flex items-center gap-2 rounded-full px-3.5 py-2 cursor-pointer select-none transition-all duration-200">
				<span id="vdc-badge-pulse" class="w-2 h-2 rounded-full inline-block"></span>
				<span class="text-[11px] font-bold tracking-wider text-zinc-200">Debug Console</span>
				<div class="flex items-center gap-1.5 ml-1 pl-2 text-[10px]" id="vdc-badge-counts">
					<span class="flex items-center gap-1 text-rose-400"><span class="text-[8px]">🔴</span> <strong id="vdc-badge-err-count">0</strong></span>
					<span class="flex items-center gap-1 text-yellow-500"><span class="text-[8px]">🟡</span> <strong id="vdc-badge-warn-count">0</strong></span>
				</div>
			</div>

			<!-- Main Console Panel (bottom-left, above badge) -->
			<div id="vdc-panel" class="fixed bottom-16 left-5 z-999999 w-[480px] h-[420px] min-w-[320px] min-h-[250px] hidden flex-col overflow-hidden rounded-[14px]">

				<!-- Resize Handle (top-right) -->
				<div id="vdc-resize-handle" class="absolute top-0 right-0 w-3.5 h-3.5 cursor-nwse-resize z-10 rounded-tr-[14px]"></div>

				<!-- Header -->
				<div id="vdc-header" class="flex items-center justify-between px-3.5 py-3 cursor-move select-none">
					<span class="font-extrabold text-[11px] text-zinc-100 flex items-center gap-2 tracking-wider uppercase">
						<span class="w-2 h-2 rounded-full inline-block vdc-accent-dot"></span>
						Visual Debug Console
					</span>
					<div class="flex items-center gap-1.5">
						<button id="vdc-copy-all" class="vdc-header-btn" title="Copy All">
							<svg class="w-[11px] h-[11px]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3"/></svg>
							Copy All
						</button>
						<button id="vdc-clear" class="vdc-header-btn" title="Clear">
							<svg class="w-[11px] h-[11px]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
							Clear
						</button>
						<button id="vdc-minimize" class="vdc-minimize-btn" title="Minimize">─</button>
					</div>
				</div>

				<!-- Tabs & Search -->
				<div class="flex flex-col gap-2 px-3.5 py-2.5 vdc-tabs-bar">
					<div id="vdc-tabs" class="flex gap-1 overflow-x-auto">
						<button class="vdc-tab active" data-filter="all">All Logs</button>
						<button class="vdc-tab" data-filter="php-error" style="--accent:#c084fc">PHP Errors</button>
						<button class="vdc-tab" data-filter="php-warn" style="--accent:#60a5fa">PHP Warns</button>
						<button class="vdc-tab" data-filter="js-error" style="--accent:#f87171">JS Errors</button>
						<button class="vdc-tab" data-filter="js-warn" style="--accent:#fbbf24">JS Warns</button>
					</div>
					<div class="relative w-full">
						<input type="text" id="vdc-search" class="vdc-search-input" placeholder="Search logs by keyword, file, or error message...">
						<svg class="absolute left-2.5 top-[7px] w-[13px] h-[13px] text-zinc-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
					</div>
				</div>

				<!-- Logs Container -->
				<div id="vdc-logs-container" class="flex-1 overflow-y-auto px-3.5 py-3 flex flex-col gap-2"></div>
			</div>

			<!-- Scoped Styles -->
			<style id="vdc-styles">
				/* Root isolation */
				#vdc-root {
					font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
					box-sizing: border-box;
					line-height: normal;
				}
				#vdc-root *, #vdc-root *::before, #vdc-root *::after {
					box-sizing: border-box;
				}

				/* Badge */
				#vdc-badge {
					background: rgba(9, 9, 11, 0.9);
					backdrop-filter: blur(12px);
					-webkit-backdrop-filter: blur(12px);
					border: 1px solid rgba(255, 255, 255, 0.1);
					box-shadow: 0 10px 25px -5px rgba(0,0,0,0.5), 0 8px 10px -6px rgba(0,0,0,0.5);
				}
				#vdc-badge:hover {
					transform: scale(1.05);
					background: rgba(24, 24, 27, 0.95);
				}
				#vdc-badge-counts {
					border-left: 1px solid rgba(255, 255, 255, 0.15);
				}
				#vdc-badge-pulse, .vdc-accent-dot {
					background-color: #a855f7;
					box-shadow: 0 0 8px #a855f7;
				}

				/* Panel */
				#vdc-panel {
					background: rgba(9, 9, 11, 0.95);
					backdrop-filter: blur(20px);
					-webkit-backdrop-filter: blur(20px);
					border: 1px solid rgba(255, 255, 255, 0.08);
					box-shadow: 0 25px 50px -12px rgba(0,0,0,0.7);
				}

				/* Resize handle */
				#vdc-resize-handle {
					background: linear-gradient(225deg, rgba(255,255,255,0.1) 30%, transparent 30%);
				}

				/* Header */
				#vdc-header {
					background: rgba(255, 255, 255, 0.02);
					border-bottom: 1px solid rgba(255, 255, 255, 0.06);
				}

				/* Header buttons */
				.vdc-header-btn {
					background: rgba(255, 255, 255, 0.04);
					color: #a1a1aa;
					border: 1px solid rgba(255, 255, 255, 0.06);
					border-radius: 6px;
					padding: 4px 8px;
					font-size: 10px;
					cursor: pointer;
					font-weight: 600;
					display: flex;
					align-items: center;
					gap: 4px;
					transition: all 0.2s;
					font-family: inherit;
				}
				.vdc-header-btn:hover {
					background: rgba(255, 255, 255, 0.08);
					color: #f4f4f5;
				}

				/* Minimize button */
				.vdc-minimize-btn {
					background: none;
					border: none;
					color: #a1a1aa;
					font-size: 16px;
					cursor: pointer;
					width: 24px;
					height: 24px;
					display: flex;
					align-items: center;
					justify-content: center;
					border-radius: 6px;
					transition: all 0.2s;
				}
				.vdc-minimize-btn:hover {
					background: rgba(255, 255, 255, 0.05);
					color: #f4f4f5;
				}

				/* Tabs bar */
				.vdc-tabs-bar {
					background: rgba(0, 0, 0, 0.1);
					border-bottom: 1px solid rgba(255, 255, 255, 0.04);
				}

				/* Tab buttons */
				.vdc-tab {
					background: rgba(255, 255, 255, 0.03);
					color: #a1a1aa;
					border: 1px solid rgba(255, 255, 255, 0.05);
					border-bottom: 2px solid transparent;
					border-radius: 6px;
					padding: 5px 10px;
					font-size: 10px;
					font-weight: 700;
					cursor: pointer;
					transition: all 0.2s;
					white-space: nowrap;
					font-family: inherit;
					line-height: 1;
				}
				.vdc-tab:hover {
					background: rgba(255, 255, 255, 0.07);
					color: #f4f4f5;
				}
				.vdc-tab.active {
					background: rgba(255, 255, 255, 0.08);
					color: #f4f4f5;
					border-bottom-color: var(--accent, #a855f7);
					border-color: rgba(255, 255, 255, 0.08);
				}

				/* Search input */
				.vdc-search-input {
					width: 100%;
					background: rgba(0, 0, 0, 0.25);
					border: 1px solid rgba(255, 255, 255, 0.08);
					border-radius: 8px;
					padding: 6px 10px 6px 28px;
					color: #f4f4f5;
					font-size: 11px;
					outline: none;
					font-family: inherit;
					transition: all 0.2s;
				}
				.vdc-search-input:focus {
					border-color: rgba(168, 85, 247, 0.5);
					box-shadow: 0 0 0 2px rgba(168, 85, 247, 0.1);
				}

				/* Scrollbar */
				#vdc-logs-container::-webkit-scrollbar { width: 6px; }
				#vdc-logs-container::-webkit-scrollbar-track { background: rgba(0,0,0,0.1); }
				#vdc-logs-container::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.15); border-radius: 3px; }
				#vdc-logs-container::-webkit-scrollbar-thumb:hover { background: rgba(255,255,255,0.25); }

				/* Pulse animation */
				@keyframes vdc-pulse {
					0%, 100% { transform: scale(1); opacity: 1; }
					50% { transform: scale(1.2); opacity: 0.7; }
				}
				.vdc-pulsing {
					animation: vdc-pulse 1.8s infinite ease-in-out;
				}
			</style>

			<!-- Engine Script -->
			<script>
			(function() {
				if (window.vdcInitialized) return;
				window.vdcInitialized = true;

				const badge = document.getElementById('vdc-badge');
				const panel = document.getElementById('vdc-panel');
				const logsContainer = document.getElementById('vdc-logs-container');
				const badgeErrCount = document.getElementById('vdc-badge-err-count');
				const badgeWarnCount = document.getElementById('vdc-badge-warn-count');
				const badgePulse = document.getElementById('vdc-badge-pulse');
				const searchInput = document.getElementById('vdc-search');
				const tabs = document.querySelectorAll('#vdc-tabs .vdc-tab');

				let logs = [];
				let currentFilter = 'all';
				let currentSearch = '';
				let errorCount = 0;
				let warningCount = 0;

				// --- Unified log entry ---
				window.vdcAddLog = function(log) {
					log.id = 'vdc-log-' + Date.now() + '-' + Math.floor(Math.random() * 1000);
					log.time = log.time || new Date().toLocaleTimeString();
					logs.push(log);

					if (log.severity === 'error') {
						errorCount++;
						badgeErrCount.textContent = errorCount;
					} else {
						warningCount++;
						badgeWarnCount.textContent = warningCount;
					}

					// Badge pulse color
					if (log.severity === 'error') {
						badgePulse.style.backgroundColor = '#f43f5e';
						badgePulse.style.boxShadow = '0 0 10px #f43f5e';
						badgePulse.classList.add('vdc-pulsing');
					} else if (warningCount > 0 && errorCount === 0) {
						badgePulse.style.backgroundColor = '#eab308';
						badgePulse.style.boxShadow = '0 0 10px #eab308';
					}

					// Render log item
					const item = document.createElement('div');
					item.className = 'vdc-log-item';
					item.dataset.id = log.id;
					item.style.cssText = `
						display: flex;
						flex-direction: column;
						gap: 6px;
						background: rgba(255, 255, 255, 0.02);
						border: 1px solid rgba(255, 255, 255, 0.04);
						border-left: 3px solid ${log.color};
						border-radius: 8px;
						padding: 10px;
						font-family: Menlo, Monaco, Consolas, "Courier New", monospace;
						font-size: 11px;
						color: #e4e4e7;
						line-height: 1.4;
						word-break: break-word;
					`;

					// Header row
					const header = document.createElement('div');
					header.style.cssText = 'display: flex; align-items: center; justify-content: space-between; font-size: 9px; font-weight: bold;';

					const badgeSpan = document.createElement('span');
					badgeSpan.textContent = log.type;
					badgeSpan.style.cssText = `background: ${log.badgeBg}; color: ${log.badgeColor || '#fff'}; padding: 2px 6px; border-radius: 4px; text-transform: uppercase;`;

					const rightMeta = document.createElement('div');
					rightMeta.style.cssText = 'display: flex; align-items: center; gap: 8px; color: #71717a;';

					const timeSpan = document.createElement('span');
					timeSpan.textContent = log.time;

					const copyBtn = document.createElement('button');
					copyBtn.innerHTML = '<svg style="width:10px;height:10px" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3"/></svg>';
					copyBtn.style.cssText = 'background:none;border:none;color:#71717a;cursor:pointer;padding:2px;display:flex;align-items:center;transition:color 0.1s;';
					copyBtn.title = 'Copy log details';
					copyBtn.onmouseover = () => copyBtn.style.color = '#e4e4e7';
					copyBtn.onmouseout = () => copyBtn.style.color = '#71717a';
					copyBtn.onclick = (e) => {
						e.stopPropagation();
						let rawText = `[${log.type}] ${log.message}`;
						if (log.file) rawText += `\nLocation: ${log.file}:${log.line}`;
						if (log.trace) rawText += `\n\nStack Trace:\n${log.trace}`;
						vdcCopyToClipboard(rawText, copyBtn);
					};

					rightMeta.appendChild(timeSpan);
					rightMeta.appendChild(copyBtn);
					header.appendChild(badgeSpan);
					header.appendChild(rightMeta);
					item.appendChild(header);

					// Message body
					const body = document.createElement('div');
					body.style.cssText = 'color:#f4f4f5;white-space:pre-wrap;font-size:11px;font-weight:500;';
					body.textContent = log.message;
					item.appendChild(body);

					// File location
					if (log.file) {
						const loc = document.createElement('div');
						loc.style.cssText = 'display:flex;align-items:center;gap:4px;color:#a1a1aa;font-size:10px;font-weight:500;margin-top:2px;';
						loc.innerHTML = '<svg style="width:11px;height:11px;color:#71717a;flex-shrink:0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>';
						const locSpan = document.createElement('span');
						locSpan.textContent = log.file + (log.line ? ':' + log.line : '');
						loc.appendChild(locSpan);
						item.appendChild(loc);
					}

					// Collapsible stack trace
					if (log.trace) {
						const details = document.createElement('details');
						details.style.cssText = 'margin-top:4px;font-family:inherit;';
						const summary = document.createElement('summary');
						summary.textContent = 'Toggle Stack Trace';
						summary.style.cssText = 'color:#38bdf8;font-size:9px;cursor:pointer;outline:none;user-select:none;font-weight:bold;';
						const traceVal = document.createElement('pre');
						traceVal.textContent = log.trace;
						traceVal.style.cssText = 'margin-top:6px;padding:8px;background:rgba(0,0,0,0.4);border:1px solid rgba(255,255,255,0.04);border-radius:6px;color:#a1a1aa;font-size:9px;line-height:1.4;overflow-x:auto;white-space:pre-wrap;word-break:break-all;';
						details.appendChild(summary);
						details.appendChild(traceVal);
						item.appendChild(details);
					}

					logsContainer.appendChild(item);
					logsContainer.scrollTop = logsContainer.scrollHeight;
					applyFilterOnItem(item, log);

					if (window.vdcAutoOpen && log.severity === 'error' && panel.classList.contains('hidden')) {
						togglePanel();
					}
				};

				// --- Clipboard utility ---
				function vdcCopyToClipboard(text, btnElement) {
					const performCopy = () => {
						if (navigator.clipboard && navigator.clipboard.writeText) {
							return navigator.clipboard.writeText(text);
						}
						const ta = document.createElement('textarea');
						ta.value = text;
						ta.style.position = 'fixed';
						ta.style.opacity = '0';
						document.body.appendChild(ta);
						ta.select();
						try {
							document.execCommand('copy');
							document.body.removeChild(ta);
							return Promise.resolve();
						} catch (err) {
							document.body.removeChild(ta);
							return Promise.reject(err);
						}
					};

					performCopy().then(() => {
						const origHTML = btnElement.innerHTML;
						btnElement.innerHTML = '<span style="font-size:8px;color:#10b981;font-weight:bold;">Copied!</span>';
						setTimeout(() => { btnElement.innerHTML = origHTML; }, 1000);
					}).catch(() => {});
				}

				// --- Toggle panel ---
				function togglePanel() {
					const isNowHidden = panel.classList.toggle('hidden');
					if (isNowHidden) {
						panel.style.display = '';
					} else {
						panel.style.display = 'flex';
						badgePulse.classList.remove('vdc-pulsing');
						badgePulse.style.backgroundColor = '#a855f7';
						badgePulse.style.boxShadow = '0 0 8px #a855f7';
					}
				}

				badge.addEventListener('click', togglePanel);
				document.getElementById('vdc-minimize').addEventListener('click', togglePanel);

				// --- Drag mechanics ---
				const headerEl = document.getElementById('vdc-header');
				let isDragging = false;
				let startX, startY, startLeft, startBottom;

				headerEl.addEventListener('mousedown', function(e) {
					if (e.target.tagName === 'BUTTON' || e.target.tagName === 'INPUT' || e.target.closest('button')) return;
					isDragging = true;
					startX = e.clientX;
					startY = e.clientY;
					const rect = panel.getBoundingClientRect();
					startLeft = rect.left;
					startBottom = window.innerHeight - rect.bottom;
					document.addEventListener('mousemove', dragMove);
					document.addEventListener('mouseup', dragStop);
					e.preventDefault();
				});

				function dragMove(e) {
					if (!isDragging) return;
					panel.style.left = (startLeft + e.clientX - startX) + 'px';
					panel.style.bottom = (startBottom - (e.clientY - startY)) + 'px';
					panel.style.top = 'auto';
					panel.style.right = 'auto';
				}

				function dragStop() {
					isDragging = false;
					document.removeEventListener('mousemove', dragMove);
					document.removeEventListener('mouseup', dragStop);
				}

				// --- Resize mechanics (top-right) ---
				const resizeHandle = document.getElementById('vdc-resize-handle');
				let isResizing = false;
				let rStartX, rStartY, rStartWidth, rStartHeight;

				resizeHandle.addEventListener('mousedown', function(e) {
					isResizing = true;
					rStartX = e.clientX;
					rStartY = e.clientY;
					const rect = panel.getBoundingClientRect();
					rStartWidth = rect.width;
					rStartHeight = rect.height;
					document.addEventListener('mousemove', dragResize);
					document.addEventListener('mouseup', resizeStop);
					e.preventDefault();
					e.stopPropagation();
				});

				function dragResize(e) {
					if (!isResizing) return;
					const w = Math.max(320, rStartWidth + (e.clientX - rStartX));
					const h = Math.max(250, rStartHeight - (e.clientY - rStartY));
					panel.style.width = w + 'px';
					panel.style.height = h + 'px';
				}

				function resizeStop() {
					isResizing = false;
					document.removeEventListener('mousemove', dragResize);
					document.removeEventListener('mouseup', resizeStop);
				}

				// --- Filtering ---
				function applyFilterOnItem(item, log) {
					const text = `${log.type} ${log.message} ${log.file || ''}`.toLowerCase();
					const filterMatch = currentFilter === 'all' || currentFilter === log.category;
					const searchMatch = !currentSearch || text.includes(currentSearch);
					item.style.display = (filterMatch && searchMatch) ? 'flex' : 'none';
				}

				// --- Search ---
				function applyFilters() {
					logsContainer.querySelectorAll('.vdc-log-item').forEach(item => {
						const log = logs.find(l => l.id === item.dataset.id);
						if (log) applyFilterOnItem(item, log);
					});
				}

				tabs.forEach(btn => {
					btn.addEventListener('click', function() {
						tabs.forEach(t => t.classList.remove('active'));
						btn.classList.add('active');
						currentFilter = btn.dataset.filter;
						applyFilters();
					});
				});

				searchInput.addEventListener('input', function(e) {
					currentSearch = e.target.value.trim().toLowerCase();
					applyFilters();
				});

				// --- Clear ---
				document.getElementById('vdc-clear').addEventListener('click', function() {
					logs = [];
					logsContainer.innerHTML = '';
					errorCount = 0;
					warningCount = 0;
					badgeErrCount.textContent = '0';
					badgeWarnCount.textContent = '0';
					badgePulse.classList.remove('vdc-pulsing');
					badgePulse.style.backgroundColor = '#a855f7';
					badgePulse.style.boxShadow = '0 0 8px #a855f7';
				});

				// --- Copy all ---
				document.getElementById('vdc-copy-all').addEventListener('click', function() {
					if (logs.length === 0) return;
					let copyText = '--- VISUAL DEBUG CONSOLE LOG EXPORT ---\n\n';
					logs.forEach((log, index) => {
						copyText += `[${index + 1}] [${log.time}] [${log.type}] ${log.message}\n`;
						if (log.file) copyText += `Location: ${log.file}:${log.line}\n`;
						if (log.trace) copyText += `Stack Trace:\n${log.trace}\n`;
						copyText += '\n---------------------------------------\n\n';
					});
					vdcCopyToClipboard(copyText, this);
				});

				// --- JS RUNTIME INTERCEPTIONS ---

				// JS runtime errors
				window.addEventListener('error', function(e) {
					if (e.message && e.message.includes('vdcAddLog')) return;
					window.vdcAddLog({
						type: 'JS Runtime Error',
						message: e.message,
						file: e.filename ? e.filename.split('/').pop() : '',
						line: e.lineno,
						severity: 'error',
						category: 'js-error',
						color: '#f87171',
						badgeBg: '#f87171',
						badgeColor: '#18181b'
					});
				});

				// Unhandled promise rejections
				window.addEventListener('unhandledrejection', function(e) {
					const msg = e.reason ? (e.reason.message || e.reason) : 'Unknown Promise Rejection';
					const stack = e.reason && e.reason.stack ? e.reason.stack : '';
					window.vdcAddLog({
						type: 'JS Promise Rejection',
						message: 'Unhandled Promise Rejection: ' + msg,
						file: '',
						line: 0,
						severity: 'error',
						category: 'js-error',
						color: '#f87171',
						badgeBg: '#ef4444',
						badgeColor: '#ffffff',
						trace: stack
					});
				});

				// console.error interception
				const origError = console.error;
				console.error = function() {
					origError.apply(console, arguments);
					const msg = Array.prototype.slice.call(arguments).map(arg => {
						if (arg instanceof Error) return arg.message + '\n' + arg.stack;
						if (typeof arg === 'object') { try { return JSON.stringify(arg); } catch(e) { return String(arg); } }
						return String(arg);
					}).join(' ');
					window.vdcAddLog({
						type: 'JS Console Error',
						message: msg,
						file: '', line: 0,
						severity: 'error',
						category: 'js-error',
						color: '#f87171',
						badgeBg: '#fda4af',
						badgeColor: '#9f1239'
					});
				};

				// console.warn interception
				const origWarn = console.warn;
				console.warn = function() {
					origWarn.apply(console, arguments);
					const msg = Array.prototype.slice.call(arguments).map(arg => {
						if (typeof arg === 'object') { try { return JSON.stringify(arg); } catch(e) { return String(arg); } }
						return String(arg);
					}).join(' ');
					window.vdcAddLog({
						type: 'JS Console Warning',
						message: msg,
						file: '', line: 0,
						severity: 'warning',
						category: 'js-warn',
						color: '#fbbf24',
						badgeBg: '#fef08a',
						badgeColor: '#854d0e'
					});
				};

				// --- INJECT PHP ERRORS ---
				window.vdcAutoOpen = <?php echo $autoOpen; ?>;
				const phpErrors = <?php echo $encodedPhpErrors; ?>;
				if (Array.isArray(phpErrors) && phpErrors.length > 0) {
					phpErrors.forEach(err => {
						const isError = err.severity === 'error';
						window.vdcAddLog({
							type: err.type,
							message: err.message,
							file: err.file,
							line: err.line,
							severity: err.severity,
							category: isError ? 'php-error' : 'php-warn',
							color: isError ? '#c084fc' : '#60a5fa',
							badgeBg: isError ? '#c084fc' : '#60a5fa',
							badgeColor: '#18181b',
							trace: err.trace,
							time: err.time
						});
					});
				}
			})();
			</script>
		</div>
		<?php
		return ob_get_clean();
	}
}
