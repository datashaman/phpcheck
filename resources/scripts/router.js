import Vue from 'vue'

import VueRouter from 'vue-router'
Vue.use(VueRouter)

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
    },
    {
        name: 'function',
        path: '/:shortName',
        component: Vue.component('Function'),
        props: true
    }
]

const router = new VueRouter({
    mode: 'history',
    routes
})

export default router
