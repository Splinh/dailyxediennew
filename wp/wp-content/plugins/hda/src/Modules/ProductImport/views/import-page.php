<?php
/**
 * Import page view.
 *
 * @var string $nonce WP nonce for AJAX.
 * @package HDAddons\Modules\ProductImport
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="wrap" id="spl-import-app">
	<h1>📦 Import Sản Phẩm + ACF TSKT</h1>
	<p>Upload file XML được export từ site cũ → preview → import sản phẩm, ảnh, ACF thông số kỹ thuật.</p>

	<!-- Step 1: Upload -->
	<div class="imp-card" id="step-upload">
		<h2>📁 Bước 1: Upload file XML</h2>
		<p>Chọn file XML (WXR) đã export từ site cũ:</p>
		<input type="file" id="imp-file" accept=".xml" />
		<button class="button button-primary button-hero" id="imp-parse-btn" type="button">
			🔍 Phân tích file
		</button>
		<div id="imp-parse-status" class="imp-status"></div>
	</div>

	<!-- Step 2: Preview -->
	<div class="imp-card" id="step-preview" style="display:none">
		<h2>👁️ Bước 2: Xem trước dữ liệu</h2>

		<div class="imp-stats" id="imp-stats"></div>

		<div class="imp-table-wrap">
			<table class="wp-list-table widefat fixed striped" id="imp-preview-table">
				<thead>
					<tr>
						<th style="width:40px"><input type="checkbox" id="imp-check-all" checked /></th>
						<th>ID cũ</th>
						<th>Tên sản phẩm</th>
						<th>Loại</th>
						<th>Trạng thái</th>
						<th>TSKT</th>
						<th>Danh mục</th>
					</tr>
				</thead>
				<tbody id="imp-preview-body"></tbody>
			</table>
		</div>

		<div class="imp-options">
			<label><input type="checkbox" id="imp-opt-images" checked /> Tải ảnh từ site cũ (featured + gallery)</label>
			<label><input type="checkbox" id="imp-opt-skip" checked /> Bỏ qua sản phẩm đã tồn tại (theo slug)</label>
		</div>

		<button class="button button-primary button-hero" id="imp-execute-btn" type="button">
			🚀 Bắt đầu Import
		</button>
	</div>

	<!-- Step 3: Progress -->
	<div class="imp-card" id="step-progress" style="display:none">
		<h2>⏳ Đang import...</h2>
		<div class="imp-progress">
			<div class="imp-progress-bar" id="imp-progress-bar"></div>
		</div>
		<p id="imp-progress-text">Đang xử lý sản phẩm, ảnh, TSKT...</p>
	</div>

	<!-- Step 4: Result -->
	<div class="imp-card" id="step-result" style="display:none">
		<h2>📊 Kết quả Import</h2>
		<div id="imp-result-stats" class="imp-stats"></div>
		<div id="imp-log" class="imp-log"></div>
	</div>
</div>

<script>
(function($){
	var nonce = '<?php echo esc_js( $nonce ); ?>';
	var tempFile = '';

	// ── Step 1: Parse XML ──
	$('#imp-parse-btn').on('click', function(){
		var file = $('#imp-file')[0].files[0];
		if (!file) {
			$('#imp-parse-status').attr('class','imp-status error').text('❌ Chọn file XML trước!');
			return;
		}
		if (!file.name.endsWith('.xml')) {
			$('#imp-parse-status').attr('class','imp-status error').text('❌ File phải có định dạng .xml!');
			return;
		}

		var fd = new FormData();
		fd.append('action', 'spl_import_parse');
		fd.append('nonce', nonce);
		fd.append('xml_file', file);

		var btn = $(this);
		btn.prop('disabled', true).text('Đang phân tích...');
		$('#imp-parse-status').attr('class','imp-status').text('');

		$.ajax({
			url: ajaxurl,
			method: 'POST',
			data: fd,
			processData: false,
			contentType: false,
			success: function(res) {
				btn.prop('disabled', false).html('🔍 Phân tích file');
				if (!res.success) {
					$('#imp-parse-status').attr('class','imp-status error').text('❌ ' + res.data);
					return;
				}
				tempFile = res.data.temp_file;
				renderPreview(res.data);
			},
			error: function() {
				btn.prop('disabled', false).html('🔍 Phân tích file');
				$('#imp-parse-status').attr('class','imp-status error').text('❌ Lỗi kết nối');
			}
		});
	});

	// ── Render Preview ──
	function renderPreview(data) {
		var products   = data.products.filter(function(p){ return p.type === 'product'; });
		var variations = data.products.filter(function(p){ return p.type === 'product_variation'; });
		var tsktCount  = products.filter(function(p){ return p.tskt > 0; }).length;

		$('#imp-stats').html(
			'<div class="imp-stat">🛍️ Sản phẩm: <strong>' + products.length + '</strong></div>' +
			'<div class="imp-stat">🔀 Biến thể: <strong>' + variations.length + '</strong></div>' +
			'<div class="imp-stat">📊 Có TSKT: <strong>' + tsktCount + '</strong></div>' +
			'<div class="imp-stat">🖼️ Ảnh: <strong>' + data.attachments + '</strong></div>'
		);

		var body = '';
		data.products.forEach(function(p) {
			if (p.type !== 'product') return;
			var tsktBadge = p.tskt > 0
				? '<span class="imp-badge success">✅ ' + p.tskt + ' dòng</span>'
				: '<span class="imp-badge">—</span>';

			body += '<tr>' +
				'<td><input type="checkbox" class="imp-check-item" value="' + p.id + '" checked /></td>' +
				'<td>' + p.id + '</td>' +
				'<td><strong>' + $('<span>').text(p.title).html() + '</strong></td>' +
				'<td>' + p.type + '</td>' +
				'<td>' + p.status + '</td>' +
				'<td>' + tsktBadge + '</td>' +
				'<td>' + $('<span>').text(p.cats).html() + '</td>' +
				'</tr>';
		});

		$('#imp-preview-body').html(body);
		$('#step-preview').show();
		$('#imp-parse-status').attr('class','imp-status success')
			.text('✅ Đọc thành công ' + data.total_items + ' items');
	}

	// ── Check all ──
	$('#imp-check-all').on('change', function(){
		$('.imp-check-item').prop('checked', this.checked);
	});

	// ── Step 2: Execute Import ──
	$('#imp-execute-btn').on('click', function(){
		if (!tempFile) { alert('Chưa có file để import!'); return; }

		var btn = $(this);
		btn.prop('disabled', true).text('Đang import...');
		$('#step-progress').show();
		$('#step-result').hide();
		$('#imp-progress-bar').css('width', '30%');

		$.post(ajaxurl, {
			action: 'spl_import_execute',
			nonce: nonce,
			temp_file: tempFile,
			import_images: $('#imp-opt-images').is(':checked') ? 1 : 0,
			skip_existing: $('#imp-opt-skip').is(':checked') ? 1 : 0
		}, function(res){
			$('#imp-progress-bar').css('width', '100%');
			btn.prop('disabled', false).html('🚀 Bắt đầu Import');
			$('#step-progress').hide();

			if (!res.success) {
				alert('❌ ' + res.data);
				return;
			}

			var d = res.data;
			$('#imp-result-stats').html(
				'<div class="imp-stat success">✅ Tạo mới: <strong>' + d.created + '</strong></div>' +
				'<div class="imp-stat">⏭️ Bỏ qua: <strong>' + d.skipped + '</strong></div>' +
				'<div class="imp-stat">🔀 Biến thể: <strong>' + d.variations + '</strong></div>' +
				'<div class="imp-stat">📊 TSKT: <strong>' + d.tskt + '</strong></div>' +
				'<div class="imp-stat">🖼️ Ảnh: <strong>' + d.images + '</strong></div>' +
				(d.errors.length ? '<div class="imp-stat error">❌ Lỗi: <strong>' + d.errors.length + '</strong></div>' : '')
			);

			var logHtml = '';
			d.log.forEach(function(line){
				logHtml += '<div class="imp-log-line">' + $('<span>').text(line).html() + '</div>';
			});
			if (d.errors.length) {
				logHtml += '<h4>Chi tiết lỗi:</h4>';
				d.errors.forEach(function(e){
					logHtml += '<div class="imp-log-line error">' + $('<span>').text(e).html() + '</div>';
				});
			}
			$('#imp-log').html(logHtml);
			$('#step-result').show();

		}).fail(function(){
			btn.prop('disabled', false).html('🚀 Bắt đầu Import');
			$('#step-progress').hide();
			alert('Lỗi kết nối server! Có thể do timeout – thử import ít sản phẩm hơn.');
		});
	});
})(jQuery);
</script>
