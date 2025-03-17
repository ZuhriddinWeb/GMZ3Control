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
	// {
	// 	path: '/blogs',
	// 	name:'blogs',
	// 	component: () => import('../pages/BlogsPage.vue'),
	// 	meta: {
	// 		guard: 'auth',
	// 	},
	// },
	{
		path: '/blogs',
		name:'blogs',
		component: () => import('../pages/BlogCardPage.vue'),
		meta: {
			guard: 'auth',
		},
	},
	{
		path: '/blog/:id',
		name:'BlogDetail',
		component: () => import('../pages/BlogsPage.vue'),
		meta: {
			guard: 'auth',
		},
		props: true,
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
	// {
	// 	path: '/graphictimes',
	// 	name:'graphictimes',
	// 	component: () => import('../pages/GraphicTimesPage.vue'),
	// 	meta: {
	// 		guard: 'auth',
	// 	},
	// },
	{
		path: '/graphictimes',
		name:'graphictimes',
		component: () => import('../pages/TimeCardPage.vue'),
		meta: {
			guard: 'auth',
		},
	},
	{
		path: '/time/:id',
		name:'TimeDetail',
		component: () => import('../pages/GraphicTimesPage.vue'),
		meta: {
			guard: 'auth',
		},
		props: true,
	},
	{
		path: '/cardPage/:id',
		name:'CardDetailPage',
		component: () => import('../pages/ParameterGraphicsPage.vue'),
		meta: {
			guard: 'auth',
		},
		props: true,
	},
	{
		path: '/card/:id/:page',
		name: 'CardDetail',
		component: () => import('../pages/ParameterGraphics.vue'),
		meta: {
		  guard: 'auth',
		},
		props: route => ({
		  id: route.params.id,
		  page: route.params.page
		})
	  },
	  
	  
	{
		path: '/paramgraphics',
		name:'paramgraphics',
		component: () => import('../pages/GraphicCardsPage.vue'),
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
		path: '/formula',
		name:'formula',
		component: () => import('../pages/FormulaPage.vue'),
		meta: {
			guard: 'auth',
		},
	},
	// {
	// 	path: '/pages',
	// 	name:'pages',
	// 	component: () => import('../pages/NumberPage.vue'),
	// 	meta: {
	// 		guard: 'auth',
	// 	},
	// },
	{
		path: '/pages',
		name:'pages',
		component: () => import('../pages/NumberPageCard.vue'),
		meta: {
			guard: 'auth',
		},
	},
	{
		path: '/page/:id',
		name:'PageDetail',
		component: () => import('../pages/NumberPage.vue'),
		meta: {
			guard: 'auth',
		},
		props: true,
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
		path: '/vparam/:id',
		name:'vparam',
		component: () => import('../pages/ParametrValue.vue'),
		meta: {
			guard: 'auth',
		},
		props: true,
	},
	{
		path: '/vparams',
		name:'vparams',
		component: () => import('../pages/ParametrValueCard.vue'),
		meta: {
			guard: 'auth',
		},
	},
	{
		path: '/vparam/:id',
		name:'vparam',
		component: () => import('../pages/ParametrValue.vue'),
		meta: {
			guard: 'auth',
		},
		props: true,
	},
	{
		path: '/count',
		name:'createDoc',
		component: () => import('../pages/ParametrValueCard.vue'),
		meta: {
			guard: 'auth',
		},
	},
	{
		path: '/opercount',
		name:'createDoc',
		component: () => import('../pages/OperatorsCountInputValueCard.vue'),
		meta: {
			guard: 'auth',
		},
	},
	{
		path: '/documents',
		name:'documents',
		component: () => import('../pages/Documents.vue'),
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