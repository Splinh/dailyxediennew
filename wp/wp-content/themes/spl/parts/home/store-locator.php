<?php
/**
 * Home page — Store Locator section.
 *
 * Queries local_store CPT directly. Outputs compact JSON for client-side
 * province / type filtering. No hardcoded fallback data.
 *
 * @package SPL
 */

defined( 'ABSPATH' ) || exit;

$data     = $args ?? [];
$title    = $data['title'] ?? __( 'Hệ thống cửa hàng & đại lý ủy quyền', 'spl' );
$subtitle = $data['subtitle'] ?? __( 'Tìm địa chỉ đại lý gần bạn nhất', 'spl' );

// ── Get stores from plugin helper (cached, single source of truth) ──
$stores    = [];
$provinces = [];

if ( function_exists( 'dxd_dealer_get_stores' ) ) {
	$stores = dxd_dealer_get_stores();
	foreach ( $stores as $s ) {
		if ( $s['p'] ) {
			$provinces[ $s['p'] ] = true;
		}
	}
}


// Store type terms for tabs.
$type_terms = get_terms( [
	'taxonomy'   => 'store_type',
	'hide_empty' => true,
	'orderby'    => 'term_id',
	'order'      => 'ASC',
] );
if ( is_wp_error( $type_terms ) ) {
	$type_terms = [];
}

// Sorted province list.
$prov_list = array_keys( $provinces );
sort( $prov_list );

// Prioritize provinces containing at least one store of the default type
if ( ! empty( $type_terms ) ) {
	$default_type = $type_terms[0]->slug;
	$provinces_with_default = [];
	foreach ( $stores as $s ) {
		if ( $s['p'] && $s['ty'] === $default_type ) {
			$provinces_with_default[ $s['p'] ] = true;
		}
	}
	if ( ! empty( $provinces_with_default ) ) {
		$group_with = [];
		$group_without = [];
		foreach ( $prov_list as $p ) {
			if ( isset( $provinces_with_default[ $p ] ) ) {
				$group_with[] = $p;
			} else {
				$group_without[] = $p;
			}
		}
		$prov_list = array_merge( $group_with, $group_without );
	}
}

// Dealer page link.
$dealer_pages = get_pages( [ 'meta_key' => '_wp_page_template', 'meta_value' => 'templates/template-page-daily.php', 'number' => 1 ] );
$dealer_url   = ! empty( $dealer_pages ) ? get_permalink( $dealer_pages[0] ) : '#';

// Skip rendering if no stores.
if ( empty( $stores ) ) {
	return;
}
?>
<section class="max-w-7xl mx-auto px-4 mb-8 md:mb-16 scroll-mt-24" id="store-section">
	<!-- Header -->
	<div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
		<div class="flex items-center gap-3">
			<span class="w-1.5 h-6 bg-amber-500 rounded-full"></span>
			<h2 class="text-2xl font-black text-[#0B2545] tracking-tight"><?php echo esc_html( $title ); ?></h2>
		</div>
		<a href="<?php echo esc_url( $dealer_url ); ?>" class="text-sm font-black text-[#0B2545] hover:text-amber-600 flex items-center gap-1 transition-colors">
			<?php esc_html_e( 'Xem tất cả', 'spl' ); ?>
			<svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>
		</a>
	</div>

	<!-- Province carousel -->
	<?php if ( $prov_list ) : ?>
	<div class="relative mb-6">
		<button onclick="dxdScrollProv('left')" aria-label="<?php esc_attr_e( 'Xem tỉnh trước', 'spl' ); ?>" class="absolute left-0 top-1/2 -translate-y-1/2 z-10 w-7 h-7 md:w-8 md:h-8 rounded-full bg-[#0B2545] hover:bg-[#13315C] text-white shadow-md flex items-center justify-center transition-all border-none">
			<svg class="w-3.5 h-3.5 text-white stroke-[2.5]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><polyline points="15 18 9 12 15 6"/></svg>
		</button>
		<div id="dxd-prov-scroll" class="flex gap-2 overflow-x-auto px-10 py-1.5 scroll-smooth" style="-ms-overflow-style:none;scrollbar-width:none;">
			<?php foreach ( $prov_list as $i => $prov ) :
				$cls = $i === 0
					? 'bg-amber-500 text-slate-950 font-black shadow-md'
					: 'bg-slate-100 text-slate-700 hover:bg-slate-200 font-bold';
			?>
				<button onclick="dxdFilterProv('<?php echo esc_js( $prov ); ?>',this)" class="dxd-prov px-4 py-2 text-xs rounded-full transition-all whitespace-nowrap <?php echo $cls; ?>">
					<?php echo esc_html( mb_strtoupper( $prov ) ); ?>
				</button>
			<?php endforeach; ?>
		</div>
		<button onclick="dxdScrollProv('right')" aria-label="<?php esc_attr_e( 'Xem tỉnh kế tiếp', 'spl' ); ?>" class="absolute right-0 top-1/2 -translate-y-1/2 z-10 w-7 h-7 md:w-8 md:h-8 rounded-full bg-[#0B2545] hover:bg-[#13315C] text-white shadow-md flex items-center justify-center transition-all border-none">
			<svg class="w-3.5 h-3.5 text-white stroke-[2.5]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><polyline points="9 6 15 12 9 18"/></svg>
		</button>
	</div>
	<?php endif; ?>

	<!-- Type tabs -->
	<?php if ( $type_terms ) : ?>
	<div class="flex items-center gap-6 border-b border-slate-200 pb-3 mb-6">
		<?php foreach ( $type_terms as $ti => $tt ) :
			$tab_cls = $ti === 0
				? 'text-amber-600 border-b-2 border-amber-500 font-black'
				: 'text-slate-400 hover:text-slate-600 font-bold';
		?>
			<button onclick="dxdFilterType('<?php echo esc_js( $tt->slug ); ?>',this)" class="dxd-type-tab flex items-center gap-2 text-sm pb-2 transition-all <?php echo $tab_cls; ?>">
				<?php echo esc_html( mb_strtoupper( $tt->name ) ); ?>
			</button>
		<?php endforeach; ?>
	</div>
	<?php endif; ?>

	<!-- Store cards grid (JS-rendered) -->
	<div id="dxd-home-stores" class="grid grid-cols-1 md:grid-cols-3 gap-5 min-h-[120px]"></div>
	<p id="dxd-home-empty" class="hidden text-center py-10 text-slate-400 text-sm"><?php esc_html_e( 'Không tìm thấy cửa hàng nào.', 'spl' ); ?></p>
</section>

<script>
(function(){
	var S=<?php echo wp_json_encode( $stores, JSON_UNESCAPED_UNICODE ); ?>;
	var cp='<?php echo esc_js( $prov_list[0] ?? '' ); ?>',ct='<?php echo esc_js( ! empty( $type_terms ) ? $type_terms[0]->slug : '' ); ?>';

	window.dxdScrollProv=function(d){var e=document.getElementById('dxd-prov-scroll');if(e)e.scrollBy({left:d==='left'?-200:200,behavior:'smooth'})};

	window.dxdFilterProv=function(n,b){
		cp=n;
		document.querySelectorAll('.dxd-prov').forEach(function(x){x.className=x.className.replace(/bg-amber-500 text-slate-950 font-black shadow-md/g,'bg-slate-100 text-slate-700 hover:bg-slate-200 font-bold')});
		if(b){b.className=b.className.replace(/bg-slate-100 text-slate-700 hover:bg-slate-200/g,'bg-amber-500 text-slate-950 font-black shadow-md')}
		render();
	};

	window.dxdFilterType=function(slug,b){
		ct=ct===slug?'':slug;
		document.querySelectorAll('.dxd-type-tab').forEach(function(x){x.classList.remove('text-amber-600','border-b-2','border-amber-500','font-black');x.classList.add('text-slate-400','font-bold')});
		if(b&&ct){b.classList.remove('text-slate-400');b.classList.add('text-amber-600','border-b-2','border-amber-500','font-black')}
		render();
	};

	function render(){
		var c=document.getElementById('dxd-home-stores'),e=document.getElementById('dxd-home-empty');if(!c)return;
		var f=S.filter(function(s){return(!cp||s.p===cp)&&(!ct||s.ty===ct)});
		if(!f.length){c.innerHTML='';if(e)e.classList.remove('hidden');return}
		if(e)e.classList.add('hidden');
		c.innerHTML=f.map(function(s){
			var img=s.img||"data:image/svg+xml,"+encodeURIComponent('<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 200 140"><rect fill="#f1f5f9" width="200" height="140"/><text x="50%" y="50%" text-anchor="middle" dy=".3em" fill="#94a3b8" font-size="14">DXD</text></svg>');
			var dl=s.ty.indexOf('dai-ly')>=0||s.tn.indexOf('Đại')>=0;
			var tagClass=dl?'bg-emerald-500':'bg-[#1e73be]';
			var tagColor=dl?'text-emerald-600 bg-emerald-50 border-emerald-100':'text-[#1e73be] bg-[#f0f5ff] border-[#e0ebff]';
			var phones=[s.ph,s.hl].filter(Boolean).map(function(p){return p.trim();});
			
			var phonesHtml=phones.map(function(phone,idx){
				var colorClass=idx===0?'text-blue-500':'text-amber-500';
				var iconSvg=idx===0
					? '<svg class="w-3.5 h-3.5 '+colorClass+' shrink-0" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 22 16.92z"/></svg>'
					: '<svg class="w-3.5 h-3.5 '+colorClass+' shrink-0" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><rect x="5" y="2" width="14" height="20" rx="2" ry="2"/><line x1="12" y1="18" x2="12.01" y2="18"/></svg>';
				var cleanPhone=phone.replace(/\s+/g,'');
				return '<a href="tel:'+cleanPhone+'" class="flex items-center gap-1.5 hover:underline text-slate-700 font-semibold">'+iconSvg+' '+phone+'</a>';
			}).join('');

			var dirUrl=(s.la&&s.lo)?'https://www.google.com/maps/dir//'+s.la+','+s.lo+'/':'https://maps.google.com/?q='+encodeURIComponent(s.t+', '+s.a);

			return '<div class="bg-white border border-slate-100 rounded-2xl overflow-hidden shadow-[0_4px_20px_-2px_rgba(0,0,0,0.05)] group hover:shadow-[0_20px_40px_-4px_rgba(0,0,0,0.08)] hover:-translate-y-1 transition-all duration-300 flex flex-col justify-between">'+
				'<div>'+
					'<!-- Store Image 4:3 -->'+
					'<div class="relative aspect-[4/3] bg-slate-100 overflow-hidden">'+
						'<img loading="lazy" src="'+img+'" alt="'+s.t+'" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300">'+
						'<span class="absolute top-2.5 left-2.5 '+tagClass+' text-white font-bold text-[9px] px-2 py-0.5 rounded-md uppercase">'+s.tn+'</span>'+
					'</div>'+
					'<!-- Card Content -->'+
					'<div class="p-5 space-y-4">'+
						'<div>'+
							'<h3 class="font-bold text-slate-800 text-sm leading-snug group-hover:text-[#1e73be] transition-colors">'+s.t+'</h3>'+
						'</div>'+
					'</div>'+
				'</div>'+
				'<!-- Actions & Address -->'+
				'<div class="p-5 pt-0 space-y-3.5">'+
					'<div class="grid grid-cols-2 gap-2">'+
						'<a href="'+dirUrl+'" target="_blank" class="bg-emerald-600 hover:bg-emerald-700 active:scale-95 text-white font-bold text-xs py-2.5 rounded-xl transition-all shadow-md flex items-center justify-center gap-1.5">'+
							'<svg class="w-3.5 h-3.5 fill-current" viewBox="0 0 24 24"><polygon points="3 11 22 2 13 21 11 13 3 11"/></svg> Chỉ đường'+
						'</a>'+
						'<a href="'+s.u+'" class="border border-[#1e73be] text-[#1e73be] bg-white hover:bg-[#1e73be]! hover:text-white! active:scale-95 font-bold text-xs py-2.5 rounded-xl flex items-center justify-center gap-1.5 transition-all duration-200">'+
							'<svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg> Chi tiết'+
						'</a>'+
					'</div>'+
					'<p class="text-[10px] text-slate-400 flex items-start gap-1.5 leading-relaxed">'+
						'<svg class="w-3 h-3 text-[#1e73be] shrink-0 mt-0.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/></svg>'+
						'<span>'+s.a+'</span>'+
					'</p>'+
				'</div>'+
			'</div>';
		}).join('');
	}
	render();
})();
</script>
