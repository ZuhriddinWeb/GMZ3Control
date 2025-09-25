export default [
  {
    path: "/",
    name: "home",
    component: () => import("../pages/HomePage.vue"),
    meta: {
      guard: "auth",
    },
  },
  {
    path: "/login",
    name: "login",
    meta: {
      guard: "guest",
    },
    component: () => import("../pages/LoginPage.vue"),
  },
  {
    path: "/factory",
    name: "factory",
    component: () => import("../pages/FactoriesPage.vue"),
    meta: {
      guard: "auth",
    },
  },
  {
    path: "/structure",
    name: "structure",
    component: () => import("../pages/FactoriesStructurePage.vue"),
    meta: {
      guard: "auth",
    },
  },
  {
    path: "/cardPageStatic",
    name: "CardDetailPageStatic",
    component: () => import("../pages/svodka/staticscards/StaticPageCard.vue"),
    meta: {
      guard: "auth",
    },
    props: true,
  },
  {
  path: "/cardPageStatic/:page/groups",
  name: "CardGroupsPageStatic",
  component: () => import("../pages/svodka/staticscards/GroupsCards.vue"),
  meta: { guard: "auth" },
  props: r => ({ page: Number(r.params.page) }),
},
  // {
  //   path: "/static/:id",
  //   name: "static",
  //   component: () => import("../pages/StaticParametrs.vue"),
  //   meta: {
  //     guard: "auth",
  //   },
  //   props: true
  // },
  {
  path: "/static/:id/:groupId",
  name: "staticByGroup",
  component: () => import("../pages/StaticParametrs.vue"),
  meta: { guard: "auth" },
  props: r => ({ id: Number(r.params.id), groupId: Number(r.params.groupId) }),
},
// {
//   path: "/static/:id/group/:groupId",
//   name: "staticByGroup",
//   component: () => import("../pages/StaticParametrs.vue"),
//   meta: { guard: "auth" },
//   props: r => ({
//     id: Number(r.params.id),
//     groupId: Number(r.params.groupId),
//   }),
// },
{
  path: "/static/:id/group/:groupId?",
  name: "static",
  component: () => import("../pages/StaticParametrs.vue"),
  meta: { guard: "auth" },
  props: r => ({
    id: Number(r.params.id),
    groupId: r.params.groupId != null ? Number(r.params.groupId) : null,
  }),
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
    path: "/blogs",
    name: "blogs",
    component: () => import("../pages/BlogCardPage.vue"),
    meta: {
      guard: "auth",
    },
  },
  {
    path: "/blog/:id",
    name: "BlogDetail",
    component: () => import("../pages/BlogsPage.vue"),
    meta: {
      guard: "auth",
    },
    props: true,
  },
  {
    path: "/units",
    name: "units",
    component: () => import("../pages/UnitsPage.vue"),
    meta: {
      guard: "auth",
    },
  },
  {
    path: "/graphics",
    name: "graphics",
    component: () => import("../pages/GraphicsPage.vue"),
    meta: {
      guard: "auth",
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
    path: "/graphictimes",
    name: "graphictimes",
    component: () => import("../pages/TimeCardPage.vue"),
    meta: {
      guard: "auth",
    },
  },
  {
    path: "/time/:id",
    name: "TimeDetail",
    component: () => import("../pages/GraphicTimesPage.vue"),
    meta: {
      guard: "auth",
    },
    props: true,
  },

  {
    path: "/graphicterms",
    name: "graphicterms",
    component: () => import("../pages/TermsCardPage.vue"),
    meta: {
      guard: "auth",
    },
  },
  {
    path: "/terms/:id",
    name: "TermsDetail",
    component: () => import("../pages/GraphicTermsPage.vue"),
    meta: {
      guard: "auth",
    },
    props: true,
  },

  {
    path: "/cardPage/:id",
    name: "CardDetailPage",
    component: () => import("../pages/ParameterGraphicsPage.vue"),
    meta: {
      guard: "auth",
    },
    props: true,
  },

  {
    path: "/card/:id/:page",
    name: "CardDetail",
    component: () => import("../pages/ParameterGraphics.vue"),
    meta: {
      guard: "auth",
    },
    props: (route) => ({
      id: route.params.id,
      page: route.params.page,
    }),
  },

  {
    path: "/paramgraphics",
    name: "paramgraphics",
    component: () => import("../pages/GraphicCardsPage.vue"),
    meta: {
      guard: "auth",
    },
  },

  {
    path: "/groupPage/:id",
    name: "GroupDetailPage",
    component: () => import("../pages/GroupPagesCard.vue"),
    meta: {
      guard: "auth",
    },
    props: true,
  },
  {
    path: "/group/:id/:page",
    name: "GroupDetail",
    component: () => import("../pages/GroupsPage.vue"),
    meta: {
      guard: "auth",
    },
    props: (route) => ({
      id: route.params.id,
      page: route.params.page,
    }),
  },

  {
    path: "/group",
    name: "groupcard",
    component: () => import("../pages/GroupCardPage.vue"),
    meta: {
      guard: "auth",
    },
  },

  {
    path: "/paramtypes",
    name: "paramtypes",
    component: () => import("../pages/ParametersTypesPage.vue"),
    meta: {
      guard: "auth",
    },
  },
  {
    path: "/params",
    name: "params",
    component: () => import("../pages/ParametersPage.vue"),
    meta: {
      guard: "auth",
    },
  },
  {
    path: "/formula",
    name: "formula",
    component: () => import("../pages/FormulaPage.vue"),
    meta: {
      guard: "auth",
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
    path: "/pages",
    name: "pages",
    component: () => import("../pages/NumberPageCard.vue"),
    meta: {
      guard: "auth",
    },
  },
  {
    path: "/page/:id",
    name: "PageDetail",
    component: () => import("../pages/NumberPage.vue"),
    meta: {
      guard: "auth",
    },
    props: true,
  },
  {
    path: "/sources",
    name: "sources",
    component: () => import("../pages/SourcesPage.vue"),
    meta: {
      guard: "auth",
    },
  },
  {
    path: "/changes",
    name: "changes",
    component: () => import("../pages/ChangesPage.vue"),
    meta: {
      guard: "auth",
    },
  },
  // {
  // 	path: '/vparam/:id',
  // 	name:'vparam',
  // 	component: () => import('../pages/ParametrValue.vue'),
  // 	meta: {
  // 		guard: 'auth',
  // 	},
  // 	props: true,
  // },
  {
    path: "/vparams",
    name: "vparams",
    component: () => import("../pages/ParametrValueCardVertical.vue"),
    meta: {
      guard: "auth",
    },
  },
  {
    path: "/vparam/:id",
    name: "vparam",
    component: () => import("../pages/ParametrValueVertical.vue"),
    meta: {
      guard: "auth",
    },
    props: true,
  },

  {
    path: "/vparamsHorizontal",
    name: "vparamsHorizontal",
    component: () => import("../pages/ParametrValueCardHorizontal.vue"),
    meta: {
      guard: "auth",
    },
  },
  {
    path: "/vparamsHorizontal/:id",
    name: "vparamHorizontal",
    component: () => import("../pages/ParametrValueHorizontal.vue"),
    meta: {
      guard: "auth",
    },
    props: true,
  },
  {
    path: "/count",
    name: "createDoc",
    component: () => import("../pages/ParametrValueCardVertical.vue"),
    meta: {
      guard: "auth",
    },
  },
  {
    path: "/opercount",
    name: "createDoc",
    component: () => import("../pages/OperatorsCountInputValueCard.vue"),
    meta: {
      guard: "auth",
    },
  },
  {
    path: "/operDetail/:id",
    name: "OperatorDetail",
    component: () => import("../pages/OperatorResultPage.vue"),
    meta: {
      guard: "auth",
    },
    props: true,
  },
  {
    path: "/documents",
    name: "documents",
    component: () => import("../pages/Documents.vue"),
    meta: {
      guard: "auth",
    },
  },
  {
    path: "/vparamsget",
    name: "vparamsget",
    component: () => import("../pages/ParametrGetValue.vue"),
    meta: {
      guard: "auth",
    },
  },
  {
    path: "/gmz3",
    name: "gmz3-report",
    component: () => import("../pages/svodka/GMZ3Report.vue"),
    meta: {
      guard: "auth",
    },
  },
  {
    path: "/users",
    name: "users",
    component: () => import("../pages/UsersPage.vue"),
  },
  {
    path: "/:pathMatch(.*)*",
    redirect: "/",
    name: "pathMatch",
    meta: {
      title: "all",
    },
  },
];
