export default [
	{
		path: '/',
		name:'home',
		component: () => import('../pages/HomePage.vue'),
		meta: {
			guard: 'auth',
		},
	},
	{
		path: '/login',
		name: 'login',
		meta: {
			guard: 'guest',
		},
		component: () => import('../pages/LoginPage.vue'),
	},
	{
		path: '/factory',
		name:'factory',
		component: () => import('../pages/FactoriesPage.vue'),
		meta: {
			guard: 'auth',
		},
	},
	{
		path: '/structure',
		name:'structure',
		component: () => import('../pages/FactoriesStructurePage.vue'),
		meta: {
			guard: 'auth',
		},
	},
	{
		path: '/blogs',
		name:'blogs',
		component: () => import('../pages/BlogsPage.vue'),
		meta: {
			guard: 'auth',
		},
	},
	{
		path: '/units',
		name:'units',
		component: () => import('../pages/UnitsPage.vue'),
		meta: {
			guard: 'auth',
		},
	},
	{
		path: '/graphics',
		name:'graphics',
		component: () => import('../pages/GraphicsPage.vue'),
		meta: {
			guard: 'auth',
		},
	},
	{
		path: '/graphictimes',
		name:'graphictimes',
		component: () => import('../pages/GraphicTimesPage.vue'),
		meta: {
			guard: 'auth',
		},
	},
	{
		path: '/paramgraphics',
		name:'paramgraphics',
		component: () => import('../pages/ParameterGraphics.vue'),
		meta: {
			guard: 'auth',
		},
	},
	{
		path: '/paramtypes',
		name:'paramtypes',
		component: () => import('../pages/ParametersTypesPage.vue'),
		meta: {
			guard: 'auth',
		},
	},
	{
		path: '/params',
		name:'params',
		component: () => import('../pages/ParametersPage.vue'),
		meta: {
			guard: 'auth',
		},
	},
	{
		path: '/sources',
		name:'sources',
		component: () => import('../pages/SourcesPage.vue'),
		meta: {
			guard: 'auth',
		},
	},
	{
		path: '/changes',
		name:'changes',
		component: () => import('../pages/ChangesPage.vue'),
		meta: {
			guard: 'auth',
		},
	},
	{
		path: '/vparams',
		name:'vparams',
		component: () => import('../pages/ParametrValue.vue'),
		meta: {
			guard: 'auth',
		},
	},
	{
		path: '/vparamsget',
		name:'vparamsget',
		component: () => import('../pages/ParametrGetValue.vue'),
		meta: {
			guard: 'auth',
		},
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