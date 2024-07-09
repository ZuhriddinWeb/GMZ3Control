export default [
	{
		path: '/',
		name:'home',
		component: () => import('../pages/HomePage.vue'),
	},
	{
		path: '/units',
		name:'units',
		component: () => import('../pages/UnitsPage.vue'),
	},
	{
		path: '/graphics',
		name:'graphics',
		component: () => import('../pages/GraphicsPage.vue'),
	},
	{
		path: '/graphictimes',
		name:'graphictimes',
		component: () => import('../pages/GraphicTimesPage.vue'),
	},
	{
		path: '/paramtypes',
		name:'paramtypes',
		component: () => import('../pages/ParametersTypesPage.vue'),
	},
	{
		path: '/params',
		name:'params',
		component: () => import('../pages/ParametersPage.vue'),
	},
	{
		path: '/sources',
		name:'sources',
		component: () => import('../pages/SourcesPage.vue'),
	},
	{
		path: '/changes',
		name:'changes',
		component: () => import('../pages/ChangesPage.vue'),
	},
	{
		path: '/:pathMatch(.*)*',
		redirect: '/',
		name: 'pathMatch',
		meta: {
			title: 'all',
		}
	}
];