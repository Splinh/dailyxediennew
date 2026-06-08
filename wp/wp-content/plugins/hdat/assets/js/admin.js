var e=new class{routes=new Map;current;register(e,t){this.routes.set(e,t)}init(){window.addEventListener(`hashchange`,()=>this.navigate()),this.navigate()}navigate(){let e=location.hash||`#/dashboard`,t=this.routes.get(e);if(!t)return;this.current?.unmount?.();let n=document.getElementById(`hdat-content`);n.innerHTML=``,this.current=t(),this.current.mount(n),document.querySelectorAll(`#hdat-nav a`).forEach(t=>t.classList.toggle(`active`,t.getAttribute(`href`)===e))}};async function t(e,t={}){let n=await fetch(hdatAdmin.restUrl+e,{...t,headers:{"Content-Type":`application/json`,"X-WP-Nonce":hdatAdmin.nonce,...t.headers}});if(!n.ok){let e=await n.json().catch(()=>({}));throw Error(e.message??`HTTP ${n.status}`)}return n.json()}var n={dashboard:{stats:()=>t(`/admin/dashboard`)},credentials:{list:(e=1,n=20)=>t(`/admin/credentials?page=${e}&per_page=${n}`),create:e=>t(`/admin/credentials`,{method:`POST`,body:JSON.stringify(e)}),update:(e,n)=>t(`/admin/credentials/${e}`,{method:`PUT`,body:JSON.stringify(n)}),delete:e=>t(`/admin/credentials/${e}`,{method:`DELETE`}),test:e=>t(`/admin/credentials/${e}/test`,{method:`POST`}),health:e=>t(`/admin/credentials/${e}/health`,{method:`POST`})},tokens:{list:()=>t(`/admin/tokens`),create:e=>t(`/admin/tokens`,{method:`POST`,body:JSON.stringify(e)}),update:(e,n)=>t(`/admin/tokens/${e}`,{method:`PUT`,body:JSON.stringify(n)}),revoke:e=>t(`/admin/tokens/${e}`,{method:`DELETE`})},openrouter:{models:()=>t(`/admin/openrouter/models`),allModels:()=>t(`/admin/openrouter/all-models`),rateLimits:()=>t(`/admin/openrouter/rate-limits`),pool:()=>t(`/admin/openrouter/pool`),savePool:e=>t(`/admin/openrouter/pool`,{method:`PUT`,body:JSON.stringify(e)}),sync:()=>t(`/admin/openrouter/sync`,{method:`POST`})},usage:{stats:(e={})=>t(`/admin/usage?`+new URLSearchParams(e))},providers:{list:()=>t(`/admin/providers`),models:(e,n)=>t(`/admin/providers/${e}/models`,{method:`POST`,body:JSON.stringify(n)}),validateCustom:e=>t(`/admin/custom-providers/validate`,{method:`POST`,body:JSON.stringify(e)})},routeState:{list:()=>t(`/admin/route-state`),reset:e=>t(`/admin/route-state/${e}`,{method:`DELETE`})},modules:{list:()=>t(`/admin/modules`),save:e=>t(`/admin/modules`,{method:`PUT`,body:JSON.stringify({enabled:e})})},settings:{get:()=>t(`/admin/settings`),save:e=>t(`/admin/settings`,{method:`PUT`,body:JSON.stringify(e)})},forceProvider:{get:()=>t(`/admin/force-provider`),set:e=>t(`/admin/force-provider`,{method:`PUT`,body:JSON.stringify({credential_id:e})}),clear:()=>t(`/admin/force-provider`,{method:`DELETE`})},github:{status:()=>t(`/admin/github-token/status`),save:e=>t(`/admin/github-token`,{method:`PUT`,body:JSON.stringify({token:e})}),remove:()=>t(`/admin/github-token`,{method:`DELETE`})}};function r(e){return e>=1e6?(e/1e6).toFixed(1)+`M`:e>=1e3?(e/1e3).toFixed(1)+`K`:String(e)}function i(){return{async mount(e){e.innerHTML=`<div class="hdat-loading">Loading…</div>`;try{let t=await n.dashboard.stats(),i=t.today?.requests??0,a=t.today?.tokens??0,o=t.credentials?.total??0,s=t.routes?.total??0,c=t.routes?.healthy??0,l=t.routes?.degraded??0;e.innerHTML=`<div class="hdat-page">
					<h2>Dashboard</h2>
					<div class="stats-grid">
						<div class="stat-card">
							<span class="stat-value">${i.toLocaleString()}</span>
							<span class="stat-label">Requests Today</span>
						</div>
						<div class="stat-card">
							<span class="stat-value">${r(a)}</span>
							<span class="stat-label">Tokens Today</span>
						</div>
						<div class="stat-card">
							<span class="stat-value">${o}</span>
							<span class="stat-label">Credentials</span>
						</div>
						<div class="stat-card">
							<span class="stat-value">${s}</span>
							<span class="stat-label">Routes</span>
						</div>
					</div>

					<h3>Route Health</h3>
					<div class="stats-grid">
						<div class="stat-card">
							<span class="stat-value success">${c}</span>
							<span class="stat-label">Healthy</span>
						</div>
						<div class="stat-card">
							<span class="stat-value warning">${l}</span>
							<span class="stat-label">Degraded</span>
						</div>
					</div>

					${t.all_time?`
					<h3>All Time</h3>
					<div class="stats-grid">
						<div class="stat-card">
							<span class="stat-value">${(t.all_time.requests??0).toLocaleString()}</span>
							<span class="stat-label">Total Requests</span>
						</div>
						<div class="stat-card">
							<span class="stat-value">${r(t.all_time.tokens??0)}</span>
							<span class="stat-label">Total Tokens</span>
						</div>
					</div>`:``}
				</div>`}catch(t){e.innerHTML=`<div class="hdat-page"><p class="error-message">Error: ${t.message}</p></div>`}}}}var a=null;function o(){return a||(a=document.createElement(`div`),a.className=`hdat-toast-container`,document.body.appendChild(a)),a}function s(e,t){let n=document.createElement(`div`);n.className=`hdat-toast hdat-toast-${t}`,n.textContent=e,o().appendChild(n),setTimeout(()=>n.remove(),3e3)}var c={success:e=>s(e,`success`),error:e=>s(e,`error`)};function l(e,t){let n,r,i;if(typeof e==`string`?(n=e,r=t||n,i=d(n)):(n=e.provider,r=e.customLabel||n,i=e.isCustomProvider||d(n)),i&&!t&&n.startsWith(`custom:`)){let e=n.substring(7);e&&(r=e)}return`<span class="${i?`badge badge-custom`:`badge badge-indigo`}">${i?u():``}${f(r)}</span>`}function u(){return`<svg class="inline-block w-3 h-3 mr-1 -mt-0.5" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
		<path d="M10 3.5a1.5 1.5 0 013 0V4a1 1 0 001 1h3a1 1 0 011 1v3a1 1 0 01-1 1h-.5a1.5 1.5 0 000 3h.5a1 1 0 011 1v3a1 1 0 01-1 1h-3a1 1 0 01-1-1v-.5a1.5 1.5 0 00-3 0v.5a1 1 0 01-1 1H6a1 1 0 01-1-1v-3a1 1 0 00-1-1h-.5a1.5 1.5 0 010-3H4a1 1 0 001-1V6a1 1 0 011-1h3a1 1 0 001-1v-.5z"/>
	</svg>`}function d(e){return e===`custom`||e.startsWith(`custom:`)}function f(e){let t=document.createElement(`div`);return t.textContent=e,t.innerHTML}var p=[];function m(e){let t=document.createElement(`div`);return t.textContent=e,t.innerHTML}function h(e){if(!d(e.provider))return;let t=e.custom_provider_meta;if(typeof t==`string`)try{t=JSON.parse(t)}catch{t=null}let n=t?.custom_label;return typeof n==`string`&&n.trim()?n:void 0}function g(e){return e===`paid`?`<span class="badge badge-green">Paid</span>`:`<span class="badge">Free</span>`}function _(e){if(!e)return`<span class="badge badge-gray">Auto</span>`;let t=e.length>30?`…`+e.slice(-28):e;return`<span class="badge" title="${m(e)}">${m(t)}</span>`}function v(e){return e.length?`<table class="hdat-table">
		<thead><tr>
			<th>Label</th><th>Provider</th><th>Key</th><th>Tier</th><th>Model</th><th>Priority</th><th>Status</th><th></th>
		</tr></thead>
		<tbody>${e.map(e=>{let t=h(e);return`<tr>
			<td>${m(e.label||e.provider)}</td>
			<td>${l(e.provider,t)}</td>
			<td><code>${m(e.api_key_masked)}</code></td>
			<td>${g(e.tier)}</td>
			<td>${_(e.preferred_model)}</td>
			<td>${e.priority}</td>
			<td>${e.is_active?`<span class="badge badge-green">Active</span>`:`<span class="badge badge-red">Inactive</span>`}</td>
			<td>
				<div class="btn-group">
					<button class="btn-xs" data-test="${e.id}">Test</button>
					<button class="btn-xs" data-edit="${e.id}">Edit</button>
					<button class="btn-xs btn-danger" data-del="${e.id}">Del</button>
				</div>
			</td>
		</tr>`}).join(``)}</tbody>
	</table>`:`<div class="hdat-empty"><p>No credentials yet.</p></div>`}function y(){return p.map(e=>`<option value="${m(e.id)}">${m(e.label)}</option>`).join(``)+`<option value="custom">Custom Provider</option>`}function b(e,t){let r=!!t,i=t??{};d(i.provider||``);let a=document.createElement(`div`);a.className=`hdat-modal-overlay`,a.innerHTML=`<div class="hdat-modal">
		<h3>${r?`Edit`:`Add`} Credential</h3>
		<form class="hdat-form" id="cred-form">
			<label>Provider <select name="provider">${y()}</select></label>

			<!-- Custom Provider Form Fields (hidden by default) -->
			<div id="custom-provider-fields" style="display: none;">
				<div style="background: #f0f9ff; border: 1px solid #bae6fd; border-radius: 6px; padding: 12px; margin-bottom: 16px;">
					<p style="margin: 0 0 8px 0; font-weight: 500; color: #0369a1;">Custom Provider Configuration</p>
					<p style="margin: 0; font-size: 0.875rem; color: #0c4a6e;">Configure your custom LLM provider by specifying the API format and endpoint details.</p>
				</div>

				<label><span>Custom Provider Name <span style="color: #dc2626;">*</span></span>
					<input type="text" name="custom_label" placeholder="e.g., My Local LLM, Acme AI API" value="${m(i.custom_label??``)}">
				</label>
				<small class="text-muted" style="display: block; margin-top: -6px; margin-bottom: 10px;">A friendly name to identify this custom provider</small>

				<label><span>API Format <span style="color: #dc2626;">*</span></span></label>
				<div style="margin-bottom: 16px;">
					<label style="display: flex; flex-flow: row nowrap; align-items: center; margin-bottom: 8px; cursor: pointer;">
						<input type="radio" name="api_format" value="openai_compatible" checked style="margin-right: 8px;">
						<span>OpenAI Compatible (/v1/chat/completions)</span>
					</label>
					<label style="display: flex; flex-flow: row nowrap; align-items: center; cursor: pointer;">
						<input type="radio" name="api_format" value="anthropic_messages" style="margin-right: 8px;">
						<span>Anthropic Messages (/v1/messages)</span>
					</label>
				</div>

				<label><span>Base URL <span style="color: #dc2626;">*</span></span>
					<input type="text" name="custom_base_url" placeholder="https://api.example.com/v1" value="${m(i.custom_base_url??``)}">
				</label>
				<small class="text-muted" id="custom-base-url-hint" style="display: block; margin-top: -6px; margin-bottom: 10px;">OpenAI: nhập kèm version path (vd <code>/v1</code>) — hệ thống tự thêm <code>/chat/completions</code>.</small>

				<label><span>Models Endpoint (optional)</span>
					<input type="text" name="models_url" placeholder="https://api.example.com/v1/models" value="${m(i.models_url??``)}">
				</label>
				<small class="text-muted" style="display: block; margin-top: -6px; margin-bottom: 10px;">Leave empty to manually enter model IDs</small>

				<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 16px;">
					<div>
						<label>Auth Header Name
							<input type="text" name="auth_header_name" placeholder="Authorization" value="${m(i.auth_header_name??`Authorization`)}">
						</label>
					</div>
					<div>
						<label>Auth Header Prefix
							<input type="text" name="auth_header_prefix" placeholder="Bearer" value="${m(i.auth_header_prefix??`Bearer`)}">
						</label>
					</div>
				</div>

				<label style="margin-bottom: 12px;">Capabilities</label>
				<div style="display: grid; grid-template-columns: repeat(2, 1fr); column-gap: 8px;">
					<label style="display: flex; flex-flow: row nowrap; align-items: center; cursor: pointer;">
						<input type="checkbox" name="capability_chat" checked style="margin-right: 8px;">
						<span>Chat</span>
					</label>
					<label style="display: flex; flex-flow: row nowrap; align-items: center; cursor: pointer;">
						<input type="checkbox" name="capability_vision" style="margin-right: 8px;">
						<span>Vision</span>
					</label>
					<label style="display: flex; flex-flow: row nowrap; align-items: center; cursor: pointer;">
						<input type="checkbox" name="capability_function_call" style="margin-right: 8px;">
						<span>Function Calling</span>
					</label>
					<label style="display: flex; flex-flow: row nowrap; align-items: center; cursor: pointer;">
						<input type="checkbox" name="capability_tool_use" style="margin-right: 8px;">
						<span>Tool Use</span>
					</label>
					<label style="display: flex; flex-flow: row nowrap; align-items: center; cursor: pointer;">
						<input type="checkbox" name="capability_embedding" style="margin-right: 8px;">
						<span>Embeddings</span>
					</label>
					<label style="display: flex; flex-flow: row nowrap; align-items: center; cursor: pointer;">
						<input type="checkbox" name="capability_image" style="margin-right: 8px;">
						<span>Image Generation</span>
					</label>
				</div>

				<label style="display: flex; flex-flow: row nowrap; align-items: center; cursor: pointer; margin-bottom: 16px;">
					<input type="checkbox" name="supports_live_models" style="margin-right: 8px;">
					<span>Supports Live Model Fetching</span>
				</label>
				<small class="text-muted" style="display: block; margin-top: -12px; margin-bottom: 10px;">Check if models_url returns a valid model list</small>

				<button type="button" class="btn-sm" id="validate-custom-provider" style="width: 100%; margin-bottom: 16px;">
					Validate Configuration
				</button>
				<div id="validation-result" style="display: none; padding: 10px; border-radius: 6px; margin-bottom: 16px;"></div>
			</div>

			<!-- Standard Fields -->
			<label>Label <input type="text" name="label" value="${m(i.label??``)}"></label>
			<label>API Key <input type="text" name="api_key" value="" placeholder="${r?`(unchanged)`:``}"></label>
			<small class="text-muted" id="api-key-hint" style="display: none; margin-top: -6px; margin-bottom: 10px;"></small>
			<label id="base-url-row">Base URL (optional) <input type="text" name="base_url" value="${m(i.base_url??``)}"></label>
			<label>Tier <select name="tier"><option value="free">Free</option><option value="paid"${i.tier===`paid`?` selected`:``}>Paid</option></select></label>
			<div id="preferred-model-row">
				<label>Preferred Model (optional)
					<div id="preferred-model-container"></div>
				</label>
				<small class="text-muted" id="preferred-model-hint"></small>
			</div>
			<label>Priority <input type="number" name="priority" value="${i.priority??5}" min="1" max="10"></label>
			<div class="toggle-row"><span>Active</span><label class="toggle"><input type="checkbox" name="is_active"${i.is_active===!1?``:` checked`}><span></span></label></div>
			<div class="hdat-modal-actions">
				<button type="button" class="btn-sm" id="modal-cancel">Cancel</button>
				<button type="submit" class="btn-primary">${r?`Update`:`Create`}</button>
			</div>
		</form>
	</div>`,document.body.appendChild(a);let o=a.querySelector(`select[name="provider"]`),s=a.querySelector(`input[name="base_url"]`),l=a.querySelector(`#base-url-row`),u=a.querySelector(`select[name="tier"]`),f=a.querySelector(`#preferred-model-container`),h=a.querySelector(`#preferred-model-hint`),g=a.querySelector(`#api-key-hint`),_=a.querySelector(`#custom-provider-fields`),v=a.querySelector(`#validate-custom-provider`),b=a.querySelector(`#validation-result`),x=null,S=null,C=e=>{_&&(_.style.display=e?`block`:`none`),l&&(l.style.display=e?`none`:`block`)};v&&v.addEventListener(`click`,async()=>{if(!v||!b)return;let e=a.querySelector(`input[name="custom_base_url"]`)?.value.trim(),t=a.querySelector(`input[name="api_key"]`)?.value.trim(),o=a.querySelector(`input[name="api_format"]:checked`)?.value,s=a.querySelector(`input[name="models_url"]`)?.value.trim(),c=a.querySelector(`input[name="auth_header_name"]`)?.value.trim()||`Authorization`,l=a.querySelector(`input[name="auth_header_prefix"]`)?.value.trim()||`Bearer`;if(!e){b.innerHTML=`<span style="color: #dc2626;">❌ Base URL is required</span>`,b.style.display=`block`,b.style.background=`#fee2e2`,b.style.border=`1px solid #fca5a5`;return}let u=r&&!!i.id&&!t;if(!t&&!u){b.innerHTML=`<span style="color: #dc2626;">❌ API Key is required for validation</span>`,b.style.display=`block`,b.style.background=`#fee2e2`,b.style.border=`1px solid #fca5a5`;return}try{new URL(e)}catch{b.innerHTML=`<span style="color: #dc2626;">❌ Invalid URL format</span>`,b.style.display=`block`,b.style.background=`#fee2e2`,b.style.border=`1px solid #fca5a5`;return}v.disabled=!0,v.textContent=`Validating...`;try{let r=await n.providers.validateCustom({api_format:o,base_url:e,...t?{api_key:t}:{},...u?{credential_id:i.id}:{},models_url:s||void 0,auth_header_name:c,auth_header_prefix:l});if(r.valid){let e=`Valid ${r.detected_format||o} API`;r.sample_models&&r.sample_models.length>0&&(e+=` — Found ${r.sample_models.length} models`),b.innerHTML=`<span style="color: #16a34a;">${e}</span>`,b.style.background=`#dcfce7`,b.style.border=`1px solid #86efac`}else b.innerHTML=`<span style="color: #dc2626;">❌ ${r.error||`Validation failed`}</span>`,b.style.background=`#fee2e2`,b.style.border=`1px solid #fca5a5`;b.style.display=`block`}catch(e){b.innerHTML=`<span style="color: #dc2626;">❌ ${e.message||`Validation failed`}</span>`,b.style.display=`block`,b.style.background=`#fee2e2`,b.style.border=`1px solid #fca5a5`}finally{v.disabled=!1,v.textContent=`Validate Configuration`}});let T=a.querySelector(`#custom-base-url-hint`),E=a.querySelector(`input[name="custom_base_url"]`),D=()=>{a.querySelector(`input[name="api_format"]:checked`)?.value===`anthropic_messages`?(T&&(T.innerHTML=`Anthropic: nhập domain gốc (vd <code>https://api.example.com</code>) — hệ thống tự thêm <code>/v1/messages</code>.`),E&&(E.placeholder=`https://api.example.com`)):(T&&(T.innerHTML=`OpenAI: nhập kèm version path (vd <code>/v1</code>) — hệ thống tự thêm <code>/chat/completions</code>.`),E&&(E.placeholder=`https://api.example.com/v1`))};if(a.querySelectorAll(`input[name="api_format"]`).forEach(e=>{e.addEventListener(`change`,D)}),D(),a.querySelector(`input[name="models_url"]`)?.addEventListener(`change`,()=>{o?.value===`custom`&&N(`custom`,u?.value??`free`)}),d(i.provider||``)){C(!0);let e=a.querySelector(`input[name="custom_label"]`),t=a.querySelector(`input[name="custom_base_url"]`);if(t&&(t.value=i.base_url||``),e&&(e.value=i.label||``),i.custom_provider_meta){let n=typeof i.custom_provider_meta==`string`?JSON.parse(i.custom_provider_meta):i.custom_provider_meta,r=(e,t)=>n[e]??n[t],o=r(`custom_label`,`customLabel`);e&&o&&(e.value=o);let s=r(`base_url`,`baseUrl`);t&&!t.value&&s&&(t.value=s);let c=r(`api_format`,`apiFormat`),l=a.querySelector(`input[name="api_format"][value="${c}"]`);l&&(l.checked=!0);let u=a.querySelector(`input[name="models_url"]`);u&&(u.value=r(`models_url`,`modelsUrl`)||``);let d=a.querySelector(`input[name="auth_header_name"]`);d&&(d.value=r(`auth_header_name`,`authHeaderName`)||`Authorization`);let f=a.querySelector(`input[name="auth_header_prefix"]`);f&&(f.value=r(`auth_header_prefix`,`authHeaderPrefix`)||`Bearer`);let p=a.querySelector(`input[name="supports_live_models"]`);p&&(p.checked=r(`supports_live_models`,`supportsLiveModels`)||!1),Array.isArray(n.capabilities)&&n.capabilities.forEach(e=>{let t=a.querySelector(`input[name="capability_${e}"]`);t&&(t.checked=!0)})}D()}let O=e=>{let t=p.find(t=>t.id===e);s&&(s.value=t?.base_url??``)},k=e=>{let t=p.find(t=>t.id===e);g&&(t?.reg_url?(g.innerHTML=`Lấy API Key tại: <a href="${m(t.reg_url)}" target="_blank" rel="noopener noreferrer" style="color: #2271b1; text-decoration: underline;">${m(t.label)} Dashboard ↗</a>`,g.style.display=`block`):(g.innerHTML=``,g.style.display=`none`))},A=a.querySelector(`#preferred-model-row`),j=a.querySelector(`input[name="api_key"]`),M={},N=async(e,t)=>{if(!(!f||!A)){if(e===`custom`){let e=a.querySelector(`input[name="models_url"]`)?.value.trim()??``,t=i.preferred_model||``,o=()=>{f.innerHTML=`<input type="text" name="preferred_model" value="${m(t)}" placeholder="e.g. gpt-4o, claude-sonnet-4">`};if(A.style.display=``,!e){o(),h&&(h.textContent=`Chưa có Models Endpoint — nhập model id thủ công.`);return}let s={models_url:e,api_format:a.querySelector(`input[name="api_format"]:checked`)?.value??`openai_compatible`,auth_header_name:a.querySelector(`input[name="auth_header_name"]`)?.value.trim()||`Authorization`,auth_header_prefix:a.querySelector(`input[name="auth_header_prefix"]`)?.value.trim()||`Bearer`},c=j?.value?.trim()??``;c?s.api_key=c:r&&i.id&&(s.credential_id=i.id),f.innerHTML=`<span class="text-muted">Đang tải danh sách model…</span>`,h&&(h.textContent=``);try{let e=await n.providers.models(`custom`,s),r=e.models??[];r.length===0?(o(),h&&(h.textContent=e.message||`Không tìm thấy model nào — nhập thủ công.`)):(P(r,t),h&&(h.textContent=`Chọn model ưu tiên. Để trống = do request quyết định.`))}catch(e){o(),h&&(h.textContent=`Không load được model: `+(e.message||``))}return}if(e===`openrouter`){A.style.display=``;let e;if(t===`paid`){if(!x)try{x=await n.openrouter.allModels()}catch{x=[]}e=x,h&&(h.textContent=`Paid: model ưu tiên. Fallback → Pool → openrouter/auto`)}else{if(!S)try{S=await n.openrouter.models()}catch{S=[]}e=S,h&&(h.textContent=`Free: model ưu tiên. Fallback → Pool → openrouter/free`)}let r=i.preferred_model||``,a=`<option value="">(Auto — use Pool or fallback)</option>`+e.map(e=>`<option value="${m(e.id)}"${e.id===r?` selected`:``}>${m(e.name||e.id)}</option>`).join(``);r&&!e.find(e=>e.id===r)&&(a+=`<option value="${m(r)}" selected>${m(r)} (cached)</option>`),f.innerHTML=`<select name="preferred_model">${a}</select>`,e.length===0&&h&&(h.textContent+=` — No models cached. Run Sync on the OpenRouter tab first.`)}else if(t===`paid`){A.style.display=``;let t={};if(r&&i.id)t.credential_id=i.id;else{let e=j?.value?.trim()??``;if(!e){f.innerHTML=`<input type="text" name="preferred_model" value="${m(i.preferred_model||``)}" placeholder="e.g. claude-sonnet-4-20250514">`,h&&(h.textContent=`Nhập API Key trước để load danh sách model.`);return}t.api_key=e;let n=s?.value?.trim()??``;n&&(t.base_url=n)}let a=e+`:`+(t.credential_id??t.api_key);if(M[a]){P(M[a],i.preferred_model||``),h&&(h.textContent=`Chọn model ưu tiên. Để trống = mặc định.`);return}f.innerHTML=`<span class="text-muted">Đang tải danh sách model…</span>`,h&&(h.textContent=``);try{let r=await n.providers.models(e,t),o=r.models??[];M[a]=o,P(o,i.preferred_model||``),o.length===0&&h?h.textContent=r.message||`Không tìm thấy model nào.`:h&&(h.textContent=`Chọn model ưu tiên. Để trống = mặc định.`)}catch(e){f.innerHTML=`<input type="text" name="preferred_model" value="${m(i.preferred_model||``)}" placeholder="e.g. claude-sonnet-4-20250514">`,h&&(h.textContent=`Không load được model: `+(e.message||``))}}else{let t=p.find(t=>t.id===e),n=Array.isArray(t?.models)?t.models:[];n.length>0?(A.style.display=``,P(n,i.preferred_model||``),h&&(h.textContent=`Chọn model ưu tiên. Để trống = mặc định.`)):(A.style.display=`none`,f.innerHTML=``,h&&(h.textContent=``))}}},P=(e,t)=>{let n=`<option value="">(Mặc định)</option>`+e.map(e=>`<option value="${m(e.id)}"${e.id===t?` selected`:``}>${m(e.name||e.id)}</option>`).join(``);t&&!e.find(e=>e.id===t)&&(n+=`<option value="${m(t)}" selected>${m(t)} (saved)</option>`),f.innerHTML=`<select name="preferred_model">${n}</select>`};o&&(i.provider&&(o.value=d(i.provider)?`custom`:i.provider),o.addEventListener(`change`,()=>{let e=o.value===`custom`;C(e),e?(N(`custom`,u?.value??`free`),b&&(b.style.display=`none`)):(O(o.value),k(o.value),N(o.value,u?.value??`free`))}),!r&&s&&s.value.trim()===``&&O(o.value),k(o.value),N(o.value,u?.value??i.tier??`free`)),u&&u.addEventListener(`change`,()=>{N(o?.value??``,u.value)}),j&&!r&&j.addEventListener(`blur`,()=>{let e=o?.value??``,t=u?.value??`free`;e!==`openrouter`&&t===`paid`&&j.value.trim()&&N(e,t)}),a.querySelector(`#modal-cancel`).addEventListener(`click`,()=>a.remove()),a.querySelector(`#cred-form`).addEventListener(`submit`,async t=>{t.preventDefault();let o=new FormData(t.target),s=o.get(`provider`),l=s===`custom`,u=(o.get(`label`)||``).trim(),d=(o.get(`api_key`)||``).trim();if(l){let e=(o.get(`custom_label`)||``).trim(),t=(o.get(`custom_base_url`)||``).trim(),n=o.get(`api_format`);if(!e){c.error(`Custom Provider Name is required`);return}if(!t){c.error(`Base URL is required for custom providers`);return}if(!n){c.error(`API Format is required`);return}try{new URL(t)}catch{c.error(`Invalid Base URL format`);return}}else if(!u){c.error(`Label is required`);return}if(!r&&!d){c.error(`API Key is required`);return}let f={provider:l?`custom`:s,label:l?u||(o.get(`custom_label`)||``).trim():u,tier:o.get(`tier`),priority:Number(o.get(`priority`)),is_active:!!t.target.querySelector(`[name="is_active"]`)?.checked,preferred_model:o.get(`preferred_model`)||null};if(l){let e=[];t.target.querySelector(`[name="capability_chat"]`)?.checked&&e.push(`chat`),t.target.querySelector(`[name="capability_vision"]`)?.checked&&e.push(`vision`),t.target.querySelector(`[name="capability_function_call"]`)?.checked&&e.push(`function_call`),t.target.querySelector(`[name="capability_tool_use"]`)?.checked&&e.push(`tool_use`),t.target.querySelector(`[name="capability_embedding"]`)?.checked&&e.push(`embedding`),t.target.querySelector(`[name="capability_image"]`)?.checked&&e.push(`image`),f.custom_provider_meta=JSON.stringify({api_format:o.get(`api_format`),custom_label:(o.get(`custom_label`)||``).trim(),models_url:(o.get(`models_url`)||``).trim()||null,auth_header_name:(o.get(`auth_header_name`)||``).trim()||`Authorization`,auth_header_prefix:(o.get(`auth_header_prefix`)||``).trim()||`Bearer`,capabilities:e,supports_live_models:!!t.target.querySelector(`[name="supports_live_models"]`)?.checked}),f.base_url=(o.get(`custom_base_url`)||``).trim()||null}else f.base_url=(o.get(`base_url`)||``).trim()||null;d&&(f.api_key=d);try{r?(await n.credentials.update(i.id,f),c.success(`Credential updated`)):(f.api_key=d,await n.credentials.create(f),c.success(`Credential created`)),a.remove(),w(e)}catch(e){c.error(e.message)}})}var x=1,S=null;function C(e){let t=e.filter(e=>e.is_active);if(t.length===0)return``;let n=S!==null,r=n?t.find(e=>e.id===S):null,i=[`<option value="">Auto (All Providers)</option>`,...t.map(e=>{let t=h(e),n=e.label||e.provider,r=e.id===S?` selected`:``;return`<option value="${e.id}"${r}>${m(n)} (${m(t||e.provider)})</option>`})].join(``),a=n?`<span class="badge badge-orange" style="margin-left: 8px;">FORCE MODE ACTIVE</span>`:``,o=n&&r?`Only "${m(r.label||r.provider)}" is active. No fallback to other providers.`:`When set, only the selected provider will be used (no fallback). Errors will be shown to users.`;return`
		<div style="background: ${n?`#fff7ed`:`#f0f9ff`}; border: 1px solid ${n?`#fed7aa`:`#bae6fd`}; border-radius: 6px; padding: 16px; margin-bottom: 20px;">
			<div style="display: flex; align-items: center; margin-bottom: 12px;">
				<label style="margin: 0; font-weight: 600; color: ${n?`#9a3412`:`#0369a1`}; display: flex; align-items: center;">
					Force Single Provider:
					${a}
				</label>
			</div>
			<select id="force-provider-select" style="width: 100%; max-width: 400px; margin-bottom: 8px;">
				${i}
			</select>
			<p style="margin: 8px 0 0 0; font-size: 0.875rem; color: ${n?`#9a3412`:`#0c4a6e`};">
				${o}
			</p>
		</div>
	`}async function w(e){e.innerHTML=`<div class="hdat-loading">Loading…</div>`;try{p.length||(p=await n.providers.list()),S=(await n.forceProvider.get()).credential_id;let t=await n.credentials.list(x),r=t.items??[],i=t.total??0,a=t.pages??1;e.innerHTML=`<div class="hdat-page">
			<div class="hdat-toolbar">
				<h2>Credentials</h2>
				<span class="badge">${i} total</span>
				<button class="btn-sm" id="cred-add">+ Add</button>
			</div>
			${C(r)}
			${v(r)}
			${a>1?`<div class="hdat-pagination">
				<button id="pg-prev"${x<=1?` disabled`:``}>← Prev</button>
				<span>Page ${x} / ${a}</span>
				<button id="pg-next"${x>=a?` disabled`:``}>Next →</button>
			</div>`:``}
		</div>`;let o=e.querySelector(`#force-provider-select`);o&&o.addEventListener(`change`,async()=>{let t=o.value;try{t===``?(await n.forceProvider.clear(),c.success(`Force mode disabled`)):(await n.forceProvider.set(Number(t)),c.success(`Force mode enabled`)),w(e)}catch(t){c.error(t.message),w(e)}}),e.querySelector(`#cred-add`)?.addEventListener(`click`,()=>b(e)),e.querySelector(`#pg-prev`)?.addEventListener(`click`,()=>{x>1&&(x--,w(e))}),e.querySelector(`#pg-next`)?.addEventListener(`click`,()=>{x<a&&(x++,w(e))}),e.onclick=async t=>{let i=t.target,a=i.closest(`[data-test]`);if(a){a.textContent=`…`;try{let e=await n.credentials.test(Number(a.dataset.test));c[e.ok?`success`:`error`](e.ok?`OK (${e.latency_ms}ms)`:e.error)}catch(e){c.error(e.message)}a.textContent=`Test`;return}let o=i.closest(`[data-edit]`);if(o){let t=r.find(e=>e.id===Number(o.dataset.edit));t&&b(e,t);return}let s=i.closest(`[data-del]`);if(s){if(!confirm(`Delete this credential?`))return;try{await n.credentials.delete(Number(s.dataset.del)),c.success(`Deleted`),w(e)}catch(e){c.error(e.message)}}}}catch(t){e.innerHTML=`<div class="hdat-page"><p class="error-message">Error: ${t.message}</p></div>`}}function T(){return x=1,{mount:w}}function E(e){let t=document.createElement(`div`);return t.textContent=e,t.innerHTML}function D(e){return e.length?`<table class="hdat-table">
		<thead><tr><th>Name</th><th>Prefix</th><th>RPM/RPD</th><th>TPM/TPD</th><th>Status</th><th></th></tr></thead>
		<tbody>${e.map(e=>{let t=!!e.revoked_at,n=e.expires_at&&new Date(e.expires_at)<new Date,r=t?`<span class="badge badge-red">Revoked</span>`:n?`<span class="badge badge-yellow">Expired</span>`:`<span class="badge badge-green">Active</span>`;return`<tr>
			<td>${E(e.name)}</td>
			<td><code>${E(e.token_prefix)}…</code></td>
			<td>${e.rpm_limit??`∞`} / ${e.rpd_limit??`∞`}</td>
			<td>${e.tpm_limit??`∞`} / ${e.tpd_limit??`∞`}</td>
			<td>${r}</td>
			<td>${t?``:`<button class="btn-xs btn-danger" data-revoke="${e.id}">Revoke</button>`}</td>
		</tr>`}).join(``)}</tbody>
	</table>`:`<div class="hdat-empty"><p>No consumer tokens yet.</p></div>`}function O(e,t){let r=document.createElement(`div`);r.className=`hdat-modal-overlay`,r.innerHTML=`<div class="hdat-modal">
		<h3>Create Token</h3>
		<form class="hdat-form" id="token-form">
			<label>Name <input type="text" name="name" required></label>
			<label>RPM Limit <input type="number" name="rpm_limit" placeholder="unlimited"></label>
			<label>RPD Limit <input type="number" name="rpd_limit" placeholder="unlimited"></label>
			<label>TPM Limit <input type="number" name="tpm_limit" placeholder="unlimited"></label>
			<label>TPD Limit <input type="number" name="tpd_limit" placeholder="unlimited"></label>
			<label>Expires at <input type="date" name="expires_at"></label>
			<div class="hdat-modal-actions">
				<button type="button" class="btn-sm" id="modal-cancel">Cancel</button>
				<button type="submit" class="btn-primary">Create</button>
			</div>
		</form>
	</div>`,document.body.appendChild(r),r.querySelector(`#modal-cancel`).addEventListener(`click`,()=>r.remove()),r.querySelector(`#token-form`).addEventListener(`submit`,async e=>{e.preventDefault();let i=new FormData(e.target),a={name:i.get(`name`)};for(let e of[`rpm_limit`,`rpd_limit`,`tpm_limit`,`tpd_limit`]){let t=i.get(e);t&&(a[e]=Number(t))}let o=i.get(`expires_at`);o&&(a.expires_at=o);try{let e=await n.tokens.create(a);if(r.remove(),e.raw){let n=document.createElement(`div`);n.className=`hdat-modal-overlay`,n.innerHTML=`<div class="hdat-modal">
					<h3>Token Created</h3>
					<p class="token-warning">Copy this token now — it won't be shown again.</p>
					<input type="text" value="${E(e.raw)}" readonly class="token-display">
					<div class="hdat-modal-actions">
						<button class="btn-sm" id="copy-token">Copy</button>
						<button class="btn-primary" id="close-token">Close</button>
					</div>
				</div>`,document.body.appendChild(n),n.querySelector(`#copy-token`).addEventListener(`click`,()=>{navigator.clipboard.writeText(e.raw),c.success(`Copied to clipboard`)}),n.querySelector(`#close-token`).addEventListener(`click`,()=>{n.remove(),t()})}else c.success(`Token created`),t()}catch(e){c.error(e.message)}})}async function k(e){e.innerHTML=`<div class="hdat-loading">Loading…</div>`;try{let t=await n.tokens.list();e.innerHTML=`<div class="hdat-page">
			<div class="hdat-toolbar">
				<h2>Consumer Tokens</h2>
				<span class="badge">${t.length} total</span>
				<button class="btn-sm" id="token-add">+ Create</button>
			</div>
			${D(t)}
		</div>`,e.querySelector(`#token-add`)?.addEventListener(`click`,()=>O(e,()=>k(e))),e.onclick=async t=>{let r=t.target.closest(`[data-revoke]`);if(r&&confirm(`Revoke this token?`))try{await n.tokens.revoke(Number(r.dataset.revoke)),c.success(`Token revoked`),k(e)}catch(e){c.error(e.message)}}}catch(t){e.innerHTML=`<div class="hdat-page"><p class="error-message">Error: ${t.message}</p></div>`}}function A(){return{mount:k}}function j(e){let t=[``,`K`,`M`,`B`,`T`],n=0,r=e;for(;Math.abs(r)>=1e3&&n<t.length-1;)r/=1e3,n++;return(n===0?String(r):r.toFixed(1).replace(/\.0$/,``))+t[n]}async function M(e,t={}){e.innerHTML=`<div class="hdat-loading">Loading…</div>`;try{let r=await n.usage.stats(t),i=r.summary??{},a=r.by_provider??[],o=Array.isArray(a)?a.map(e=>`<tr>
				<td>${e.provider}</td>
				<td><code>${e.model||`—`}</code></td>
				<td>${(e.requests??0).toLocaleString()}</td>
				<td>${j(e.prompt_tokens??0)}</td>
				<td>${j(e.completion_tokens??0)}</td>
				<td>${j(e.total_tokens??0)}</td>
			</tr>`).join(``):``;e.innerHTML=`<div class="hdat-page">
			<h2>Usage</h2>

			<form id="usage-filters" class="usage-filters">
				<label class="field-label">
					From <input type="date" name="from" value="${t.from??``}" class="input-search">
				</label>
				<label class="field-label">
					To <input type="date" name="to" value="${t.to??``}" class="input-search">
				</label>
				<button type="submit" class="btn-sm">Filter</button>
			</form>

			<div class="stats-grid">
				<div class="stat-card">
					<span class="stat-value">${(i.requests??0).toLocaleString()}</span>
					<span class="stat-label">Requests</span>
				</div>
				<div class="stat-card">
					<span class="stat-value">${j(i.tokens??0)}</span>
					<span class="stat-label">Total Tokens</span>
				</div>
			</div>

			${o?`<table class="hdat-table">
				<thead><tr><th>Provider</th><th>Model</th><th>Requests</th><th>Prompt</th><th>Completion</th><th>Total</th></tr></thead>
				<tbody>${o}</tbody>
			</table>`:`<div class="hdat-empty"><p>No usage data for this period.</p></div>`}
		</div>`,e.querySelector(`#usage-filters`)?.addEventListener(`submit`,t=>{t.preventDefault();let n=new FormData(t.target),r={},i=n.get(`from`),a=n.get(`to`);i&&(r.from=i),a&&(r.to=a),M(e,r)})}catch(t){e.innerHTML=`<div class="hdat-page"><p class="error-message">Error: ${t.message}</p></div>`}}function N(){return{mount:e=>M(e)}}function P(e,t){if(t<=0)return``;let n=Math.round(e/t*100);return`<div class="rl-row">
		<span>${e}/${t}</span>
		<div class="rl-track"><div class="${n>50?`rl-green`:n>20?`rl-yellow`:`rl-red`} rl-bar" style="width:${n}%"></div></div>
	</div>`}var F=[],I={models:[]},L={},R;function z(e){let t=document.createElement(`div`);return t.textContent=e,t.innerHTML}function B(e){let t=L[e.id];return t?(t.remaining??0)>0?`status-dot--active`:`status-dot--exhausted`:`status-dot--active`}function V(e){let t=I.models?.find(t=>t.id===e.id)?.enabled??!1,n=L[e.id],r=n?P(n.remaining??0,n.limit??0):``;return`<div class="model-card${t?` enabled`:``}" data-id="${z(e.id)}" draggable="true">
		<div class="model-card-head">
			<span class="model-name" title="${z(e.id)}">${z(e.name||e.id)}</span>
			<span class="status-dot ${B(e)}" data-dot="${z(e.id)}"></span>
			<label class="toggle">
				<input type="checkbox" data-toggle="${z(e.id)}"${t?` checked`:``}>
				<span></span>
			</label>
		</div>
		${r}
		<div class="model-card-foot">
			<span class="badge badge-gray">${Math.round((e.context_length??0)/1e3)}K ctx</span>
		</div>
	</div>`}function H(e){for(let[t,n]of Object.entries(L)){let r=e.querySelector(`[data-id="${t}"] .rl-bar`);if(r){let e=n,t=e.limit>0?Math.round(e.remaining/e.limit*100):100;r.style.width=`${t}%`,r.className=`rl-bar ${t>50?`rl-green`:t>20?`rl-yellow`:`rl-red`}`}let i=e.querySelector(`[data-dot="${t}"]`);i&&(i.className=`status-dot ${(n.remaining??0)>0?`status-dot--active`:`status-dot--exhausted`}`)}}function U(e,t){let r=null;t.addEventListener(`dragstart`,e=>{r=e.target.closest(`.model-card`),r&&r.classList.add(`dragging`)}),t.addEventListener(`dragover`,e=>{e.preventDefault();let n=e.target.closest(`.model-card`);if(n&&n!==r&&r){let i=n.getBoundingClientRect(),a=i.top+i.height/2;e.clientY<a?t.insertBefore(r,n):t.insertBefore(r,n.nextSibling)}}),t.addEventListener(`dragend`,async()=>{r&&r.classList.remove(`dragging`),r=null;let t=e.querySelectorAll(`.model-card:not([style*="display: none"])`);t.forEach((e,n)=>{let r=e.dataset.id,i=I.models?.find(e=>e.id===r);i||(i={id:r,enabled:!1,priority:5},I.models.push(i)),i.priority=t.length-n});try{await n.openrouter.savePool(I)}catch(e){c.error(e.message)}})}async function W(e){R&&=(clearInterval(R),void 0),e.innerHTML=`<div class="hdat-loading">Loading…</div>`;try{[F,I,L]=await Promise.all([n.openrouter.models(),n.openrouter.pool(),n.openrouter.rateLimits()]),Array.isArray(I.models)||(I={models:[]});let t=Array.isArray(F)?F.filter(e=>e.id?.endsWith(`:free`)):F,r=new Set(t.map(e=>e.id)),i=I.models.filter(e=>e.enabled&&r.has(e.id)).length,a=i>=t.length;e.innerHTML=`<div class="hdat-page">
			<div class="playground-header">
				<div class="hdat-toolbar-title" style="display: flex; align-items: center; gap: 0.75rem;">
					<h2>OpenRouter Pool</h2>
					<span class="badge badge-indigo">${i}/${t.length} enabled</span>
				</div>
				<div class="hdat-toolbar-actions" style="display: flex; align-items: center; gap: 0.75rem;">
					<input id="or-search" type="text" placeholder="Search models…" class="input-search" style="margin: 0; width: 220px;">
					<button id="or-toggle-all" class="btn-sm${a?` btn-danger`:``}">${a?`Disable All`:`Enable All`}</button>
					<button id="or-sync" class="btn-sm">Sync models</button>
				</div>
			</div>
			<p class="text-muted" style="margin-top:-0.5rem; margin-bottom:1.5rem; font-size:0.85rem; max-width: 800px; line-height: 1.45;">
				Pool models supplement the Preferred Model as fallback. If a credential's Preferred Model is rate-limited, the system falls back to pool models in priority order (drag to reorder). If all pool models are also limited: free credentials fall back to <code>openrouter/free</code>, paid credentials to <code>openrouter/auto</code>.
			</p>
			<div id="or-list" class="model-grid">
				${[...t].sort((e,t)=>{let n=I.models?.find(t=>t.id===e.id)?.priority??0;return(I.models?.find(e=>e.id===t.id)?.priority??0)-n}).map(e=>V(e)).join(``)}
			</div>
		</div>`,e.querySelector(`#or-sync`)?.addEventListener(`click`,async()=>{let t=e.querySelector(`#or-sync`);t.disabled=!0,t.textContent=`Syncing…`;try{await n.openrouter.sync(),F=await n.openrouter.models(),c.success(`Models synced`),W(e)}catch(e){c.error(e.message),t.disabled=!1,t.textContent=`Sync models`}}),e.querySelector(`#or-search`)?.addEventListener(`input`,t=>{let n=t.target.value.toLowerCase();e.querySelectorAll(`.model-card`).forEach(e=>{e.style.display=e.dataset.id.toLowerCase().includes(n)?``:`none`})}),e.querySelector(`#or-toggle-all`)?.addEventListener(`click`,async()=>{let t=e.querySelector(`#or-toggle-all`),r=I.models.filter(e=>e.enabled).length<(Array.isArray(F)?F.filter(e=>e.id?.endsWith(`:free`)).length:0);t.disabled=!0,t.textContent=r?`Enabling…`:`Disabling…`;let i=Array.isArray(F)?F.filter(e=>e.id?.endsWith(`:free`)):[];for(let e of i){let t=I.models.find(t=>t.id===e.id);t?t.enabled=r:I.models.push({id:e.id,enabled:r,priority:5})}try{await n.openrouter.savePool(I),c.success(r?`All models enabled`:`All models disabled`),W(e)}catch(e){c.error(e.message),t.disabled=!1,t.textContent=r?`Enable All`:`Disable All`}}),e.onchange=async e=>{let t=e.target.closest(`[data-toggle]`);if(!t)return;let r=t.dataset.toggle,i=I.models.find(e=>e.id===r);i?i.enabled=t.checked:I.models.push({id:r,enabled:t.checked,priority:5}),t.closest(`.model-card`)?.classList.toggle(`enabled`,t.checked);try{await n.openrouter.savePool(I)}catch(e){c.error(e.message)}};let o=e.querySelector(`#or-list`);o&&U(e,o),R=setInterval(async()=>{try{L=await n.openrouter.rateLimits(),H(e)}catch{}},3e4)}catch(t){e.innerHTML=`<div class="hdat-page"><p class="error-message">Error: ${t.message}</p></div>`}}function G(){return{mount:W,unmount(){R&&clearInterval(R),R=void 0}}}function K(e){let t=document.createElement(`div`);return t.textContent=e,t.innerHTML}function q(e){return e.has_token?`<span class="badge badge-green">${K(e.source===`db`?`DB`:`wp-config.php`)}</span>`:`<span class="badge badge-gray">Not configured</span>`}async function J(e){e.innerHTML=`<div class="hdat-loading">Loading…</div>`;try{let[t,r,i]=await Promise.all([n.settings.get(),n.modules.list(),n.github.status()]),a=Array.isArray(r)?r.map(e=>`<label class="toggle-row${e.always_active?` disabled`:``}">
				<span>${K(e.title)} <small class="text-muted">${K(e.description??``)}</small></span>
				<label class="toggle">
					<input type="checkbox" data-module="${K(e.slug)}"
						${e.active?` checked`:``}
						${e.always_active?` disabled`:``}>
					<span></span>
				</label>
			</label>`).join(``):``;e.innerHTML=`<div class="hdat-page">
			<form id="settings-form" class="hdat-form">
				<h2>Settings</h2>

				<label>Max route attempts
					<input name="max_route_attempts" type="number" value="${t.max_route_attempts??6}" min="1" max="10">
				</label>

				<label>Circuit breaker threshold
					<input name="circuit_threshold" type="number" value="${t.circuit_threshold??5}" min="1" max="20">
				</label>

				<label>Circuit breaker cooldown (seconds)
					<input name="circuit_ttl" type="number" value="${t.circuit_ttl??300}" min="60">
				</label>

				<div class="toggle-row">
					<span>Emit X-Routed-Via headers</span>
					<label class="toggle">
						<input type="checkbox" name="route_headers"${t.route_headers?` checked`:``}>
						<span></span>
					</label>
				</div>

				<div class="toggle-row">
					<span>Clean uninstall <small class="text-muted">(delete all data on plugin delete)</small></span>
					<label class="toggle">
						<input type="checkbox" name="clean_uninstall"${t.clean_uninstall?` checked`:``}>
						<span></span>
					</label>
				</div>

				${a?`<h3>Modules</h3>${a}`:``}

				<h3>GitHub Updates</h3>
				<div class="field-row" id="github-token-row">
					<label>Personal Access Token ${q(i)}</label>
					<div class="github-token-input-group">
						<input id="github-token-input" type="password" placeholder="ghp_xxxx" autocomplete="off">
						<button type="button" id="save-github-token" class="btn-sm">Save Token</button>
						${i.has_token&&i.source===`db`?`<button type="button" id="del-github-token" class="btn-sm btn-danger">Remove</button>`:``}
					</div>
					<small class="text-muted">Encrypted at rest. Or define <code>HDAT_GITHUB_TOKEN</code> in wp-config.php.</small>
				</div>

				<button type="submit" class="btn-primary" style="margin-top:1.5rem">Save Settings</button>
			</form>
		</div>`,e.querySelector(`#settings-form`).addEventListener(`submit`,async e=>{e.preventDefault();let t=e.target,i={max_route_attempts:Number(t.querySelector(`[name="max_route_attempts"]`)?.value),circuit_threshold:Number(t.querySelector(`[name="circuit_threshold"]`)?.value),circuit_ttl:Number(t.querySelector(`[name="circuit_ttl"]`)?.value),route_headers:!!t.querySelector(`[name="route_headers"]`)?.checked,clean_uninstall:!!t.querySelector(`[name="clean_uninstall"]`)?.checked};try{if(await n.settings.save(i),Array.isArray(r)){let e=r.filter(e=>!e.always_active&&t.querySelector(`[data-module="${e.slug}"]`)?.checked).map(e=>e.slug);await n.modules.save(e)}c.success(`Settings saved`)}catch(e){c.error(e.message)}}),e.querySelector(`#save-github-token`)?.addEventListener(`click`,async()=>{let t=e.querySelector(`#github-token-input`),r=t?.value.trim()??``;if(r===``){c.error(`Token required`);return}try{await n.github.save(r),c.success(`GitHub token saved`),t&&(t.value=``),await J(e)}catch(e){c.error(e.message)}}),e.querySelector(`#del-github-token`)?.addEventListener(`click`,async()=>{if(confirm(`Remove the stored GitHub token?`))try{await n.github.remove(),c.success(`GitHub token removed`),await J(e)}catch(e){c.error(e.message)}})}catch(t){e.innerHTML=`<div class="hdat-page"><p class="error-message">Error: ${t.message}</p></div>`}}function Y(){return{mount:J}}function X(e){let t=document.createElement(`div`);return t.textContent=e,t.innerHTML}var Z=[];function Q(){let e=null,t=null,n=null,r=null,i=null;function a(e){e.innerHTML=`<div class="hdat-page">
			<div class="playground-header">
				<h2>Playground</h2>
				<div class="playground-model-picker">
					<span class="picker-label">Model:</span>
					<input id="pg-model" type="text" placeholder="auto-route (optional)" autocomplete="off">
				</div>
			</div>

			<div class="playground-console">
				<div id="pg-log" class="pg-log" aria-live="polite"></div>
				<form id="pg-form" class="pg-chat-input-bar">
					<textarea id="pg-input" placeholder="Type a message..." rows="2"></textarea>
					<div class="pg-chat-actions">
						<button type="submit" id="pg-send" class="btn-primary">Send</button>
						<button type="button" id="pg-clear" class="btn-sm">Clear</button>
					</div>
				</form>
			</div>
		</div>`}function o(){e&&(e.innerHTML=Z.map(e=>`<div class="pg-msg pg-${e.role}"><strong>${e.role}</strong><div>${X(e.content)}</div></div>`).join(``),e.scrollTop=e.scrollHeight)}async function s(t,n){if(!e)return;Z.push({role:`user`,content:n});let r={role:`assistant`,content:``};Z.push(r),o();let i=await fetch(hdatAdmin.restUrl+`/admin/playground/stream`,{method:`POST`,headers:{"Content-Type":`application/json`,"X-WP-Nonce":hdatAdmin.nonce},body:JSON.stringify({messages:Z.filter(e=>e.content!==``).map(e=>({role:e.role,content:e.content})),model:t===``?void 0:t})});if(!i.ok||!i.body){r.content=`Error: HTTP ${i.status}`,o();return}let a=i.body.getReader(),s=new TextDecoder,c=``;for(;;){let{done:e,value:t}=await a.read();if(e)break;c+=s.decode(t,{stream:!0});let n;for(;(n=c.indexOf(`
`))!==-1;){let e=c.slice(0,n).trim();if(c=c.slice(n+1),!e.startsWith(`data:`))continue;let t=e.slice(5).trim();if(t===`[DONE]`)return;try{let e=JSON.parse(t);if(e.error)r.content+=`\n[${e.error.message}]`;else{let t=e.choices?.[0]?.delta?.content;t&&(r.content+=t)}o()}catch{}}}}return{mount(c){a(c),e=c.querySelector(`#pg-log`),t=c.querySelector(`#pg-form`),n=c.querySelector(`#pg-input`),r=c.querySelector(`#pg-model`),i=c.querySelector(`#pg-send`),o(),t.addEventListener(`submit`,async e=>{if(e.preventDefault(),!n||!r||!i)return;let t=n.value.trim();if(t!==``){n.value=``,i.disabled=!0;try{await s(r.value.trim(),t)}finally{i.disabled=!1}}}),c.querySelector(`#pg-clear`)?.addEventListener(`click`,()=>{Z.length=0,o()})}}}var $=[{hash:`#/dashboard`,label:`Dashboard`,icon:`<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>`},{hash:`#/credentials`,label:`Credentials`,icon:`<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 2l-2 2m-7.61 7.61a5.5 5.5 0 1 1-7.778 7.778 5.5 5.5 0 0 1 7.777-7.777zm0 0L15.5 7.5m0 0l3 3L22 7l-3-3m-3.5 3.5L19 4"/></svg>`},{hash:`#/tokens`,label:`Tokens`,icon:`<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="7" width="20" height="14" rx="2" ry="2"/><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/></svg>`},{hash:`#/usage`,label:`Usage`,icon:`<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>`},{hash:`#/openrouter`,label:`OpenRouter`,icon:`<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg>`},{hash:`#/playground`,label:`Playground`,icon:`<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>`},{hash:`#/settings`,label:`Settings`,icon:`<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"/><path d="M12 1v6m0 6v6m5.2-13.2l-4.2 4.2m-2 2l-4.2 4.2M23 12h-6m-6 0H1m18.2 5.2l-4.2-4.2m-2-2l-4.2-4.2"/></svg>`}];function ee(){let e=document.getElementById(`hdat-nav`);e&&(e.innerHTML=`<div class="nav-brand"><span>HD AI</span> Toolkit</div>`+$.map(e=>`<a href="${e.hash}"><span class="nav-icon">${e.icon}</span>${e.label}</a>`).join(``))}e.register(`#/dashboard`,i),e.register(`#/credentials`,T),e.register(`#/tokens`,A),e.register(`#/usage`,N),e.register(`#/openrouter`,G),e.register(`#/playground`,Q),e.register(`#/settings`,Y),document.addEventListener(`DOMContentLoaded`,()=>{ee(),e.init()});