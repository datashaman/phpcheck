import BootstrapVue from 'bootstrap-vue'
import FuzzySearch from 'fuzzy-search'
import Vue from 'vue'
import Vuex from 'vuex'

Vue.use(BootstrapVue)
Vue.use(Vuex)

require('../styles/app.scss')

import router from './router'

const functions = require('../data/functions.json')
const generators = new FuzzySearch(functions.generators, ['shortName', 'summary'])
const helpers = new FuzzySearch(functions.helpers, ['shortName', 'summary'])

const store = new Vuex.Store({
    state: {
        query: ''
    },
    getters: {
        generators: state => {
            return generators.search(state.query)
        },
        helpers: state => {
            return helpers.search(state.query)
        }
    },
    mutations: {
        filter (state, query) {
            state.query = query
        }
    }
})

const app = new Vue({
    el: '#app',
    router,
    store,
    render: h => h(Vue.component('App'))
})
