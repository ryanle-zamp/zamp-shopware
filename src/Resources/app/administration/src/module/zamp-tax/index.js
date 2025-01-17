import './page/zamp-tax-main-page';

Shopware.Module.register('zamp-tax', {
	type: 'plugin',
	name: 'zamp-tax',
	title: 'Zamp Tax',
	description: 'Zamp Tax Plugin',
	color: '#5075d3',
	icon: 'default-action-settings',
	routes: {
		dashboard: {
			component: 'zamp-tax-main-page',
			path: 'dashboard',
			meta: {
                allow: ['user', 'admin'],  // Explicitly allow all users
            },
		},
	},
	navigation: [{
		label: 'Zamp Tax',
		color: '#5075d3',
		path: 'zamp.tax.dashboard',
		icon: 'default-action-settings',
		parent: 'sw-extension',
		position: 100,
	}],
});