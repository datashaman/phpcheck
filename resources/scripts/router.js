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
    }
]

const router = new VueRouter({
    routes
})

export default router
