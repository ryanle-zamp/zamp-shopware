import './module/zamp-tax';

const { Component, Mixin } = Shopware;
import template from './view/sw-customer-group-tax-exempt.html.twig';
import template2 from './view/sw-product-detail-zamp-tax-code.html.twig';

Component.override('sw-settings-customer-group-detail', {
    template,

    computed: {
        customerGroup() {
            return this.$super('customerGroup');
        },

        customerGroupRepository() {
            return this.repositoryFactory.create('customer_group');
        }
    },
    watch: {
        customerGroup(newValue){
            if(newValue.customFields && newValue.customFields.tax_exempt_code){
                this.customerGroup.taxExemptCode = newValue.customFields.tax_exempt_code
            }
            
        }
    },

    methods: {
		onSave() {
			this.$super('onSave');

			let customFields = this.customerGroup.customFields || {};

			customFields.tax_exempt_code = this.customerGroup.taxExemptCode;

			this.customerGroup.customFields = customFields;
	
			console.log('Customer Group Tax Exempt Code: ', this.customerGroup.taxExemptCode);
			var cgId = this.customerGroup.id;
	
			this.customerGroupRepository.save(this.customerGroup).then(() => {
				return this.customerGroupRepository.get(cgId, Shopware.Context.api);
			}).then(entity => {
				this.customerGroup = entity;
				console.log("Entity: ", JSON.stringify(this.customerGroup));
			}).catch(error => {
				console.error("Failed to save customer group: ", error);
				if (error.response && error.response.data) {
					console.error("Error response data: ", error.response.data);
				}
				this.createNotificationError({
					message: this.$tc('sw-settings.customerGroup.detail.saveError', 0)
				});
			});
		}
    }
});

Component.override('sw-product-detail-base', {
    template: template2,

    data: function() {
        return {
            zampProductTaxCode: '',
            zampProductId: '',
            zampEntityId: '',
			zampEntity: {},
        };
    },

    computed: {

        product() {
            return this.$super('product');
        },
        productRepository() {
            return this.repositoryFactory.create('product');
        },
        zampProductTaxCodeRepository() {
            return this.repositoryFactory.create('zamp_product_tax_code');
        }
    },

    watch: {
        product(val) {

            if(val){
                this.zampProductId = val.id;

                const { Criteria } = Shopware.Data;

                const randomHexUuid = Shopware.Utils.createId();

                const criteria = new Shopware.Data.Criteria();

                criteria.addFilter(Criteria.equals('productId', val.id));

                this.zampProductTaxCodeRepository.search(criteria, Shopware.Context.api).then(result => {
                    if(result.length > 0){
                        this.zampEntity = result.first();

                        this.zampEntityId = result.first().id;

                        if(this.zampEntity.productId !== null){
                            this.zampProductId = this.zampEntity.productId;
                        }

                        if(this.zampEntity.productTaxCode !== null){
                            this.zampProductTaxCode = this.zampEntity.productTaxCode;
                        }
                    } 
                });
            }           

        }
    },
    methods: {
        getTaxCode(){
            return this.zampProductTaxCode;
        }
    }
});

Component.override('sw-product-detail', {
    data() {
        return {
            productTaxCode: '',
            zampProductId: '',
            zampEntity: {}
        };
    },
    computed: {
        zampProductTaxCodeRepository() {
            return this.repositoryFactory.create('zamp_product_tax_code');
        },
        product() {
            return this.$super('product');
        },

    },
    methods: {
        onSave() {

            this.$super('onSave').then(() => {
                const zampProductId = this.product.id;

                console.log("Product ID: ", zampProductId);

                const zampProductTaxCode = document.querySelector('#zamp-product-tax-code-input .sw-block-field__block input').value;

                console.log("Zamp Product Tax Code: ", zampProductTaxCode);

                
                if (zampProductId && zampProductTaxCode) {

                    const criteria = new Shopware.Data.Criteria();

                    const { Criteria } = Shopware.Data;
                    criteria.addFilter(Criteria.equals('productId', zampProductId));

                    this.zampProductTaxCodeRepository.search(criteria, Shopware.Context.api)
                    .then(result => {
                        if(result.length > 0){
                            this.zampEntity = result.first();
                            const zptcId = this.zampEntity.id;
                            this.zampEntity.productTaxCode = zampProductTaxCode;

                            return this.zampProductTaxCodeRepository.save(this.zampEntity, Shopware.Context.api)
                                .then(() => {
                                    this.createNotificationSuccess({
                                        title: 'Success',
                                        message: 'Product updated successfully.',
                                    });
                                    return this.zampProductTaxCodeRepository.get(zptcId, Shopware.Context.api);
                                }).then((entity) => {
                                    this.zampEntity = entity;
                                });
                                
                        } else {
                            this.zampEntity = this.zampProductTaxCodeRepository.create(Shopware.Context.api);

                            const randomHexUuid = Shopware.Utils.createId();

                                this.zampEntity.id = randomHexUuid;
                                this.zampEntity.productId = zampProductId;
                                this.zampEntity.productTaxCode = zampProductTaxCode;

                            return this.zampProductTaxCodeRepository.save(this.zampEntity, Shopware.Context.api)
                                .then(() => {
                                    this.createNotificationSuccess({
                                        title: 'Success',
                                        message: 'Product saved successfully.',
                                    });
                                    return this.zampProductTaxCodeRepository.get(this.zampEntity.id, Shopware.Context.api);
                                }).then((entity) => {
                                    this.zampEntity = entity;
                                });
                        }
                    }) .catch(err => {
                        this.createNotificationError({
                            title: 'Error',
                            message: 'Failed to save or update Zamp Product Tax Code: ' + err.message,
                        });
                    });
                } else {
                    console.error('Product ID or Zamp Tax Code is missing.');
                }
            }).catch(error => {
                console.error('Failed to save product:', error);
                this.createNotificationError({
                    title: 'Error',
                    message: 'Failed to save product: ' + error.message,
                });
            });
        }
    }
});


