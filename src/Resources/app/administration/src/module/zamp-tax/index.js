import './page/zamp-tax-main-page';
import deDE from './snippet/de-DE';
import enGB from './snippet/en-GB';
import enUS from './snippet/en-US';

Shopware.Module.register('zamp-tax', {
	type: 'plugin',
	name: 'zamp-tax',
	title: 'Zamp Tax',
	description: 'Zamp Tax Plugin',
	color: '#5075d3',
	icon: 'default-action-settings',
	snippets: {
		'de-DE': deDE,
		'en-GB': enGB,
		'en-US': enUS,
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