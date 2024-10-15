import { createStore } from "vuex";
import { useRouter } from "vue-router";

export default createStore({
  state() {
    return {
      user: null,
      selectedRowId: null,
      countInputedParams: null,
      change:null,
      currentTime:null,
      userTabel:'',
      structureID:null,
      newvalue:null,

    };
  },
  mutations: {
    setUser(state, user) {
      state.user = user;
    },
  },
  actions: {
    async login({ state, dispatch }, data) {
      try {
        const result = await axios.post('login', data);
        if (result.status == 299) {
          return result.data; 
        } else {
          localStorage.setItem('token', `${result.data.type} ${result.data.token}`); 
          state.logined = null;
          await dispatch('getUser');
          return { success: true }; 
        }
      } catch (error) {
        console.error('Login action error:', error);
        return { success: false, message: 'Login failed' }; 
      }
    },

    async register({ dispatch }, props) {
      const result = await axios.post("register", props);

      if (result.status == 299) return result.data;
      if (result.status == 200) {
        dispatch("login", props);
      }
    },
    async logout({ commit }) {
      try {
        const data = await axios.get("logout");
        if (data.status === 200) {
          delete axios.defaults.headers.common["Authorization"];
          localStorage.removeItem("token");
          commit("setUser", null);
        }
      } catch (error) {
        console.error("Logout failed:", error);
      }
    },

    async getUser({ commit }) {
      axios.defaults.headers.common["Authorization"] = localStorage.getItem(
        "token"
      );
      await axios
        .get("user")
        .then((res) => {
          commit("setUser", res.data);
        })
        .catch(() => {
          console.clear();
        });
    },
  },
});
