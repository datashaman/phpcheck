import BootstrapVue from 'bootstrap-vue'
import FuzzySearch from 'fuzzy-search'
import Vue from 'vue'
import VueClipboard from 'vue-clipboard2'
import VueHighlightJS from 'vue-highlightjs'
import Vuex from 'vuex'

import '@fortawesome/fontawesome-free/js/fontawesome'
import '@fortawesome/fontawesome-free/js/solid'
import '@fortawesome/fontawesome-free/js/regular'
import '@fortawesome/fontawesome-free/js/brands'

Vue.use(BootstrapVue)
Vue.use(VueClipboard)
Vue.use(VueHighlightJS)
Vue.use(Vuex)

require('../styles/app.scss')
require('highlight.js/styles/solarized-light.css')

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
