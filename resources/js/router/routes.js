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
		path: '/:pathMatch(.*)*',
		redirect: '/',
		name: 'pathMatch',
		meta: {
			title: 'all',
		}
	}
];