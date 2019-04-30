import BootstrapVue from 'bootstrap-vue'
import FuzzySearch from 'fuzzy-search'
import Vue from 'vue'
import VueClipboard from 'vue-clipboard2'
import VueHighlightJS from 'vue-highlightjs'
import Vuex from 'vuex'

import { library } from '@fortawesome/fontawesome-svg-core'
import {
    faCopy,
    faQuestion
} from '@fortawesome/free-solid-svg-icons'
import {
    FontAwesomeIcon,
    FontAwesomeLayers,
    FontAwesomeLayersText
} from '@fortawesome/vue-fontawesome'

library.add(
    faCopy,
    faQuestion
)

Vue.component('font-awesome-icon', FontAwesomeIcon)
Vue.component('font-awesome-layers', FontAwesomeLayers)
Vue.component('font-awesome-layers-text', FontAwesomeLayersText)

Vue.config.productionTip = false

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
        func: state => shortName => {
            let f = functions.generators.find(f => f.shortName == shortName)
            if (f) return f
            return functions.helpers.find(f => f.shortName == shortName)
        },
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
