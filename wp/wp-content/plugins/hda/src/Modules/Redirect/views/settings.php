<?php
/**
 * Redirect module options panel.
 *
 * Two distinct sections:
 * 1. Redirect Rules (301/302) — table with inline editing, search, import/export.
 * 2. HTTP Status Code Rules (401/410) — same repeater UI, with different accent.
 *
 * @package HDAddons\Modules\Redirect
 */

use HDAddons\Modules\Redirect\RedirectRuleService;
use HDAddons\Modules\Redirect\StatusCodeRuleService;

\defined( 'ABSPATH' ) || exit;

// ── Redirect Rules (301/302) ────────────────────
// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only pagination param.
$current_page = max( 1, absint( wp_unslash( $_GET['redirect_page'] ?? 1 ) ) );
$paginated    = RedirectRuleService::getPaginated( $current_page );
$rules        = $paginated['rules'];
$total        = $paginated['total'];
$total_pages  = $paginated['total_pages'];
$page         = $paginated['page'];
$offset       = $paginated['offset'];

// ── Status Code Rules (401/410) ─────────────────
// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only pagination param.
$sc_current_page = max( 1, absint( wp_unslash( $_GET['sc_page'] ?? 1 ) ) );
$sc_paginated    = StatusCodeRuleService::getPaginated( $sc_current_page );
$sc_rules        = $sc_paginated['rules'];
$sc_total        = $sc_paginated['total'];
$sc_total_pages  = $sc_paginated['total_pages'];
$sc_page         = $sc_paginated['page'];
$sc_offset       = $sc_paginated['offset'];

// Build base URLs for pagination links (each preserves its own tab + hash context).
$base_url      = remove_query_arg( [ 'redirect_page', 'sc_page', 'tab' ] );
$hash          = '#redirect_settings';
$redirect_base = add_query_arg( 'tab', 'redirect', $base_url ) . $hash;
$sc_base       = add_query_arg( 'tab', 'status_code', $base_url ) . $hash;

?>
<div class="container">
	<input type="hidden" name="hda_redirect[redirect_page]" value="<?php echo esc_attr( $page ); ?>">
	<input type="hidden" name="hda_redirect[sc_page]" value="<?php echo esc_attr( $sc_page ); ?>">

	<!-- ── Tab Navigation ─────────────────────────── -->
	<?php
	$active_tab = sanitize_key( $_GET['tab'] ?? 'redirect' );
	if ( ! in_array( $active_tab, [ 'redirect', 'status_code' ], true ) ) {
		$active_tab = 'redirect';
	}
	?>
	<nav class="hda-redirect-tabs">
		<a href="<?php echo esc_url( $redirect_base ); ?>"
			class="hda-redirect-tabs__tab<?php echo 'redirect' === $active_tab ? ' is-active' : ''; ?>"
			data-tab="redirect">
			<span class="dashicons dashicons-randomize"></span>
			<?php esc_html_e( 'Redirect Rules', 'hda' ); ?>
			<span class="hda-redirect-tabs__badge"><?php echo esc_html( $total ); ?></span>
		</a>
		<a href="<?php echo esc_url( $sc_base ); ?>"
			class="hda-redirect-tabs__tab<?php echo 'status_code' === $active_tab ? ' is-active' : ''; ?>"
			data-tab="status_code">
			<span class="dashicons dashicons-shield"></span>
			<?php esc_html_e( 'Status Code Rules', 'hda' ); ?>
			<span class="hda-redirect-tabs__badge hda-redirect-tabs__badge--amber"><?php echo esc_html( $sc_total ); ?></span>
		</a>
	</nav>

	<!-- ═══════════════════════════════════════════════ -->
	<!-- Tab: Redirect Rules (301/302)                  -->
	<!-- ═══════════════════════════════════════════════ -->
	<div class="hda-redirect-tab-panel<?php echo 'redirect' === $active_tab ? ' is-active' : ''; ?>" data-panel="redirect">
	<fieldset class="container-fieldset mt-0">
		<legend class="section-legend"><?php esc_html_e( 'Redirect Rules', 'hda' ); ?></legend>

		<div class="hda-notice hda-notice--info mb-6">
			<p class="mb-2">
				<span class="dashicons dashicons-randomize"></span>
				<?php esc_html_e( 'Source: relative path (/). Destination: full URL. Case-insensitive; trailing slashes ignored.', 'hda' ); ?>
			</p>
			<p class="text-amber-700">
				<span class="dashicons dashicons-warning"></span>
				<?php esc_html_e( 'Deletes take effect immediately without saving.', 'hda' ); ?>
			</p>
		</div>

		<!-- ── Toolbar: Add / Search / Import / Export ──────── -->
		<div class="flex items-center justify-between flex-wrap gap-2.5 mb-4">
			<div class="flex items-center flex-wrap gap-2">
				<button type="button" id="hda-redirect-add" class="button inline-flex! items-center gap-1">
					<span class="dashicons dashicons-plus-alt2"></span>
					<?php esc_html_e( 'Add', 'hda' ); ?>
				</button>
				<button type="button" id="hda-redirect-delete-selected" class="button inline-flex! items-center gap-1" style="display:none;">
					<span class="dashicons dashicons-trash"></span>
					<?php esc_html_e( 'Delete Selected', 'hda' ); ?>
				</button>
				<button type="button" id="hda-redirect-delete-all" class="button inline-flex! items-center gap-1" 
				<?php
				if ( $total === 0 ) {
					echo 'style="display:none;"';}
				?>
				>
					<span class="dashicons dashicons-dismiss"></span>
					<?php esc_html_e( 'Delete All', 'hda' ); ?>
				</button>
			</div>
			<div class="flex items-center flex-wrap gap-2">
				<!-- Search -->
				<div class="flex items-center" 
				<?php
				if ( $total === 0 ) {
					echo 'style="display:none;"';}
				?>
				>
					<input type="search" id="hda-redirect-search" class="h-8 px-2 text-sm border border-slate-300 rounded bg-slate-50 min-w-44 transition-colors focus:border-wp-primary focus:outline-none" placeholder="<?php esc_attr_e( 'Search rules...', 'hda' ); ?>">
				</div>

				<!-- Import -->
				<span class="inline-flex items-center gap-1">
					<input type="file" id="hda-redirect-import-file" accept=".csv,.xlsx" style="display:none;">
					<button type="button" id="hda-redirect-import-btn" class="button inline-flex! items-center gap-1" title="<?php esc_attr_e( 'Import CSV or XLSX', 'hda' ); ?>">
						<span class="dashicons dashicons-upload"></span>
						<?php esc_html_e( 'Import', 'hda' ); ?>
					</button>
					<select id="hda-redirect-import-mode" class="h-8 min-w-24 px-2 text-sm border border-slate-300 rounded bg-slate-50">
						<option value="append"><?php esc_html_e( 'Append', 'hda' ); ?></option>
						<option value="replace"><?php esc_html_e( 'Replace All', 'hda' ); ?></option>
					</select>
				</span>

				<!-- Export -->
				<?php if ( $total > 0 ) : ?>
					<span class="inline-flex items-center gap-1">
						<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-ajax.php?action=hda_redirect_export&format=csv' ), 'hda_redirect_manage', '_nonce' ) ); ?>" class="button inline-flex! items-center gap-1" title="<?php esc_attr_e( 'Export CSV', 'hda' ); ?>">
							<span class="dashicons dashicons-download"></span>
							CSV
						</a>
						<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-ajax.php?action=hda_redirect_export&format=xlsx' ), 'hda_redirect_manage', '_nonce' ) ); ?>" class="button inline-flex! items-center gap-1" title="<?php esc_attr_e( 'Export XLSX', 'hda' ); ?>">
							<span class="dashicons dashicons-download"></span>
							XLSX
						</a>
					</span>
				<?php endif; ?>
			</div>
		</div>

		<!-- ── Import Status ──────────────────────────── -->
		<div id="hda-redirect-import-status" class="mb-3 py-2 px-3 text-sm rounded bg-blue-50 border-l-3 border-blue-400" style="display:none;"></div>

		<!-- ── Table ──────────────────────────────────── -->
		<div class="overflow-x-auto" id="hda-redirect-table-wrap" 
		<?php
		if ( $total === 0 ) {
			echo 'style="display:none;"';}
		?>
		>
			<table class="widefat striped hda-redirect-table" id="hda-redirect-table">
				<thead>
					<tr>
						<th class="hda-redirect-table__cb"><input type="checkbox" id="hda-redirect-select-all" title="<?php esc_attr_e( 'Select All', 'hda' ); ?>"></th>
						<th class="hda-redirect-table__num">#</th>
						<th class="hda-redirect-table__from"><?php esc_html_e( 'From (path)', 'hda' ); ?></th>
						<th class="hda-redirect-table__to"><?php esc_html_e( 'To (URL)', 'hda' ); ?></th>
						<th class="hda-redirect-table__type"><?php esc_html_e( 'Type', 'hda' ); ?></th>
						<th class="hda-redirect-table__actions"><?php esc_html_e( 'Actions', 'hda' ); ?></th>
					</tr>
				</thead>
				<tbody id="hda-redirect-rules">
					<?php
					foreach ( $rules as $i => $rule ) :
						$type_label = ( (int) ( $rule['type'] ?? 301 ) === 302 ) ? '302' : '301';
						?>
						<tr class="hda-redirect-row" data-index="<?php echo esc_attr( $offset + $i ); ?>">
							<td class="hda-redirect-table__cb"><input type="checkbox" class="hda-redirect-cb"></td>
							<td class="hda-redirect-table__num"><?php echo esc_html( $offset + $i + 1 ); ?></td>
							<td>
								<span class="hda-redirect-display"><?php echo esc_html( $rule['from'] ); ?></span>
								<input type="hidden" name="hda_redirect[redirect_old_from][]" value="<?php echo esc_attr( $rule['from'] ); ?>">
								<input type="text" class="input hda-redirect-input" name="hda_redirect[redirect_from][]" value="<?php echo esc_attr( $rule['from'] ); ?>" placeholder="/old-page" readonly>
							</td>
							<td>
								<span class="hda-redirect-display"><?php echo esc_html( $rule['to'] ); ?></span>
								<input type="url" class="input hda-redirect-input" name="hda_redirect[redirect_to][]" value="<?php echo esc_url( $rule['to'] ); ?>" placeholder="https://example.com/new-page" readonly>
							</td>
							<td>
								<span class="hda-redirect-display"><?php echo esc_html( $type_label ); ?></span>
								<input type="hidden" name="hda_redirect[redirect_type][]" value="<?php echo esc_attr( $rule['type'] ?? 301 ); ?>" class="hda-redirect-type-hidden">
								<select class="select hda-redirect-select" data-name="hda_redirect[redirect_type]" disabled>
									<option value="301" <?php selected( $rule['type'] ?? 301, 301 ); ?>>301</option>
									<option value="302" <?php selected( $rule['type'] ?? 301, 302 ); ?>>302</option>
								</select>
							</td>
							<td class="hda-redirect-table__actions-cell">
								<button type="button" class="button button-small hda-redirect-edit" title="<?php esc_attr_e( 'Edit', 'hda' ); ?>">
									<span class="dashicons dashicons-edit"></span>
								</button>
								<button type="button" class="button button-small hda-redirect-save" title="<?php esc_attr_e( 'Save', 'hda' ); ?>">
									<span class="dashicons dashicons-yes"></span>
								</button>
								<button type="button" class="button button-small hda-redirect-remove" title="<?php esc_attr_e( 'Delete', 'hda' ); ?>">
									<span class="dashicons dashicons-trash"></span>
								</button>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>

		<?php if ( $total === 0 ) : ?>
			<p class="text-slate-400 italic mt-2" id="hda-redirect-empty">
				<?php esc_html_e( 'No redirect rules. Click "Add" or import a file to get started.', 'hda' ); ?>
			</p>
		<?php endif; ?>

		<!-- ── Pagination ─────────────────────────── -->
		<?php if ( $total_pages > 1 ) : ?>
			<div class="flex items-center justify-between flex-wrap gap-2.5 mt-4 pt-3 border-t border-slate-200">
				<span class="text-sm text-slate-500">
					<?php
					printf(
						/* translators: %1$d–%2$d of %3$d */
						esc_html__( 'Showing %1$d–%2$d of %3$d rules', 'hda' ),
						$offset + 1,
						min( $offset + RedirectRuleService::PER_PAGE, $total ),
						$total
					);
					?>
				</span>
				<span class="flex gap-1 items-center">
					<?php if ( $page > 1 ) : ?>
						<a href="<?php echo esc_url( add_query_arg( 'redirect_page', $page - 1, $redirect_base ) ); ?>" class="button button-small min-w-8 text-center">&laquo; <?php esc_html_e( 'Prev', 'hda' ); ?></a>
					<?php endif; ?>

					<?php for ( $p = 1; $p <= $total_pages; $p++ ) : ?>
						<?php if ( $p === $page ) : ?>
							<span class="button button-small button-primary min-w-8 text-center cursor-default pointer-events-none"><?php echo esc_html( $p ); ?></span>
						<?php else : ?>
							<a href="<?php echo esc_url( add_query_arg( 'redirect_page', $p, $redirect_base ) ); ?>" class="button button-small min-w-8 text-center"><?php echo esc_html( $p ); ?></a>
						<?php endif; ?>
					<?php endfor; ?>

					<?php if ( $page < $total_pages ) : ?>
						<a href="<?php echo esc_url( add_query_arg( 'redirect_page', $page + 1, $redirect_base ) ); ?>" class="button button-small min-w-8 text-center"><?php esc_html_e( 'Next', 'hda' ); ?> &raquo;</a>
					<?php endif; ?>
				</span>
			</div>
		<?php endif; ?>
	</fieldset>
	</div>

	<!-- ═══════════════════════════════════════════════ -->
	<!-- Tab: HTTP Status Code Rules (401/410)          -->
	<!-- ═══════════════════════════════════════════════ -->
	<div class="hda-redirect-tab-panel<?php echo 'status_code' === $active_tab ? ' is-active' : ''; ?>" data-panel="status_code">
	<fieldset class="container-fieldset mt-0 hda-sc-fieldset">
		<legend class="section-legend"><?php esc_html_e( 'HTTP Status Code Rules', 'hda' ); ?></legend>

		<div class="hda-notice hda-notice--warning mb-6">
			<p class="mb-2">
				<span class="dashicons dashicons-shield"></span>
				<?php esc_html_e( 'Define URLs that should return specific HTTP status codes (e.g. 410 Gone, 401 Unauthorized). These are NOT redirects — the server will respond with the status code directly.', 'hda' ); ?>
			</p>
			<p class="text-amber-700">
				<span class="dashicons dashicons-warning"></span>
				<?php esc_html_e( 'Deletes take effect immediately without saving.', 'hda' ); ?>
			</p>
		</div>

		<!-- ── SC Toolbar ──────────────────────────── -->
		<div class="flex items-center justify-between flex-wrap gap-2.5 mb-4">
			<div class="flex items-center flex-wrap gap-2">
				<button type="button" id="hda-sc-add" class="button inline-flex! items-center gap-1">
					<span class="dashicons dashicons-plus-alt2"></span>
					<?php esc_html_e( 'Add', 'hda' ); ?>
				</button>
				<button type="button" id="hda-sc-delete-selected" class="button inline-flex! items-center gap-1" style="display:none;">
					<span class="dashicons dashicons-trash"></span>
					<?php esc_html_e( 'Delete Selected', 'hda' ); ?>
				</button>
				<button type="button" id="hda-sc-delete-all" class="button inline-flex! items-center gap-1" 
				<?php
				if ( $sc_total === 0 ) {
					echo 'style="display:none;"';}
				?>
				>
					<span class="dashicons dashicons-dismiss"></span>
					<?php esc_html_e( 'Delete All', 'hda' ); ?>
				</button>
			</div>
			<div class="flex items-center flex-wrap gap-2">
				<!-- Search -->
				<div class="flex items-center" 
				<?php
				if ( $sc_total === 0 ) {
					echo 'style="display:none;"';}
				?>
				>
					<input type="search" id="hda-sc-search" class="h-8 px-2 text-sm border border-slate-300 rounded bg-slate-50 min-w-44 transition-colors focus:border-wp-primary focus:outline-none" placeholder="<?php esc_attr_e( 'Search rules...', 'hda' ); ?>">
				</div>

				<!-- Import -->
				<span class="inline-flex items-center gap-1">
					<input type="file" id="hda-sc-import-file" accept=".csv,.xlsx" style="display:none;">
					<button type="button" id="hda-sc-import-btn" class="button inline-flex! items-center gap-1" title="<?php esc_attr_e( 'Import CSV or XLSX', 'hda' ); ?>">
						<span class="dashicons dashicons-upload"></span>
						<?php esc_html_e( 'Import', 'hda' ); ?>
					</button>
					<select id="hda-sc-import-mode" class="h-8 min-w-24 px-2 text-sm border border-slate-300 rounded bg-slate-50">
						<option value="append"><?php esc_html_e( 'Append', 'hda' ); ?></option>
						<option value="replace"><?php esc_html_e( 'Replace All', 'hda' ); ?></option>
					</select>
				</span>

				<!-- Export -->
				<?php if ( $sc_total > 0 ) : ?>
					<span class="inline-flex items-center gap-1">
						<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-ajax.php?action=hda_status_code_export&format=csv' ), 'hda_redirect_manage', '_nonce' ) ); ?>" class="button inline-flex! items-center gap-1" title="<?php esc_attr_e( 'Export CSV', 'hda' ); ?>">
							<span class="dashicons dashicons-download"></span>
							CSV
						</a>
						<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-ajax.php?action=hda_status_code_export&format=xlsx' ), 'hda_redirect_manage', '_nonce' ) ); ?>" class="button inline-flex! items-center gap-1" title="<?php esc_attr_e( 'Export XLSX', 'hda' ); ?>">
							<span class="dashicons dashicons-download"></span>
							XLSX
						</a>
					</span>
				<?php endif; ?>
			</div>
		</div>

		<!-- ── SC Import Status ───────────────────── -->
		<div id="hda-sc-import-status" class="mb-3 py-2 px-3 text-sm rounded bg-amber-50 border-l-3 border-amber-400" style="display:none;"></div>

		<!-- ── SC Table ───────────────────────────── -->
		<div class="overflow-x-auto" id="hda-sc-table-wrap" 
		<?php
		if ( $sc_total === 0 ) {
			echo 'style="display:none;"';}
		?>
		>
			<table class="widefat striped hda-redirect-table hda-sc-table" id="hda-sc-table">
				<thead>
					<tr>
						<th class="hda-redirect-table__cb"><input type="checkbox" id="hda-sc-select-all" title="<?php esc_attr_e( 'Select All', 'hda' ); ?>"></th>
						<th class="hda-redirect-table__num">#</th>
						<th><?php esc_html_e( 'Path', 'hda' ); ?></th>
						<th class="hda-redirect-table__type"><?php esc_html_e( 'Code', 'hda' ); ?></th>
						<th class="hda-redirect-table__actions"><?php esc_html_e( 'Actions', 'hda' ); ?></th>
					</tr>
				</thead>
				<tbody id="hda-sc-rules">
					<?php
					foreach ( $sc_rules as $i => $rule ) :
						$code_label = (int) ( $rule['code'] ?? 410 );
						?>
						<tr class="hda-redirect-row hda-sc-row" data-index="<?php echo esc_attr( $sc_offset + $i ); ?>">
							<td class="hda-redirect-table__cb"><input type="checkbox" class="hda-sc-cb"></td>
							<td class="hda-redirect-table__num"><?php echo esc_html( $sc_offset + $i + 1 ); ?></td>
							<td>
								<span class="hda-redirect-display"><?php echo esc_html( $rule['path'] ); ?></span>
								<input type="hidden" name="hda_redirect[sc_old_path][]" value="<?php echo esc_attr( $rule['path'] ); ?>">
								<input type="text" class="input hda-redirect-input" name="hda_redirect[sc_path][]" value="<?php echo esc_attr( $rule['path'] ); ?>" placeholder="/removed-page" readonly>
							</td>
							<td>
								<span class="hda-redirect-display hda-sc-code-badge hda-sc-code-badge--<?php echo esc_attr( $code_label ); ?>"><?php echo esc_html( $code_label ); ?></span>
								<input type="hidden" name="hda_redirect[sc_code][]" value="<?php echo esc_attr( $code_label ); ?>" class="hda-sc-code-hidden">
								<select class="select hda-redirect-select" data-name="hda_redirect[sc_code]" disabled>
									<option value="410" <?php selected( $code_label, 410 ); ?>>410 Gone</option>
									<option value="401" <?php selected( $code_label, 401 ); ?>>401 Unauthorized</option>
								</select>
							</td>
							<td class="hda-redirect-table__actions-cell">
								<button type="button" class="button button-small hda-redirect-edit" title="<?php esc_attr_e( 'Edit', 'hda' ); ?>">
									<span class="dashicons dashicons-edit"></span>
								</button>
								<button type="button" class="button button-small hda-redirect-save" title="<?php esc_attr_e( 'Save', 'hda' ); ?>">
									<span class="dashicons dashicons-yes"></span>
								</button>
								<button type="button" class="button button-small hda-redirect-remove" title="<?php esc_attr_e( 'Delete', 'hda' ); ?>">
									<span class="dashicons dashicons-trash"></span>
								</button>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>

		<?php if ( $sc_total === 0 ) : ?>
			<p class="text-slate-400 italic mt-2" id="hda-sc-empty">
				<?php esc_html_e( 'No status code rules. Click "Add" or import a file to get started.', 'hda' ); ?>
			</p>
		<?php endif; ?>

		<!-- ── SC Pagination ──────────────────────── -->
		<?php if ( $sc_total_pages > 1 ) : ?>
			<div class="flex items-center justify-between flex-wrap gap-2.5 mt-4 pt-3 border-t border-slate-200">
				<span class="text-sm text-slate-500">
					<?php
					printf(
						/* translators: %1$d–%2$d of %3$d */
						esc_html__( 'Showing %1$d–%2$d of %3$d rules', 'hda' ),
						$sc_offset + 1,
						min( $sc_offset + StatusCodeRuleService::PER_PAGE, $sc_total ),
						$sc_total
					);
					?>
				</span>
				<span class="flex gap-1 items-center">
					<?php if ( $sc_page > 1 ) : ?>
						<a href="<?php echo esc_url( add_query_arg( 'sc_page', $sc_page - 1, $sc_base ) ); ?>" class="button button-small min-w-8 text-center">&laquo; <?php esc_html_e( 'Prev', 'hda' ); ?></a>
					<?php endif; ?>

					<?php for ( $p = 1; $p <= $sc_total_pages; $p++ ) : ?>
						<?php if ( $p === $sc_page ) : ?>
							<span class="button button-small button-primary min-w-8 text-center cursor-default pointer-events-none"><?php echo esc_html( $p ); ?></span>
						<?php else : ?>
							<a href="<?php echo esc_url( add_query_arg( 'sc_page', $p, $sc_base ) ); ?>" class="button button-small min-w-8 text-center"><?php echo esc_html( $p ); ?></a>
						<?php endif; ?>
					<?php endfor; ?>

					<?php if ( $sc_page < $sc_total_pages ) : ?>
						<a href="<?php echo esc_url( add_query_arg( 'sc_page', $sc_page + 1, $sc_base ) ); ?>" class="button button-small min-w-8 text-center"><?php esc_html_e( 'Next', 'hda' ); ?> &raquo;</a>
					<?php endif; ?>
				</span>
			</div>
		<?php endif; ?>
	</fieldset>
	</div>
</div>
