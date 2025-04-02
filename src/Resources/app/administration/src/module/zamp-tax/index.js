import './page/zamp-tax-main-page';

Shopware.Module.register('zamp-tax', {
	type: 'plugin',
	name: 'zamp-tax',
	title: 'Zamp Tax',
	description: 'Zamp Tax Plugin',
	color: '#5075d3',
	icon: 'default-action-settings',
	snippets: {
		'de-DE': require('./snippet/de-DE.json'),
		'en-GB': require('./snippet/en-GB.json'),
		'en-US': require('./snippet/en-GB.json'),
	},
	routes: {
		dashboard: {
			component: 'zamp-tax-main-page',
			path: 'dashboard',
			meta: {
                allow: ['user', 'admin'],
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