import FuzzySearch from 'fuzzy-search'
import Vue from 'vue'
import VueClipboard from 'vue-clipboard2'
import VueRouter from 'vue-router'
import Vuex from 'vuex'

import { library } from '@fortawesome/fontawesome-svg-core'

import {
    faArrowUp,
    faCopy,
    faQuestion
} from '@fortawesome/free-solid-svg-icons'

import {
    FontAwesomeIcon,
    FontAwesomeLayers,
    FontAwesomeLayersText
} from '@fortawesome/vue-fontawesome'

library.add(
    faArrowUp,
    faCopy,
    faQuestion
)

Vue.component('font-awesome-icon', FontAwesomeIcon)
Vue.component('font-awesome-layers', FontAwesomeLayers)
Vue.component('font-awesome-layers-text', FontAwesomeLayersText)

Vue.config.productionTip = false

import BButton from 'bootstrap-vue/es/components/button/button'
import BCard from 'bootstrap-vue/es/components/card/card'
import BCardBody from 'bootstrap-vue/es/components/card/card-body'
import BCardText from 'bootstrap-vue/es/components/card/card-text'
import BCol from 'bootstrap-vue/es/components/layout/col'
import BContainer from 'bootstrap-vue/es/components/layout/container'
import BFormInput from 'bootstrap-vue/es/components/form-input/form-input'
import BInputGroup from 'bootstrap-vue/es/components/input-group/input-group'
import BInputGroupAppend from 'bootstrap-vue/es/components/input-group/input-group-append'
import BNav from 'bootstrap-vue/es/components/nav/nav'
import BNavItem from 'bootstrap-vue/es/components/nav/nav-item'
import BRow from 'bootstrap-vue/es/components/layout/row'

import Toast from 'bootstrap-vue/es/components/toast'
Vue.use(Toast)

Vue.component('b-button', BButton)
Vue.component('b-card', BCard)
Vue.component('b-card-body', BCardBody)
Vue.component('b-card-text', BCardText)
Vue.component('b-col', BCol)
Vue.component('b-container', BContainer)
Vue.component('b-form-input', BFormInput)
Vue.component('b-input-group', BInputGroup)
Vue.component('b-input-group-append', BInputGroupAppend)
Vue.component('b-nav', BNav)
Vue.component('b-nav-item', BNavItem)
Vue.component('b-row', BRow)

require('lolight')
require('../styles/app.scss')

import functions from '../json/functions.json'

Vue.use(VueClipboard)
Vue.use(VueRouter)
Vue.use(Vuex)

const generators = new FuzzySearch(functions.generators, ['shortName', 'summary'])
const helpers = new FuzzySearch(functions.helpers, ['shortName', 'summary'])

const files = require.context('./components', true, /\.vue$/)

files.keys().map(
    key => Vue.component(
        key.split('/').pop().split('.')[0],
        files(key).default
    )
)

const routes = [
    {
        name: 'home',
        path: '/',
        component: Vue.component('Home'),
    }
]

const router = new VueRouter({
    mode: 'history',
    routes
})

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
