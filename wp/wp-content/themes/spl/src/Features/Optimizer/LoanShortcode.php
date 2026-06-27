<?php
/**
 * Loan Calculator Shortcode — [loan_calculator].
 *
 * Renders an installment calculator widget for Vietnamese e-vehicles.
 * Supports 0% interest (industry standard) and custom interest rates.
 * Auto-detects product price when used on a single product page.
 *
 * Usage:
 *   [loan_calculator]                     — auto-detect product price
 *   [loan_calculator price="19900000"]     — explicit price
 *   [loan_calculator months="6,12,18,24"] — custom term options
 *   [loan_calculator rate="0"]            — annual interest rate (default 0%)
 *
 * @package SPL\Features\Optimizer
 */

declare( strict_types=1 );

namespace SPL\Features\Optimizer;

defined( 'ABSPATH' ) || exit;

final class LoanShortcode {

	/**
	 * Register the shortcode.
	 */
	public static function register(): void {
		add_shortcode( 'loan_calculator', [ self::class, 'render' ] );
	}

	/**
	 * Render the loan calculator widget.
	 *
	 * @param array<string, string>|string $atts Shortcode attributes.
	 *
	 * @return string HTML output.
	 */
	public static function render( array|string $atts = [] ): string {
		$atts = shortcode_atts( [
			'price'  => '',
			'months' => '6,12,18,24',
			'rate'   => '0',
		], $atts, 'loan_calculator' );

		// Auto-detect product price on single product pages.
		$price = (float) $atts['price'];
		if ( $price <= 0 && function_exists( 'is_product' ) && is_product() ) {
			$product = wc_get_product( get_the_ID() );
			if ( $product ) {
				$price = (float) $product->get_price();
			}
		}

		$months     = array_filter( array_map( 'absint', explode( ',', $atts['months'] ) ) );
		$rate       = max( 0, (float) $atts['rate'] );
		$uid        = 'loan-calc-' . wp_unique_id();
		$price_fmt  = $price > 0 ? number_format( $price, 0, ',', '.' ) : '';

		if ( ! $months ) {
			$months = [ 6, 12, 18, 24 ];
		}

		ob_start();
		?>
		<div class="dxd-loan-calc rounded-2xl border border-slate-200 bg-white p-5 md:p-6" id="<?= esc_attr( $uid ) ?>"
			data-rate="<?= esc_attr( (string) $rate ) ?>"
			data-months="<?= esc_attr( implode( ',', $months ) ) ?>">

			<h4 class="text-base font-bold text-slate-800 mb-4 flex items-center gap-2">
				<svg class="w-5 h-5 text-primary-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
					<path stroke-linecap="round" stroke-linejoin="round" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
				</svg>
				<?php esc_html_e( 'Tính trả góp', 'spl' ); ?>
			</h4>

			<!-- Price input -->
			<div class="mb-4">
				<label for="<?= esc_attr( $uid ) ?>-price" class="block text-xs font-semibold text-slate-600 mb-1.5">
					<?php esc_html_e( 'Giá sản phẩm (₫)', 'spl' ); ?>
				</label>
				<input
					type="text"
					id="<?= esc_attr( $uid ) ?>-price"
					class="w-full px-4 py-2.5 border border-slate-200 rounded-xl text-sm font-semibold text-slate-800 bg-slate-50 focus:border-primary-400 focus:ring-2 focus:ring-primary-100 outline-none transition-all"
					value="<?= esc_attr( $price_fmt ) ?>"
					placeholder="19.900.000"
					inputmode="numeric"
					<?= $price > 0 ? 'readonly' : '' ?>
				/>
			</div>

			<!-- Down payment -->
			<div class="mb-4">
				<label for="<?= esc_attr( $uid ) ?>-down" class="block text-xs font-semibold text-slate-600 mb-1.5">
					<?php esc_html_e( 'Trả trước (%)', 'spl' ); ?>
				</label>
				<div class="flex gap-2">
					<?php foreach ( [ 0, 10, 20, 30, 50 ] as $pct ) : ?>
						<button type="button"
							class="dxd-loan-down-btn flex-1 px-2 py-2 rounded-lg border text-xs font-semibold transition-all
								<?= $pct === 0 ? 'border-primary-500 bg-primary-50 text-primary-600' : 'border-slate-200 bg-white text-slate-600 hover:border-primary-300' ?>"
							data-pct="<?= $pct ?>">
							<?= $pct ?>%
						</button>
					<?php endforeach; ?>
				</div>
			</div>

			<!-- Term selection -->
			<div class="mb-5">
				<label class="block text-xs font-semibold text-slate-600 mb-1.5">
					<?php esc_html_e( 'Kỳ hạn (tháng)', 'spl' ); ?>
				</label>
				<div class="flex gap-2">
					<?php foreach ( $months as $i => $m ) : ?>
						<button type="button"
							class="dxd-loan-term-btn flex-1 px-2 py-2 rounded-lg border text-xs font-semibold transition-all
								<?= $i === 0 ? 'border-primary-500 bg-primary-50 text-primary-600' : 'border-slate-200 bg-white text-slate-600 hover:border-primary-300' ?>"
							data-months="<?= $m ?>">
							<?= $m ?> <?php esc_html_e( 'tháng', 'spl' ); ?>
						</button>
					<?php endforeach; ?>
				</div>
			</div>

			<!-- Result -->
			<div class="bg-gradient-to-r from-primary-50 to-blue-50 border border-primary-100 rounded-xl p-4 text-center">
				<p class="text-xs text-slate-500 mb-1"><?php esc_html_e( 'Số tiền trả hàng tháng', 'spl' ); ?></p>
				<p class="dxd-loan-result text-2xl md:text-3xl font-black text-primary-600">—</p>
				<p class="text-[10px] text-slate-400 mt-1">
					<?php
					if ( $rate > 0 ) {
						printf( esc_html__( 'Lãi suất %s%%/năm', 'spl' ), number_format( $rate, 1 ) );
					} else {
						esc_html_e( 'Lãi suất 0% — Trả góp qua CMND/CCCD', 'spl' );
					}
					?>
				</p>
			</div>
		</div>

		<script>
		(function(){
			var el = document.getElementById('<?= esc_js( $uid ) ?>');
			if (!el) return;

			var rate     = parseFloat(el.dataset.rate) / 100 / 12; // monthly rate
			var priceEl  = el.querySelector('[id$="-price"]');
			var resultEl = el.querySelector('.dxd-loan-result');
			var downBtns = el.querySelectorAll('.dxd-loan-down-btn');
			var termBtns = el.querySelectorAll('.dxd-loan-term-btn');
			var downPct  = 0;
			var months   = parseInt(termBtns[0] ? termBtns[0].dataset.months : 12);

			function parsePrice(s) {
				return parseInt(String(s).replace(/[^\d]/g, ''), 10) || 0;
			}

			function formatVND(n) {
				return n.toLocaleString('vi-VN') + '₫';
			}

			function calc() {
				var price = parsePrice(priceEl.value);
				if (price <= 0 || months <= 0) { resultEl.textContent = '—'; return; }

				var loanAmount = price * (1 - downPct / 100);
				var monthly;

				if (rate === 0) {
					monthly = loanAmount / months;
				} else {
					// PMT formula: M = P * [r(1+r)^n] / [(1+r)^n - 1]
					var pow = Math.pow(1 + rate, months);
					monthly = loanAmount * (rate * pow) / (pow - 1);
				}

				resultEl.textContent = formatVND(Math.round(monthly)) + '/tháng';
			}

			function setActive(btns, activeBtn) {
				btns.forEach(function(b) {
					b.classList.remove('border-primary-500', 'bg-primary-50', 'text-primary-600');
					b.classList.add('border-slate-200', 'bg-white', 'text-slate-600');
				});
				activeBtn.classList.remove('border-slate-200', 'bg-white', 'text-slate-600');
				activeBtn.classList.add('border-primary-500', 'bg-primary-50', 'text-primary-600');
			}

			downBtns.forEach(function(btn) {
				btn.addEventListener('click', function() {
					downPct = parseInt(btn.dataset.pct);
					setActive(downBtns, btn);
					calc();
				});
			});

			termBtns.forEach(function(btn) {
				btn.addEventListener('click', function() {
					months = parseInt(btn.dataset.months);
					setActive(termBtns, btn);
					calc();
				});
			});

			// Format price input as user types (when editable).
			if (!priceEl.readOnly) {
				priceEl.addEventListener('input', function() {
					var v = parsePrice(priceEl.value);
					if (v > 0) priceEl.value = v.toLocaleString('vi-VN');
					calc();
				});
			}

			// Initial calc.
			calc();
		})();
		</script>
		<?php

		return ob_get_clean();
	}
}
