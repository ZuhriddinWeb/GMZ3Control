export default [
	{
		path: '/',
		name:'home',
		component: () => import('../pages/HomePage.vue'),
	},
	{
		path: '/factory',
		name:'factory',
		component: () => import('../pages/FactoriesPage.vue'),
	},
	{
		path: '/structure',
		name:'structure',
		component: () => import('../pages/FactoriesStructurePage.vue'),
	},
	{
		path: '/blogs',
		name:'blogs',
		component: () => import('../pages/BlogsPage.vue'),
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
		path: '/paramgraphics',
		name:'paramgraphics',
		component: () => import('../pages/ParameterGraphics.vue'),
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
		path: '/vparams',
		name:'vparams',
		component: () => import('../pages/ParametrValue.vue'),
	},
	{
		path: '/users',
		name:'users',
		component: () => import('../pages/UsersPage.vue'),
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