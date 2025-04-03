(function(){var t={257:function(){},961:function(t,e,a){var n=a(257);n.__esModule&&(n=n.default),"string"==typeof n&&(n=[[t.id,n,""]]),n.locals&&(t.exports=n.locals),a(346).Z("46aca552",n,!0,{})},346:function(t,e,a){"use strict";function n(t,e){for(var a=[],n={},s=0;s<e.length;s++){var r=e[s],i=r[0],o={id:t+":"+s,css:r[1],media:r[2],sourceMap:r[3]};n[i]?n[i].parts.push(o):a.push(n[i]={id:i,parts:[o]})}return a}a.d(e,{Z:function(){return h}});var s="undefined"!=typeof document;if("undefined"!=typeof DEBUG&&DEBUG&&!s)throw Error("vue-style-loader cannot be used in a non-browser environment. Use { target: 'node' } in your Webpack config to indicate a server-rendering environment.");var r={},i=s&&(document.head||document.getElementsByTagName("head")[0]),o=null,d=0,c=!1,l=function(){},p=null,m="data-vue-ssr-id",u="undefined"!=typeof navigator&&/msie [6-9]\b/.test(navigator.userAgent.toLowerCase());function h(t,e,a,s){c=a,p=s||{};var i=n(t,e);return v(i),function(e){for(var a=[],s=0;s<i.length;s++){var o=r[i[s].id];o.refs--,a.push(o)}e?v(i=n(t,e)):i=[];for(var s=0;s<a.length;s++){var o=a[s];if(0===o.refs){for(var d=0;d<o.parts.length;d++)o.parts[d]();delete r[o.id]}}}}function v(t){for(var e=0;e<t.length;e++){var a=t[e],n=r[a.id];if(n){n.refs++;for(var s=0;s<n.parts.length;s++)n.parts[s](a.parts[s]);for(;s<a.parts.length;s++)n.parts.push(y(a.parts[s]));n.parts.length>a.parts.length&&(n.parts.length=a.parts.length)}else{for(var i=[],s=0;s<a.parts.length;s++)i.push(y(a.parts[s]));r[a.id]={id:a.id,refs:1,parts:i}}}}function g(){var t=document.createElement("style");return t.type="text/css",i.appendChild(t),t}function y(t){var e,a,n=document.querySelector("style["+m+'~="'+t.id+'"]');if(n){if(c)return l;n.parentNode.removeChild(n)}if(u){var s=d++;e=f.bind(null,n=o||(o=g()),s,!1),a=f.bind(null,n,s,!0)}else e=w.bind(null,n=g()),a=function(){n.parentNode.removeChild(n)};return e(t),function(n){n?(n.css!==t.css||n.media!==t.media||n.sourceMap!==t.sourceMap)&&e(t=n):a()}}var b=function(){var t=[];return function(e,a){return t[e]=a,t.filter(Boolean).join("\n")}}();function f(t,e,a,n){var s=a?"":n.css;if(t.styleSheet)t.styleSheet.cssText=b(e,s);else{var r=document.createTextNode(s),i=t.childNodes;i[e]&&t.removeChild(i[e]),i.length?t.insertBefore(r,i[e]):t.appendChild(r)}}function w(t,e){var a=e.css,n=e.media,s=e.sourceMap;if(n&&t.setAttribute("media",n),p.ssrId&&t.setAttribute(m,e.id),s&&(a+="\n/*# sourceURL="+s.sources[0]+" */\n/*# sourceMappingURL=data:application/json;base64,"+btoa(unescape(encodeURIComponent(JSON.stringify(s))))+" */"),t.styleSheet)t.styleSheet.cssText=a;else{for(;t.firstChild;)t.removeChild(t.firstChild);t.appendChild(document.createTextNode(a))}}}},e={};function a(n){var s=e[n];if(void 0!==s)return s.exports;var r=e[n]={id:n,exports:{}};return t[n](r,r.exports,a),r.exports}a.d=function(t,e){for(var n in e)a.o(e,n)&&!a.o(t,n)&&Object.defineProperty(t,n,{enumerable:!0,get:e[n]})},a.o=function(t,e){return Object.prototype.hasOwnProperty.call(t,e)},a.p="bundles/zamptax/",window?.__sw__?.assetPath&&(a.p=window.__sw__.assetPath+"/bundles/zamptax/"),function(){"use strict";a(961),Shopware.Component.register("zamp-tax-main-page",{template:'<div class="zamp-tax-main-page">\r\n	<div class="zamp-page">\r\n		<div class="zamp-page__body">\r\n			<header class="zamp-page__head-area">\r\n				<div class="zamp-page__smart-bar">\r\n					<div class="zamp-navigation">\r\n						<a href="#/sw/extension/my-extensions/listing/app?limit=25&amp;page=1" class="zamp-navigation__link">\r\n							&#x2B05; &emsp; {{ $tc(\'buttons.back\') }}</a>\r\n					</div>\r\n					<div class="zamp-content">\r\n						<div class="zamp-page__module-info">\r\n							<div class="zamp-page__module-icon">\r\n								<div class="zamp-extension-icon">\r\n									<img class="zamp-extension-icon__icon" :src="assetFilter(\'/zamptax/Zamp-Logo.png\')" alt="Zamp Logo" width="180">\r\n								</div>\r\n							</div>\r\n						</div>\r\n						<div class="zamp-page__actions">\r\n							<p class="zamp-contact-text" id="prompt-text">{{ connected ? $tc(\'labels.quest\') : $tc(\'labels.need\') }}</p>\r\n							<a class="zamp-button button-link" id="contact-button" target="_blank" rel="noopener" href="https://zamp.com/platforms/shopware">{{ connected ? $tc(\'buttons.contact\') : $tc(\'buttons.create\') }}</a>\r\n						</div>\r\n					</div>\r\n				</div>\r\n			</header>\r\n\r\n			<main class="zamp-page__content">\r\n				<div class="zamp-api">\r\n					<div class="zamp-card-wrapper">\r\n						<div></div>\r\n						<div class="zamp-api__card">\r\n							{% block zamp_tabs %}\r\n\r\n								<sw-tabs\r\n									class="zamp-tabs"\r\n									position-identifier="zamp-tax">\r\n				\r\n									{% block zamp_tabs_settings %}\r\n										<sw-tabs-item\r\n										:active="activeTab === \'settings\'"\r\n										name="settings"\r\n										class="zamp-tabs__tab-settings"\r\n										@click="onTabChange(\'settings\')"\r\n										>\r\n											{{ $tc(\'tabs.settings\') }}\r\n										</sw-tabs-item>						\r\n									{% endblock %}\r\n				\r\n									{% block zamp_tabs_historical_sync %}\r\n										<sw-tabs-item\r\n										:active="activeTab === \'historicalSync\'"\r\n										:disabled="!connected"\r\n										name="historicalSync"\r\n										class="zamp-tabs__tab-historical-sync"\r\n										@click="onTabChange(\'historicalSync\')"\r\n										>\r\n										{{ $tc(\'tabs.historicalSync\') }}\r\n										</sw-tabs-item>						\r\n									{% endblock %}\r\n				\r\n									{% block zamp_tabs_error_logs %}\r\n										<sw-tabs-item\r\n										:active="activeTab === \'errorLogs\'"\r\n										:disabled="!connected"\r\n										name="errorLogs"\r\n										class="zamp-tabs__tab-error-logs"\r\n										@click="onTabChange(\'errorLogs\')"\r\n										>\r\n										{{ $tc(\'tabs.errorLogs\') }}\r\n										</sw-tabs-item>						\r\n									{% endblock %}\r\n								</sw-tabs>\r\n							{% endblock %}\r\n\r\n							{% block zamp_settings_form %}\r\n							<template\r\n							v-if="activeTab === \'settings\'">\r\n								<div class="zamp-api__card__header">\r\n									<div class="zamp-api__card__titles">\r\n										<div class="zamp-api__card__title">{{ $tc(\'cards.settings\') }}</div>\r\n									</div>\r\n								</div>\r\n								<div class="gray-divide"></div>\r\n								<div class="zamp-api__card__content">\r\n									<form action="" id="zamp-token-form">\r\n										<div class="zamp-api-input-wrapper">\r\n											<div class="zamp-field" :label="$tc(\'labels.token\')">\r\n												<div class="zamp-field-label">\r\n													<label for="zamp-token-input">{{ $tc(\'labels.token\') }}</label>\r\n												</div>\r\n												<div class="zamp-field-input">\r\n													<input type="password" :placeholder="$tc(\'places.token\')" maxlength="64" id="zamp-token-input" autocomplete="off">\r\n												</div>\r\n											</div>\r\n										</div>\r\n										<div class="zamp-button-wrapper">\r\n											<button id="token-button" type="submit" class="zamp-button">{{ connected ? $tc(\'buttons.update\') : $tc(\'buttons.connect\') }}</button>\r\n										</div>\r\n										<small id="small-disclaimer-text">{{ connected ? $tc(\'disclaim.connected\') : $tc(\'disclaim.disclaimer\') }}</small>\r\n									</form>\r\n\r\n								</div>\r\n								<div class="gray-divide"></div>\r\n								<div class="zamp-api__card__content">\r\n									<form action="" id="zamp-settings-form">\r\n										<div class="zamp-state-input-wrapper">\r\n											<div class="zamp-field" :label="$tc(\'labels.state\')">\r\n												<div class="zamp-select-input">\r\n													<sw-multi-select\r\n														v-model="selectedStates"\r\n														:options="stateOptions"\r\n														labelProperty="name"\r\n														valueProperty="code"\r\n														:placeholder="$tc(\'places.states\')"\r\n														:searchable="true"\r\n														:multiple="true"\r\n														:disabled="!connected"\r\n													></sw-multi-select>\r\n												</div>\r\n											</div>\r\n										</div>\r\n										<div class="zamp-calc-input-wrapper">\r\n											<div class="zamp-field" :label="$tc(\'switches.allow\')">\r\n												<div id="zamp-calc-input" class="zamp-calc-input">\r\n													<sw-switch-field\r\n														v-model="calcEnabled"\r\n														:disabled="!connected"\r\n														:label="$tc(\'switches.allow\')"\r\n													></sw-switch-field>\r\n												</div>\r\n											</div>\r\n										</div>\r\n										<div class="zamp-trans-input-wrapper">\r\n											<div class="zamp-field" :label="$tc(\'switches.send\')">\r\n												<div id="zamp-trans-input" class="zamp-trans-input">\r\n													<sw-switch-field\r\n														v-model="transEnabled"\r\n														:disabled="!connected"\r\n														:label="$tc(\'switches.send\')"\r\n													></sw-switch-field>\r\n												</div>\r\n											</div>\r\n										</div>\r\n\r\n										<div class="zamp-button-wrapper">\r\n											<button id="config-button" type="submit" :disabled="!connected" class="zamp-button">{{ $tc(\'buttons.save\') }}</button>\r\n										</div>\r\n										\r\n									</form>\r\n\r\n								</div>\r\n								<div class="gray-divide-last"></div>\r\n							</template>	\r\n							{% endblock %}\r\n\r\n							{% block zamp_historical_sync_form %}\r\n								<template\r\n								v-if="activeTab === \'historicalSync\'">\r\n									<div class="zamp-api__card__header">\r\n										<div class="zamp-api__card__titles">\r\n											<div class="zamp-api__card__title">{{ $tc(\'cards.historicalSync\') }}</div>\r\n										</div>\r\n									</div>\r\n									<div class="gray-divide"></div>\r\n									<div class="zamp-api__card__content">\r\n										<form action="" id="historical-data-form" style="display: grid; grid-template-columns: 50% 1fr; grid-template-rows: auto auto; grid-column-gap: 16px; grid-template-areas: \'input input\'\'button context\';">\r\n											<div class="zamp-sync-input-wrapper">\r\n												<div class="zamp-field" :label="$tc(\'labels.start\')">\r\n													<div class="zamp-field-label">\r\n														<label for="zamp-sync-start-input">{{ $tc(\'labels.start\') }}</label>\r\n													</div>\r\n													<div class="zamp-field-input">\r\n														<input style="outline: none; border: 0px; padding: 6px; width: 100%;" type="date" id="zamp-sync-start-input" min="2022-01-01" autocomplete="off">\r\n													</div>\r\n												</div>\r\n											</div>\r\n											<div class="zamp-sync-input-wrapper">\r\n												<div class="zamp-field" :label="$tc(\'labels.end\')">\r\n													<div class="zamp-field-label">\r\n														<label for="zamp-sync-end-input">{{ $tc(\'labels.end\') }}</label>\r\n													</div>\r\n													<div class="zamp-field-input">\r\n														<input style="outline: none;  border: 0px; padding: 6px; width: 100%;" type="date" id="zamp-sync-end-input" min="2022-01-01" autocomplete="off">\r\n													</div>\r\n												</div>\r\n											</div>\r\n											<div class="zamp-button-wrapper">\r\n												<button id="sync-button" type="submit" class="zamp-button">{{ $tc(\'buttons.sync\') }}</button>\r\n												<small id="small-warning-text"></small>\r\n											</div>\r\n										</form>										\r\n	\r\n									</div>\r\n									<div class="gray-divide"></div>\r\n									<div class="zamp-api__card__content">\r\n										<sw-data-grid\r\n											:showSelection="false"\r\n											:showActions="false"\r\n											:dataSource="[\r\n												{ id: \'syncRequested\', status: $tc(\'status.request\'), count: totalSyncRequested },\r\n												{ id: \'syncRequested\', status: $tc(\'status.complete\'), count: totalSyncCompleted },\r\n												{ id: \'syncExists\', status: $tc(\'status.already\'), count: totalSyncExists },\r\n												{ id: \'syncUpdated\', status: $tc(\'status.update\'), count: totalSyncUpdated },\r\n												{ id: \'syncRequested\', status: $tc(\'status.fail\'), count: totalSyncFailed }\r\n												\r\n											]"\r\n\r\n											:columns="[\r\n												{ property: \'status\', label: $tc(\'status.stat\')},\r\n												{ property: \'count\', label: $tc(\'status.count\') }\r\n											]">\r\n										</sw-data-grid>\r\n									</div>\r\n									<div class="gray-divide-last"></div>\r\n								</template>\r\n\r\n							{% endblock %}\r\n\r\n                            {% block zamp_logs_form %}\r\n								<template\r\n								v-if="activeTab === \'errorLogs\'">\r\n									<div class="zamp-api__card__header">\r\n										<div class="zamp-api__card__titles">\r\n											<div class="zamp-api__card__title">{{ $tc(\'cards.errorLogs\') }}</div>\r\n										</div>\r\n									</div>\r\n									<div class="gray-divide"></div>\r\n									<div class="zamp-api__card__content">\r\n										<form action="" id="zamp-logs-form" style="display: grid; grid-template-columns: 50% 1fr; grid-template-rows: auto auto; grid-column-gap: 16px; grid-template-areas: \'input input\' \'button button\';">\r\n											<div class="zamp-logs-input-wrapper">\r\n												<div class="zamp-field" :label="$tc(\'labels.date\')">\r\n													<div class="zamp-field-label">\r\n														<label for="zamp-logs-date-input">{{ $tc(\'labels.date\') }}</label>\r\n													</div>\r\n													<div class="zamp-field-input">\r\n														<input style="outline: none; border: 0px; padding: 6px; width: 100%;" type="date" id="zamp-logs-date-input" min="2022-01-01">\r\n													</div>\r\n												</div>\r\n											</div>\r\n											<div class="zamp-logs-input-wrapper">\r\n\r\n											</div>\r\n											<div class="zamp-button-wrapper">\r\n												<button id="logs-button" type="submit" class="zamp-button">{{ $tc(\'buttons.get\') }}</button>\r\n												<small id="small-logs-text"></small>\r\n											</div>\r\n                                            <div class="zamp-button-wrapper">\r\n                                                <button id="logs-down-button" type="button" class="zamp-button">{{ $tc(\'buttons.down\') }}</button>\r\n                                                <pre style="display: none; width: 0px; height: 0px;" id="small-logs-down-text"></pre>\r\n                                            </div>\r\n											\r\n										</form>										\r\n                                        \r\n									</div>\r\n\r\n                                    <hr/>\r\n\r\n									<div class="zamp-api__card__content" id="logs-ui">\r\n										\r\n									</div>\r\n                                    \r\n									<div class="gray-divide-last"></div>\r\n								</template>\r\n\r\n							{% endblock %}\r\n													\r\n						</div>\r\n						<div></div>\r\n					</div>\r\n				</div>\r\n			</main>\r\n		</div>\r\n	</div>\r\n</div>\r\n',inject:["repositoryFactory"],data:function(){return{activeTab:"settings",connected:!1,entityId:"",entity:void 0,paidEntity:void 0,paidId:"",paidOrders:[],calcEnabled:!1,transEnabled:!1,selectedStates:[],stateOptions:[{code:"AL",name:"AL - Alabama"},{code:"AK",name:"AK - Alaska"},{code:"AZ",name:"AZ - Arizona"},{code:"AR",name:"AR - Arkansas"},{code:"CA",name:"CA - California"},{code:"CO",name:"CO - Colorado"},{code:"CT",name:"CT - Connecticut"},{code:"DE",name:"DE - Delaware"},{code:"DC",name:"DC - District of Columbia"},{code:"FL",name:"FL - Florida"},{code:"GA",name:"GA - Georgia"},{code:"HI",name:"HI - Hawaii"},{code:"ID",name:"ID - Idaho"},{code:"IL",name:"IL - Illinois"},{code:"IN",name:"IN - Indiana"},{code:"IA",name:"IA - Iowa"},{code:"KS",name:"KS - Kansas"},{code:"KY",name:"KY - Kentucky"},{code:"LA",name:"LA - Louisiana"},{code:"ME",name:"ME - Maine"},{code:"MD",name:"MD - Maryland"},{code:"MA",name:"MA - Massachusetts"},{code:"MI",name:"MI - Michigan"},{code:"MN",name:"MN - Minnesota"},{code:"MS",name:"MS - Mississippi"},{code:"MO",name:"MO - Missouri"},{code:"MT",name:"MT - Montana"},{code:"NE",name:"NE - Nebraska"},{code:"NV",name:"NV - Nevada"},{code:"NH",name:"NH - New Hampshire"},{code:"NJ",name:"NJ - New Jersey"},{code:"NM",name:"NM - New Mexico"},{code:"NY",name:"NY - New York"},{code:"NC",name:"NC - North Carolina"},{code:"ND",name:"ND - North Dakota"},{code:"OH",name:"OH - Ohio"},{code:"OK",name:"OK - Oklahoma"},{code:"OR",name:"OR - Oregon"},{code:"PA",name:"PA - Pennsylvania"},{code:"PR",name:"PR - Puerto Rico"},{code:"RI",name:"RI - Rhode Island"},{code:"SC",name:"SC - South Carolina"},{code:"SD",name:"SD - South Dakota"},{code:"TN",name:"TN - Tennessee"},{code:"TX",name:"TX - Texas"},{code:"UT",name:"UT - Utah"},{code:"VT",name:"VT - Vermont"},{code:"VA",name:"VA - Virginia"},{code:"WA",name:"WA - Washington"},{code:"WV",name:"WV - West Virginia"},{code:"WI",name:"WI - Wisconsin"},{code:"WY",name:"WY - Wyoming"}],totalSyncRequested:0,totalSyncCompleted:0,totalSyncExists:0,totalSyncUpdated:0,totalSyncFailed:0,orders:[]}},watch:{selectedStates:"updateSelectedStates",activeTab(t){"settings"==t?(this.$nextTick(()=>{let t=document.getElementById("zamp-token-form"),e=document.getElementById("zamp-settings-form");console.log(t),t&&t.addEventListener("submit",t=>{this.testToken(t)}),e&&e.addEventListener("submit",t=>{this.saveConfig(t)});let a=Shopware.Utils.createId(),n=new Shopware.Data.Criteria;this.zampSettingsRepository.search(n,Shopware.Context.api).then(t=>{t.length>0?(this.entity=t.first(),this.entityId=t.first().id,null!==this.entity.apiToken&&(document.querySelector("#zamp-token-input").value=this.entity.apiToken,document.querySelector("#small-disclaimer-text").classList.add("green-text"),this.connected=!0),null!==this.entity.taxableStates&&(this.selectedStates=this.entity.taxableStates.split(",")),this.entity.calculationsEnabled&&(this.calcEnabled=!0),this.entity.transactionsEnabled&&(this.transEnabled=!0)):(this.entity=this.zampSettingsRepository.create(Shopware.Context.api),this.entity.id=a,this.entityId=a,console.log(this.entity.id),this.zampSettingsRepository.save(this.entity,Shopware.Context.api))})}),this.observeDOM()):"historicalSync"===t?this.$nextTick(()=>{let t=document.getElementById("historical-data-form");document.querySelector("#small-warning-text").innerText="",t&&t.addEventListener("submit",t=>{this.syncHistory(t)})}):"errorLogs"===t&&this.$nextTick(()=>{let t=document.getElementById("zamp-logs-form");if(document.querySelector("#small-logs-text").innerText="",t){t.addEventListener("submit",t=>{this.loadLog(t)});let a=document.getElementById("zamp-logs-date-input"),n=new Date().toISOString().split("T")[0];a.value=n;var e=document.createElement("script");e.src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js",document.head.appendChild(e),document.getElementById("logs-down-button").addEventListener("click",t=>{this.downloadLog(t)})}})}},computed:{assetFilter(){return Shopware.Filter.getByName("asset")},zampSettingsRepository(){return this.repositoryFactory.create("zamp_settings")},orderRepository(){return this.repositoryFactory.create("order")},orderTransactionRepository(){return this.repositoryFactory.create("order_transaction")},stateTransalationRepository(){return this.repositoryFactory.create("state_machine_state_translation")}},mixins:[Shopware.Mixin.getByName("notification")],created(){let t=Shopware.Utils.createId(),e=new Shopware.Data.Criteria;this.zampSettingsRepository.search(e,Shopware.Context.api).then(e=>{e.length>0?(this.entity=e.first(),this.entityId=e.first().id,null!==this.entity.apiToken&&(document.querySelector("#zamp-token-input").value=this.entity.apiToken,document.querySelector("#small-disclaimer-text").classList.add("green-text"),this.connected=!0),null!==this.entity.taxableStates&&(this.selectedStates=this.entity.taxableStates.split(",")),this.entity.calculationsEnabled&&(this.calcEnabled=!0),this.entity.transactionsEnabled&&(this.transEnabled=!0)):(this.entity=this.zampSettingsRepository.create(Shopware.Context.api),this.entity.id=t,this.entityId=t,console.log(this.entity.id),this.zampSettingsRepository.save(this.entity,Shopware.Context.api))})},mounted(){let t=document.getElementById("zamp-token-form"),e=document.getElementById("zamp-settings-form");t&&t.addEventListener("submit",t=>{this.testToken(t)}),e&&e.addEventListener("submit",t=>{this.saveConfig(t)}),this.observeDOM()},methods:{downloadLog(t){t.preventDefault(t);let{jsPDF:e}=window.jspdf,a=new e,n=document.getElementById("zamp-logs-date-input"),s=document.getElementById("logs-ui"),r=n.value;if(console.log(r),!r){s.textContent=this.$tc("messages.date");return}let i=`/var/log/ZampTax-${r}.log`;fetch(i).then(t=>{if(!t.ok)throw Error("Network response was not ok.");return t.text()}).then(t=>{s.textContent=this.$tc("messages.init");let e=`Zamp Shopware Log ${r}`,n=t.split("\n"),i=a.internal.pageSize.height,o=a.internal.pageSize.width;a.setFontSize(16),a.text(e,15,31),a.setFontSize(12);let d=36;n.forEach(t=>{a.splitTextToSize(t,o-30).forEach(t=>{d+10>i-15&&(a.addPage(),d=15),a.text(t,15,d),d+=10})}),a.save(`Zamp-Shopware-log-${r}.pdf`)}).catch(t=>{s.textContent=this.$tc("errors.log"),console.error("Error fetching file content: ",t)})},loadLog(t){t.preventDefault(),console.log("loadLog function entered");let e=document.getElementById("zamp-logs-date-input"),a=document.getElementById("logs-ui"),n=e.value;if(console.log(n),!n){a.textContent=this.$tc("messages.date");return}let s=`/var/log/ZampTax-${n}.log`;fetch(s).then(t=>{if(!t.ok)throw Error("Network response was not ok.");return t.text()}).then(t=>{a.innerHTML=`<pre>${t}</pre>`}).catch(t=>{a.textContent=this.$tc("errors.log"),console.error("Error fetching file content: ",t)})},onTabChange(t){this.activeTab=t},syncHistory(t){t.preventDefault();let e=Shopware.Context.api.authToken.access,a="",n=new Shopware.Data.Criteria;this.zampSettingsRepository.search(n,Shopware.Context.api).then(t=>{if(t.length>0){let{Criteria:s}=Shopware.Data;this.entity=t.first(),this.entityId=t.first().id,null!==this.entity.apiToken&&console.log("Token is: ",a=this.entity.apiToken);let r=new Shopware.Data.Criteria;r.addFilter(s.equals("name","Paid")),this.stateTransalationRepository.search(r,Shopware.Context.api).then(t=>{if(t.length>0){this.paidEntity=t.first(),this.paidId=t.first().stateMachineStateId;let e=new Shopware.Data.Criteria;e.addFilter(s.equals("stateId",this.paidId)),this.orderTransactionRepository.search(e,Shopware.Context.api).then(t=>{t.length>0&&(t.forEach(t=>{this.paidOrders.includes(t.orderId)||this.paidOrders.push(t.orderId)}),console.log("Paid Orders: ",this.paidOrders))})}});let i=document.getElementById("zamp-sync-start-input").value,o=document.getElementById("zamp-sync-end-input").value;if(""==i&&(i="2022-01-01"),""==o){let t=new Date,e=t.getFullYear(),a=String(t.getMonth()+1).padStart(2,"0"),n=String(t.getDate()).padStart(2,"0");o=`${e}-${a}-${n} 23:59:59`}else{let t=new Date(o),e=t.getFullYear(),a=String(t.getMonth()+1).padStart(2,"0"),n=String(t.getDate()).padStart(2,"0");o=`${e}-${a}-${n} 23:59:59`}let d=new Shopware.Data.Criteria;n.addSorting(s.sort("createdAt","DESC")),this.orderRepository.search(d,Shopware.Context.api).then(t=>{let n={};t.forEach(t=>{n[t.id]||(n[t.id]=t)});let s=Object.values(n).filter(t=>{console.log("Raw Order: ",t);let e=new Date(t.createdAt),a=new Date(i),n=new Date(o);return console.log(`Order ID: ${t.id}, Created At: ${e}, In Range: ${e>=a&&e<=n}`),e>=a&&e<=n});this.orders=s,console.log("Filtered Orders: ",this.orders),this.totalSyncRequested=this.orders.length,this.totalSyncCompleted=0,this.totalSyncFailed=0,this.totalSyncExists=0,this.orders.forEach(t=>{var n=new FormData;n.append("order_id",t.id),n.append("token",a);let s=Shopware.Context.api.apiPath;fetch(`${s}/v1/_action/zamp-tax/sync-order`,{headers:{Authorization:`Bearer ${e}`},method:"POST",body:n}).then(t=>t.json()).then(t=>{"completed"==t.status?this.totalSyncCompleted+=1:"exists"==t.status?this.totalSyncExists+=1:"failed"==t.status&&(this.totalSyncFailed+=1),console.log(t)})})}).catch(t=>{console.error("Error fetching orders: ",t)})}else document.querySelector("#small-warning-text").innerText=this.$tc("messages.syncwarn")})},testToken(t){let e=Shopware.Context.api.authToken.access;t.preventDefault();var a=document.querySelector("#zamp-token-input").value,n=new FormData;n.append("token",a);let s=Shopware.Context.api.apiPath;fetch(`${s}/v1/_action/zamp-tax/test-api`,{headers:{Authorization:`Bearer ${e}`},method:"POST",body:n}).then(t=>t.json()).then(t=>{t.valid?(this.createNotificationSuccess({title:this.$tc("global.default.success"),message:this.$tc("messages.successtoken")}),this.entity.apiToken=a,this.connected=!0,this.zampSettingsRepository.save(this.entity,Shopware.Context.api).then(()=>{this.zampSettingsRepository.get(this.entityId,Shopware.Context.api).then(t=>{this.entity=t})}),document.querySelector("#zamp-token-input").value=this.entity.apiToken,document.querySelector("#small-disclaimer-text").classList.add("green-text"),this.connected=!0):(this.createNotificationError({title:this.$tc("global.notification.unspecifiedSaveErrorMessage"),message:this.$tc("messages.failtoken")}),this.connected=!1)})},updateSelectedStates(){let t=document.querySelector(".sw-select-selection-list");if(t){t.innerHTML="",this.selectedStates.forEach(e=>{console.log(e.code);let a=document.createElement("li");a.className="sw-select-selection-list__item-holder",a.dataset.id=e.code;let n=document.createElement("span");n.className="sw-label sw-label--appearance-default sw-label--size-default sw-label--dismissable";let s=document.createElement("span");s.className="sw-label__caption";let r=document.createElement("span");r.className="sw-select-selection-list__item",r.textContent=`${e.code} - ${e.name}`,s.appendChild(r),n.appendChild(s);let i=document.createElement("button");i.className="sw-label__dismiss",i.title="Remove",i.setAttribute("data-state",e.code),console.log("Button created for state:",e.code,"Data attribute:",i.getAttribute("data-state"));let o=document.createElement("span");o.className="sw-icon sw-icon--fill icon--regular-times-s",o.innerHTML='<svg id="meteor-icon-kit__regular-times-s" viewBox="0 0 12 12" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M6 4.5858L10.2929 0.29289C10.6834 -0.09763 11.3166 -0.09763 11.7071 0.29289C12.0976 0.68342 12.0976 1.31658 11.7071 1.70711L7.4142 6L11.7071 10.2929C12.0976 10.6834 12.0976 11.3166 11.7071 11.7071C11.3166 12.0976 10.6834 12.0976 10.2929 11.7071L6 7.4142L1.70711 11.7071C1.31658 12.0976 0.68342 12.0976 0.29289 11.7071C-0.09763 11.3166 -0.09763 10.6834 0.29289 10.2929L4.5858 6L0.29289 1.70711C-0.09763 1.31658 -0.09763 0.68342 0.29289 0.29289C0.68342 -0.09763 1.31658 -0.09763 1.70711 0.29289L6 4.5858z" fill="#758CA3"></path></svg>',i.appendChild(o),n.appendChild(i),a.appendChild(n),t.appendChild(a)});let e=document.createElement("li");e.innerHTML='<input class="sw-select-selection-list__input" type="text" placeholder="" value="">',t.appendChild(e)}},observeDOM(){let t=document.body,e={childList:!0,subtree:!0};this.calcEventListenersAdded=!1,this.transEventListenersAdded=!1,this.observer=new MutationObserver(a=>{let n=!1;for(let t of a)if("childList"===t.type){let t=document.querySelector("#zamp-calc-input");if(t){let e=Array.from(t.querySelectorAll("*")).find(t=>t.id.startsWith("sw-field--"));e&&!this.calcEventListenersAdded&&(e.addEventListener("click",t=>{this.calcEnabled?this.calcEnabled=!1:this.calcEnabled=!0}),this.calcEventListenersAdded=!0)}let e=document.querySelector("#zamp-trans-input");if(e){let t=Array.from(e.querySelectorAll("*")).find(t=>t.id.startsWith("sw-field--"));t&&!this.transEventListenersAdded&&(t.addEventListener("click",t=>{this.transEnabled?this.transEnabled=!1:this.transEnabled=!0}),this.transEventListenersAdded=!0)}let a=document.querySelectorAll("li.sw-select-result");if(a.length>0){n=!0,a.forEach(t=>{t.addEventListener("click",e=>{let a=t.querySelector("div.sw-highlight-text").innerText.split(" - ")[0];this.selectedStates.includes(a)||(console.log(this.selectedStates),this.selectedStates.push(a))})});break}let s=document.querySelectorAll("button.sw-label__dismiss");if(s.length>0){s.forEach(t=>{t.addEventListener("click",e=>{let a=t.parentElement.parentElement.getAttribute("data-id");this.selectedStates.includes(a)&&(this.selectedStates=this.selectedStates.filter(t=>t!==a))})});break}}n||this.observer.observe(t,e)}),t&&this.observer.observe(t,e)},saveConfig(t){t.preventDefault();let e=this.selectedStates.join(",");this.entity.taxableStates=e,this.entity.calculationsEnabled=this.calcEnabled,this.entity.transactionsEnabled=this.transEnabled,this.zampSettingsRepository.save(this.entity,Shopware.Context.api).then(()=>{this.createNotificationSuccess({title:this.$tc("global.default.success"),message:this.$tc("messages.success")}),this.zampSettingsRepository.get(this.entityId,Shopware.Context.api).then(t=>{this.entity=t})}).catch(t=>{this.createNotificationError({title:this.$tc("global.notification.unspecifiedSaveErrorMessage"),message:this.$tc("messages.fail")+t.message})})}},beforeUnmount(){this.observer&&this.observer.disconnect()}}),Shopware.Module.register("zamp-tax",{type:"plugin",name:"zamp-tax",title:"Zamp Tax",description:"Zamp Tax Plugin",color:"#5075d3",icon:"default-action-settings",routes:{dashboard:{component:"zamp-tax-main-page",path:"dashboard",meta:{allow:["user","admin"]}}},navigation:[{label:"Zamp Tax",color:"#5075d3",path:"zamp.tax.dashboard",icon:"default-action-settings",parent:"sw-extension",position:100}]});let{Component:t,Mixin:e}=Shopware;t.override("sw-settings-customer-group-detail",{template:'{% extends \'@Administration/module/sw-settings-customer-group/page/sw-settings-customer-group-detail.html.twig\' %}\r\n\r\n	{% block sw_settings_customer_group_detail_content_card %}\r\n		{% parent %}\r\n	\r\n		<sw-card\r\n			:is-loading="isLoading"\r\n			title="Entity Exemption"\r\n			position-identifier="sw-settings-customer-group-detail-content"\r\n		>\r\n			<template>\r\n				<sw-container\r\n				>\r\n				{% block sw_settings_customer_group_detail_content_card_tax_exempt_code %}\r\n					<sw-text-field\r\n						v-model:value="customerGroup.taxExemptCode"\r\n						label="Entity Exemption Code"\r\n						class="sw-customer-group-tax-exempt-code"\r\n						placeholder="Enter entity exemption code"\r\n					/>\r\n				{% endblock %}\r\n				</sw-container>\r\n	\r\n			</template>\r\n		</sw-card>\r\n	\r\n		\r\n	{% endblock %}',computed:{customerGroup(){return this.$super("customerGroup")},customerGroupRepository(){return this.repositoryFactory.create("customer_group")}},watch:{customerGroup(t){t.customFields&&t.customFields.tax_exempt_code&&(this.customerGroup.taxExemptCode=t.customFields.tax_exempt_code)}},methods:{onSave(){this.$super("onSave");let t=this.customerGroup.customFields||{};t.tax_exempt_code=this.customerGroup.taxExemptCode,this.customerGroup.customFields=t,console.log("Customer Group Tax Exempt Code: ",this.customerGroup.taxExemptCode);var e=this.customerGroup.id;this.customerGroupRepository.save(this.customerGroup).then(()=>this.customerGroupRepository.get(e,Shopware.Context.api)).then(t=>{this.customerGroup=t,console.log("Entity: ",JSON.stringify(this.customerGroup))}).catch(t=>{console.error("Failed to save customer group: ",t),t.response&&t.response.data&&console.error("Error response data: ",t.response.data),this.createNotificationError({message:this.$tc("sw-settings.customerGroup.detail.saveError",0)})})}}}),t.override("sw-product-detail-base",{template:'{% extends \'@Administration/module/sw-product/view/sw-product-detail-base/sw-product-detail-base.html.twig\' %}\r\n\r\n	{% block sw_product_detail_base %}\r\n		{% parent %}\r\n		\r\n		<sw-card\r\n			:is-loading="isLoading"\r\n			title="Zamp Product Tax Code"\r\n			position-identifier="sw-product-detail-zamp-tax-code"\r\n		>\r\n			<sw-container>\r\n				<sw-text-field\r\n                    id="zamp-product-tax-code-input"\r\n					v-model:value="zampProductTaxCode"\r\n					label="Zamp Product Tax Code"\r\n					class="sw-zamp-product-tax-code"\r\n					placeholder="Enter Zamp Product Tax code"\r\n				/>\r\n			</sw-container>\r\n		</sw-card>\r\n	\r\n	{% endblock %}',data:function(){return{zampProductTaxCode:"",zampProductId:"",zampEntityId:"",zampEntity:{}}},computed:{product(){return this.$super("product")},productRepository(){return this.repositoryFactory.create("product")},zampProductTaxCodeRepository(){return this.repositoryFactory.create("zamp_product_tax_code")}},watch:{product(t){if(t){this.zampProductId=t.id;let{Criteria:e}=Shopware.Data;Shopware.Utils.createId();let a=new Shopware.Data.Criteria;a.addFilter(e.equals("productId",t.id)),this.zampProductTaxCodeRepository.search(a,Shopware.Context.api).then(t=>{t.length>0&&(this.zampEntity=t.first(),this.zampEntityId=t.first().id,null!==this.zampEntity.productId&&(this.zampProductId=this.zampEntity.productId),null!==this.zampEntity.productTaxCode&&(this.zampProductTaxCode=this.zampEntity.productTaxCode))})}}},methods:{getTaxCode(){return this.zampProductTaxCode}}}),t.override("sw-product-detail",{data(){return{productTaxCode:"",zampProductId:"",zampEntity:{}}},computed:{zampProductTaxCodeRepository(){return this.repositoryFactory.create("zamp_product_tax_code")},product(){return this.$super("product")}},methods:{onSave(){this.$super("onSave").then(()=>{let t=this.product.id;console.log("Product ID: ",t);let e=document.querySelector("#zamp-product-tax-code-input .sw-block-field__block input").value;if(console.log("Zamp Product Tax Code: ",e),t&&e){let a=new Shopware.Data.Criteria,{Criteria:n}=Shopware.Data;a.addFilter(n.equals("productId",t)),this.zampProductTaxCodeRepository.search(a,Shopware.Context.api).then(a=>{if(a.length>0){this.zampEntity=a.first();let t=this.zampEntity.id;return this.zampEntity.productTaxCode=e,this.zampProductTaxCodeRepository.save(this.zampEntity,Shopware.Context.api).then(()=>(this.createNotificationSuccess({title:"Success",message:"Product updated successfully."}),this.zampProductTaxCodeRepository.get(t,Shopware.Context.api))).then(t=>{this.zampEntity=t})}{this.zampEntity=this.zampProductTaxCodeRepository.create(Shopware.Context.api);let a=Shopware.Utils.createId();return this.zampEntity.id=a,this.zampEntity.productId=t,this.zampEntity.productTaxCode=e,this.zampProductTaxCodeRepository.save(this.zampEntity,Shopware.Context.api).then(()=>(this.createNotificationSuccess({title:"Success",message:"Product saved successfully."}),this.zampProductTaxCodeRepository.get(this.zampEntity.id,Shopware.Context.api))).then(t=>{this.zampEntity=t})}}).catch(t=>{this.createNotificationError({title:"Error",message:"Failed to save or update Zamp Product Tax Code: "+t.message})})}else console.error("Product ID or Zamp Tax Code is missing.")}).catch(t=>{console.error("Failed to save product:",t),this.createNotificationError({title:"Error",message:"Failed to save product: "+t.message})})}}})}()})();