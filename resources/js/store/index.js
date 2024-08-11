import { createStore } from 'vuex'
import { useRouter } from 'vue-router'

export default createStore({
    state() {
        return {
            user: null,
            selectedRowId:null,
            countInputedParams:null,
        }
    },
    mutations: {
        setUser(state, user) {
            state.user = user
        },
    },
    actions: {
        async login({ state, dispatch }, data) {
            const result = await axios.post('login', data)
            if (result.status == 299) return result.data
            else {
                localStorage.setItem('token', `${result.data.type} ${result.data.token}`) // local
                state.logined = null
                await dispatch('getUser')
                router.push({ name: 'vparams' })
            }
        },
        async register({ dispatch }, props) {
            const result = await axios.post('register', props)
            
            if (result.status == 299) return result.data
            if (result.status == 200) {
                dispatch('login', props)
            }
        },
        async logout({ commit }) {
            const data = await axios.get('logout')
            if (data.status == 200) {
                axios.defaults.headers.common['Authorization'] = null
                localStorage.removeItem('token')
                commit('setUser', null)
                router.push('/')
            }
        },

        async getUser({ commit }) {
            axios.defaults.headers.common['Authorization'] = localStorage.getItem('token')
            await axios.get('user').then((res) => {
                commit('setUser', res.data);
            }).catch(() => { console.clear() })
        }
    },
})
