<template>
    <b-container>
        <div id="container">
            <b-row class="d-flex">
                <b-col md="3" xl="2" id="nav">
                    <b-form-input :value="query" type="search" placeholder="Search" @input="filter"></b-form-input>

                    <b-nav vertical>
                        <b-nav-item disabled>HELPERS</b-nav-item>

                        <b-nav-item v-for="f in helpers" :key="f.shortName" :href="'#' + f.shortName">
                            {{ f.shortName }}
                        </b-nav-item>

                        <b-nav-item disabled>GENERATORS</b-nav-item>

                        <b-nav-item v-for="f in generators" :key="f.shortName" :href="'#' + f.shortName">
                            {{ f.shortName }}
                        </b-nav-item>
                    </b-nav>
                </b-col>

                <b-col md="9" xl="10" id="main">
                    <router-view></router-view>
                </b-col>
            </b-row>
        </div>
    </b-container>
</template>

<script>
import { mapGetters, mapState } from 'vuex'

export default {
    computed: {
        ...mapGetters([
            'generators',
            'helpers',
        ]),
        ...mapState([
            'query'
        ])
    },
    methods: {
        filter (value) {
            this.$store.commit('filter', value)
            this.$router.replace({ name: 'home' })
        }
    }
}
</script>
