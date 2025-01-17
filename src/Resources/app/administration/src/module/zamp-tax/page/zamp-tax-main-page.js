import template from './zamp-tax-main-page.html.twig';
import './zamp-tax-main-page.scss';

const bear = 'e402ac655d761097e5935096013ee099d4ac28bd42225ccb829e10abf99dc16c';

Shopware.Component.register('zamp-tax-main-page', {
	template,

	inject: [
		'repositoryFactory'
	],

	data: function() {
		return {
			activeTab: 'settings',
			entityId: '',
			entity: undefined,
            paidEntity: undefined,
            paidId: '',
            paidOrders: [],
            calcEnabled: false,
			transEnabled: false,
			retainLogs: false,
            selectedStates: [],
            stateOptions: [
                { code: 'AL', name: 'AL - Alabama' },
                { code: 'AK', name: 'AK - Alaska' },
                { code: 'AZ', name: 'AZ - Arizona' },
                { code: 'AR', name: 'AR - Arkansas' },
                { code: 'CA', name: 'CA - California' },
                { code: 'CO', name: 'CO - Colorado' },
                { code: 'CT', name: 'CT - Connecticut' },
                { code: 'DE', name: 'DE - Delaware' },
                { code: 'DC', name: 'DC - District of Columbia' },
                { code: 'FL', name: 'FL - Florida' },
                { code: 'GA', name: 'GA - Georgia' },
                { code: 'HI', name: 'HI - Hawaii' },
                { code: 'ID', name: 'ID - Idaho' },
                { code: 'IL', name: 'IL - Illinois' },
                { code: 'IN', name: 'IN - Indiana' },
                { code: 'IA', name: 'IA - Iowa' },
                { code: 'KS', name: 'KS - Kansas' },
                { code: 'KY', name: 'KY - Kentucky' },
                { code: 'LA', name: 'LA - Louisiana' },
                { code: 'ME', name: 'ME - Maine' },
                { code: 'MD', name: 'MD - Maryland' },
                { code: 'MA', name: 'MA - Massachusetts' },
                { code: 'MI', name: 'MI - Michigan' },
                { code: 'MN', name: 'MN - Minnesota' },
                { code: 'MS', name: 'MS - Mississippi' },
                { code: 'MO', name: 'MO - Missouri' },
                { code: 'MT', name: 'MT - Montana' },
                { code: 'NE', name: 'NE - Nebraska' },
                { code: 'NV', name: 'NV - Nevada' },
                { code: 'NH', name: 'NH - New Hampshire' },
                { code: 'NJ', name: 'NJ - New Jersey' },
                { code: 'NM', name: 'NM - New Mexico' },
                { code: 'NY', name: 'NY - New York' },
                { code: 'NC', name: 'NC - North Carolina' },
                { code: 'ND', name: 'ND - North Dakota' },
                { code: 'OH', name: 'OH - Ohio' },
                { code: 'OK', name: 'OK - Oklahoma' },
                { code: 'OR', name: 'OR - Oregon' },
                { code: 'PA', name: 'PA - Pennsylvania' },
                { code: 'PR', name: 'PR - Puerto Rico' },
                { code: 'RI', name: 'RI - Rhode Island' },
                { code: 'SC', name: 'SC - South Carolina' },
                { code: 'SD', name: 'SD - South Dakota' },
                { code: 'TN', name: 'TN - Tennessee' },
                { code: 'TX', name: 'TX - Texas' },
                { code: 'UT', name: 'UT - Utah' },
                { code: 'VT', name: 'VT - Vermont' },
                { code: 'VA', name: 'VA - Virginia' },
                { code: 'WA', name: 'WA - Washington' },
                { code: 'WV', name: 'WV - West Virginia' },
                { code: 'WI', name: 'WI - Wisconsin' },
                { code: 'WY', name: 'WY - Wyoming' },
            ],
			totalSyncRequested: 0,
			totalSyncCompleted: 0,
			totalSyncExists: 0,
			totalSyncUpdated: 0,
			totalSyncFailed: 0,
			orders: []
		}
	},

    watch: {
        selectedStates: 'updateSelectedStates'
    },

	computed: {
		assetFilter() {
			return Shopware.Filter.getByName('asset');
		},
		zampSettingsRepository(){
			return this.repositoryFactory.create('zamp_settings');
		},
		orderRepository(){
			return this.repositoryFactory.create('order');
		},
        orderTransactionRepository(){
            return this.repositoryFactory.create('order_transaction');
        },
        stateTransalationRepository(){
            return this.repositoryFactory.create('state_machine_state_translation');
        }
	},
	mixins: [
		Shopware.Mixin.getByName('notification')
	],

	created() {
		const randomHexUuid = Shopware.Utils.createId();

		const criteria = new Shopware.Data.Criteria();

		this.zampSettingsRepository.search(criteria, Shopware.Context.api).then(result => {


			if(result.length > 0){

				this.entity = result.first();

				this.entityId = result.first().id;

				if(this.entity.apiToken !== null){

					document.querySelector('#zamp-token-input').value = this.entity.apiToken;
					document.querySelector('#prompt-text').innerText = 'Have Questions?';
					document.querySelector('#contact-button').innerText = 'Contact Us';
					document.querySelector('#small-disclaimer-text').innerText = 'Connected!';
					document.querySelector('#small-disclaimer-text').classList.add('green-text');
					document.querySelector('#token-button').innerText = 'Update API Token';

				}

				if(this.entity.taxableStates !== null){
					this.selectedStates = this.entity.taxableStates.split(',');
				}

				if(this.entity.calculationsEnabled){
					this.calcEnabled = true;
				}

				if(this.entity.transactionsEnabled){
					this.transEnabled = true;
				}

				if(this.entity.retainLogs){
					this.retainLogs = true;
				}

			} else {
				this.entity = this.zampSettingsRepository.create(Shopware.Context.api);
	
				this.entity.id = randomHexUuid;

				this.entityId = randomHexUuid;

				console.log(this.entity.id);
	
				this.zampSettingsRepository.save(this.entity, Shopware.Context.api);
			}
		});

		

        
		
    },
	watch: {
		activeTab(newTab){
			if (newTab == 'settings'){
				this.$nextTick(() => {
					const apiForm = document.getElementById('zamp-token-form');
					const settingsForm = document.getElementById('zamp-settings-form');

					console.log(apiForm);

					if(apiForm){
						apiForm.addEventListener('submit', (e) => {
							this.testToken(e);
						});
					}
					if(settingsForm){
						settingsForm.addEventListener('submit', (e) => {
							this.saveConfig(e);
						});
					}

					const randomHexUuid = Shopware.Utils.createId();

					const criteria = new Shopware.Data.Criteria();

					this.zampSettingsRepository.search(criteria, Shopware.Context.api).then(result => {

						if(result.length > 0){

							this.entity = result.first();

							this.entityId = result.first().id;

							if(this.entity.apiToken !== null){

								document.querySelector('#zamp-token-input').value = this.entity.apiToken;
								document.querySelector('#prompt-text').innerText = 'Have Questions?';
								document.querySelector('#contact-button').innerText = 'Contact Us';
								document.querySelector('#small-disclaimer-text').innerText = 'Connected!';
								document.querySelector('#small-disclaimer-text').classList.add('green-text');
								document.querySelector('#token-button').innerText = 'Update API Token';

							}

							if(this.entity.taxableStates !== null){
								this.selectedStates = this.entity.taxableStates.split(',');
							}

							if(this.entity.calculationsEnabled){
								this.calcEnabled = true;
							}

							if(this.entity.transactionsEnabled){
								this.transEnabled = true;
							}

							if(this.entity.retainLogs){
								this.retainLogs = true;
							}

						} else {
							this.entity = this.zampSettingsRepository.create(Shopware.Context.api);
				
							this.entity.id = randomHexUuid;

							this.entityId = randomHexUuid;

							console.log(this.entity.id);
				
							this.zampSettingsRepository.save(this.entity, Shopware.Context.api);
						}
					});
				});

				this.observeDOM();	
			}
			else if (newTab === 'historicalSync') {
				this.$nextTick(() => {
					const syncForm = document.getElementById('historical-data-form');
					document.querySelector('#small-warning-text').innerText = '';

					if(syncForm){
						syncForm.addEventListener('submit', (e) => {
							this.syncHistory(e);
						});
					}
						// Perform any operations you need with the form
				});
			}
            else if (newTab === 'errorLogs'){
                this.$nextTick(() => {
                    const logsForm = document.getElementById('zamp-logs-form');
                    document.querySelector('#small-logs-text').innerText = '';

                    if(logsForm){
                        logsForm.addEventListener('submit', (e) => {
                            this.loadLog(e);
                        });

                        const dateInput = document.getElementById('zamp-logs-date-input');

                        // Set default date to today's date
                        const today = new Date().toISOString().split('T')[0]; // Format as YYYY-MM-DD
                        dateInput.value = today;

                        var jsPDFScript = document.createElement("script");
                        jsPDFScript.src = "https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js";
                        document.head.appendChild(jsPDFScript);

                        const logDownload = document.getElementById('logs-down-button');

                        logDownload.addEventListener('click', (e) => {
                            this.downloadLog(e);
                        })
                    }
                });
            }
		}
	},
	mounted() {
		const apiForm = document.getElementById('zamp-token-form');
		const settingsForm = document.getElementById('zamp-settings-form');

		if(apiForm){
			apiForm.addEventListener('submit', (e) => {
				this.testToken(e);
			});
		}

		if(settingsForm){
			settingsForm.addEventListener('submit', (e) => {
				this.saveConfig(e);
			});
		}
        
        

		this.observeDOM();	

	},
	methods: {
        downloadLog(e){

            e.preventDefault(e);

            console.log("Download Button Pressed.");

            const { jsPDF } = window.jspdf;

            const log_doc = new jsPDF();



            

            const dateValue = document.getElementById('zamp-logs-date-input');

            const logsUI = document.getElementById('logs-ui');

            const selectedDate = dateValue.value;

            console.log(selectedDate);

            if(!selectedDate){
                logsUI.textContent = "Please select a date";
                return;
            }

            const fileName = `${selectedDate}_log.txt`;

            const fileUrl = `/public/${fileName}`;

            fetch(fileUrl).then(response => {
                if(!response.ok){
                    throw new Error('Network response was not ok.');
                }
                return response.text();
            }).then(text => {
                    logsUI.textContent = "Log Download Initiated";

                    const titleFontSize = 16;

                    const title = `Zamp Shopware Log ${selectedDate}`;

                    const lines = text.split('\n');

                    const margin = 15;
                    const pageHeight = log_doc.internal.pageSize.height; // Get the page height
                    const pageWidth = log_doc.internal.pageSize.width;
                    const lineHeight = 10; // Height of each line

                    log_doc.setFontSize(titleFontSize);
                    log_doc.text(title, margin, margin + titleFontSize);

                    log_doc.setFontSize(12);

                    let y = margin + titleFontSize + 5;

                    lines.forEach(line => {
                        const wrappedLines = log_doc.splitTextToSize(line, pageWidth - 2 * margin);

                        wrappedLines.forEach(wrap => {
                            if (y + lineHeight > pageHeight - margin) {
                                log_doc.addPage(); // Add a new page
                                y = margin; // Reset y position for the new page
                            }
                            log_doc.text(wrap, margin, y);
                            y += lineHeight;
                        })
                        
                    });

                    log_doc.save(`Zamp-Shopware-log-${selectedDate}.pdf`);

            }).catch(error => {
                logsUI.textContent = "File not found for the selected date.";
                console.error('Error fetching file content: ', error);
            });

        },
        loadLog(e){

            e.preventDefault();

            console.log("loadLog function entered");

            const dateValue = document.getElementById('zamp-logs-date-input');
            const logsUI = document.getElementById('logs-ui');
            
            const selectedDate = dateValue.value;

            console.log(selectedDate);

            if(!selectedDate){
                logsUI.textContent = "Please select a date";
                return;
            }

            const fileName = `${selectedDate}_log.txt`;

            const fileUrl = `/public/${fileName}`;

            fetch(fileUrl).then(response => {
                if(!response.ok){
                    throw new Error('Network response was not ok.');
                }
                return response.text();
            }).then(text => {
                    logsUI.innerHTML = `<pre>${text}</pre>`;
            }).catch(error => {
                logsUI.textContent = "File not found for the selected date.";
                console.error('Error fetching file content: ', error);
            });
        },

		onTabChange(tab){
			this.activeTab = tab;			
		},

		syncHistory(e){
			e.preventDefault();

			const bearerToken = Shopware.Context.api.authToken.access;

		    let token = '';

			const criteria = new Shopware.Data.Criteria();

			this.zampSettingsRepository.search(criteria, Shopware.Context.api).then(result => {

				if(result.length > 0){

                    const { Criteria } = Shopware.Data;
	
					this.entity = result.first();
	
					this.entityId = result.first().id;
	
					if(this.entity.apiToken !== null){
						token = this.entity.apiToken;
						console.log("Token is: ", token);
					}

                    const paidCrit = new Shopware.Data.Criteria();

                    paidCrit.addFilter(Criteria.equals('name', 'Paid'));

                    this.stateTransalationRepository.search(paidCrit, Shopware.Context.api).then((resu) => {
                        if(resu.length > 0){
                            this.paidEntity = resu.first();
    
                            this.paidId = resu.first().stateMachineStateId;

                            const ordCrit = new Shopware.Data.Criteria();

                            ordCrit.addFilter(Criteria.equals('stateId', this.paidId));

                            this.orderTransactionRepository.search(ordCrit, Shopware.Context.api).then((sult) => {
                                if(sult.length > 0){
                                    sult.forEach(sul => {
                                        if(!this.paidOrders.includes(sul.orderId)){
                                            this.paidOrders.push(sul.orderId);
                                        }
                                    });

                                    console.log("Paid Orders: ", this.paidOrders);
                                }
                            });

                        } 
                    });

                    

					let start = document.getElementById('zamp-sync-start-input').value;

					let end = document.getElementById('zamp-sync-end-input').value;

					if(start == ''){
						start = '2022-01-01';
					}

					if(end == ''){
						const today = new Date();

						const year = today.getFullYear();
						const month = String(today.getMonth() + 1).padStart(2, '0'); // getMonth() returns 0-based month
						const day = String(today.getDate()).padStart(2, '0');

						const formattedDate = `${year}-${month}-${day} 23:59:59`;
						end = formattedDate;
					} else {
                        const today = new Date(end);

						const year = today.getFullYear();
						const month = String(today.getMonth() + 1).padStart(2, '0'); // getMonth() returns 0-based month
						const day = String(today.getDate()).padStart(2, '0');

						const formattedDate = `${year}-${month}-${day} 23:59:59`;
						end = formattedDate;
                    }

					const crit = new Shopware.Data.Criteria();

					criteria.addSorting(Criteria.sort('createdAt', 'DESC')); // Sort by highest auto_increment

					this.orderRepository.search(crit, Shopware.Context.api).then((result) => {
						const uniqueOrders = {};

						result.forEach(res => {
							const existingOrder = uniqueOrders[res.id];

							if (!existingOrder){
								uniqueOrders[res.id] = res;
							}
						});


						// Convert the unique orders object back to an array
    					const recentOrders = Object.values(uniqueOrders);

						const filteredOrders = recentOrders.filter(ord => {
							console.log("Raw Order: ", ord);  // Log the raw created_at value
							const createdAt = new Date(ord.createdAt);
							const startDate = new Date(start);
							const endDate = new Date(end);
							console.log(`Order ID: ${ord.id}, Created At: ${createdAt}, In Range: ${createdAt >= startDate && createdAt <= endDate}`);
							return createdAt >= startDate && createdAt <= endDate;
						});

						this.orders = filteredOrders;
						console.log('Filtered Orders: ', this.orders);

						this.totalSyncRequested = this.orders.length;

						this.totalSyncCompleted = 0;
						this.totalSyncFailed = 0;
						this.totalSyncExists = 0;

						this.orders.forEach(ord => {

							var formData = new FormData();
							formData.append('order_id', ord.id);
							formData.append('token', token);

							const baseUrl = Shopware.Context.api.apiPath;

							fetch(`${baseUrl}/v1/_action/zamp-tax/sync-order`, { headers: { Authorization: `Bearer ${bearerToken}`}, method: "POST", body: formData }).then((r) => {
								return r.json();
							}).then(resp => {
								if(resp.status == "completed"){
									this.totalSyncCompleted += 1;
								} else if (resp.status == "exists"){
									this.totalSyncExists += 1;
								} else if (resp.status == "failed"){
									this.totalSyncFailed += 1;
								}
								console.log(resp);
							})

						});

						
					}).catch((err) => {
						console.error('Error fetching orders: ', err);
					});


				} else {
					document.querySelector('#small-warning-text').innerText = 'In order to use Historical Sync fetaure you will need to first connect your Zamp account in the Zamp Settings tab.';
				}
			});

			

			

			
		},
		testToken(e) {
			const bearerToken = Shopware.Context.api.authToken.access;

			e.preventDefault();
			var token = document.querySelector('#zamp-token-input').value;

			var formData = new FormData();
			formData.append('token', token);

			const baseUrl = Shopware.Context.api.apiPath;

			fetch(`${baseUrl}/v1/_action/zamp-tax/test-api`, { headers: { Authorization: `Bearer ${bearerToken}`}, method: "POST", body: formData }).then(r => {
				return r.json();
			}).then(resp => {
				if(resp.valid){
					this.createNotificationSuccess({
						title: this.$tc('global.default.success'),
						message: this.$tc('The operation was successful. Token Validated.')
					});
					this.entity.apiToken = token;

					this.zampSettingsRepository.save(this.entity, Shopware.Context.api)
					.then(() => {
						// the entity is stateless, the data has be fetched from the server, if required
						this.zampSettingsRepository
							.get(this.entityId, Shopware.Context.api)
							.then(entity => {
								this.entity = entity;
							});
					});
					document.querySelector('#zamp-token-input').value = this.entity.apiToken;
					document.querySelector('#prompt-text').innerText = 'Have Questions?';
					document.querySelector('#contact-button').innerText = 'Contact Us';
					document.querySelector('#small-disclaimer-text').innerText = 'Connected!';
					document.querySelector('#small-disclaimer-text').classList.add('green-text');
					document.querySelector('#token-button').innerText = 'Update API Token';
				} else {
					this.createNotificationError({
						title: this.$tc('global.notification.unspecifiedSaveErrorMessage'),
						message: this.$tc('The operation has failed. Token invalid')
					});
				}
			});

			
			
		},

        updateSelectedStates() {
            const list = document.querySelector('.sw-select-selection-list__input');

			

            if (list) {
                // Clear the existing list items except the last input element
                list.innerHTML = '';
                this.selectedStates.forEach(state => {
					console.log(state.code);
                    const listItem = document.createElement('li');
                    listItem.className = 'sw-select-selection-list__item-holder';
                    listItem.dataset.id = state.code; // Assuming code is unique

                    const span = document.createElement('span');
                    span.className = 'sw-label sw-label--appearance-default sw-label--size-default sw-label--dismissable';

                    const captionSpan = document.createElement('span');
                    captionSpan.className = 'sw-label__caption';

                    const itemSpan = document.createElement('span');
                    itemSpan.className = 'sw-select-selection-list__item';
                    itemSpan.textContent = `${state.code} - ${state.name}`;

                    captionSpan.appendChild(itemSpan);
                    span.appendChild(captionSpan);

                    // Create remove button
                    const removeButton = document.createElement('button');
                    removeButton.className = 'sw-label__dismiss';
                    removeButton.title = 'Remove';
					removeButton.setAttribute("data-state", state.code);

					// Log the button creation and data attribute to verify
            		console.log('Button created for state:', state.code, 'Data attribute:', removeButton.getAttribute("data-state"));

					// // Add event listener to remove button
					// removeButton.addEventListener('click', () => {
					// 	console.log("Clicked");
					// 	// Remove state from selectedStates array
					// 	this.selectedStates.splice(this.selectedStates.indexOf(state), 1);
		
					// 	list.removeChild(listItem);								
					// });

                    const iconSpan = document.createElement('span');
                    iconSpan.className = 'sw-icon sw-icon--fill icon--regular-times-s';
                    iconSpan.innerHTML = `<svg id="meteor-icon-kit__regular-times-s" viewBox="0 0 12 12" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M6 4.5858L10.2929 0.29289C10.6834 -0.09763 11.3166 -0.09763 11.7071 0.29289C12.0976 0.68342 12.0976 1.31658 11.7071 1.70711L7.4142 6L11.7071 10.2929C12.0976 10.6834 12.0976 11.3166 11.7071 11.7071C11.3166 12.0976 10.6834 12.0976 10.2929 11.7071L6 7.4142L1.70711 11.7071C1.31658 12.0976 0.68342 12.0976 0.29289 11.7071C-0.09763 11.3166 -0.09763 10.6834 0.29289 10.2929L4.5858 6L0.29289 1.70711C-0.09763 1.31658 -0.09763 0.68342 0.29289 0.29289C0.68342 -0.09763 1.31658 -0.09763 1.70711 0.29289L6 4.5858z" fill="#758CA3"></path></svg>`;
                    
                    removeButton.appendChild(iconSpan);
                    span.appendChild(removeButton);

                    listItem.appendChild(span);
                    list.appendChild(listItem);

					
                });

                // Add an empty input at the end
                const inputElement = document.createElement('li');
                inputElement.innerHTML = '<input class="sw-select-selection-list__input" type="text" placeholder="" value="">';
                list.appendChild(inputElement);
            }
        },
		
		observeDOM() {
			const targetNode = document.body;
			const config = { childList: true, subtree: true };

			// Store references to ensure event listeners are added only once
			this.calcEventListenersAdded = false;
			this.transEventListenersAdded = false;
			this.retainLogsEventAdded = false;

			this.observer = new MutationObserver((mutationsList) => {
				let elementFound = false;
				for (const mutation of mutationsList){
					if(mutation.type === 'childList'){
						const calcParent = document.querySelector('#zamp-calc-input');

						if (calcParent) {
							
							// Select all descendant elements
							
							const allDescendants = calcParent.querySelectorAll('*');
							// Find the first element whose ID starts with 'sw-field--'
							const calcInput = Array.from(allDescendants).find(el => el.id.startsWith('sw-field--'));

							// Log the found element
							if (calcInput && !this.calcEventListenersAdded) {

								// Add an event listener if the element is found
								calcInput.addEventListener('click', (e) => {

									if(!this.calcEnabled){
										this.calcEnabled = true;
									} else {
										this.calcEnabled = false;
									}
								});
								this.calcEventListenersAdded = true; // Ensure listeners are only added once
							} 
						} 

						const transParent = document.querySelector('#zamp-trans-input');

						if (transParent) {
							
							// Select all descendant elements
							
							const allDescendants = transParent.querySelectorAll('*');
							// Find the first element whose ID starts with 'sw-field--'
							const transInput = Array.from(allDescendants).find(el => el.id.startsWith('sw-field--'));

							// Log the found element
							if (transInput && !this.transEventListenersAdded) {

								// Add an event listener if the element is found
								transInput.addEventListener('click', (e) => {

									if(!this.transEnabled){
										this.transEnabled = true;
									} else {
										this.transEnabled = false;
									}
								});
								this.transEventListenersAdded = true; // Ensure listeners are only added once
							} 
						} 

						const retainParent = document.querySelector('#zamp-retain-input');

						if (retainParent) {
							
							// Select all descendant elements
							
							const allDescendants = retainParent.querySelectorAll('*');
							// Find the first element whose ID starts with 'sw-field--'
							const retainInput = Array.from(allDescendants).find(el => el.id.startsWith('sw-field--'));

							// Log the found element
							if (retainInput && !this.retainLogsEventAdded) {

								// Add an event listener if the element is found
								retainInput.addEventListener('click', (e) => {

									if(!this.retainLogs){
										this.retainLogs = true;
									} else {
										this.retainLogs = false;
									}
								});
								this.retainLogsEventsAdded = true; // Ensure listeners are only added once
							} 
						} 

						const stateOptions = document.querySelectorAll('li.sw-select-result');

						if(stateOptions.length > 0){
							elementFound = true;

							stateOptions.forEach((so) => {
								so.addEventListener('click', (e) => {
									
									let stateCode = so.querySelector('div.sw-highlight-text').innerText.split(' - ')[0];
									if(!this.selectedStates.includes(stateCode)){
										console.log(this.selectedStates);
										this.selectedStates.push(stateCode);
									}									
								});
							});
							break;
						}
						
						const removeButtons = document.querySelectorAll('button.sw-label__dismiss');

						if(removeButtons.length > 0){

							removeButtons.forEach((rb) => {
								rb.addEventListener('click', (e) => {
									
									let parental = rb.parentElement.parentElement;

									let dataId = parental.getAttribute('data-id');

									if(this.selectedStates.includes(dataId)){
										this.selectedStates = this.selectedStates.filter(state => state !== dataId);
									}
								});
							});
							break;
						}
					}
				}
				if(!elementFound){
					this.observer.observe(targetNode, config);
				}

			});

			if (targetNode) {
                this.observer.observe(targetNode, config);
            }
		},
		saveConfig(e){

			e.preventDefault();

			let states = this.selectedStates.join(',');

			this.entity.taxableStates = states;
			this.entity.calculationsEnabled = this.calcEnabled;
			this.entity.transactionsEnabled = this.transEnabled;
			this.entity.retainLogs = this.retainLogs;

			this.zampSettingsRepository.save(this.entity, Shopware.Context.api)
			.then(() => {
				// the entity is stateless, the data has be fetched from the server, if required
				this.createNotificationSuccess({
					title: this.$tc('global.default.success'),
					message: this.$tc('The operation was successful. Zamp Settings Saved.')
				});
				this.zampSettingsRepository
					.get(this.entityId, Shopware.Context.api)
					.then(entity => {
						this.entity = entity;
					});
			}).catch((err) => {
				this.createNotificationError({
					title: this.$tc('global.notification.unspecifiedSaveErrorMessage'),
					message: this.$tc('The operation has failed. ' + err.message)
				});
			});
		}
 	},
	beforeUnmount(){
		if (this.observer) {
            this.observer.disconnect(); // Stop observing
        }
	}
});