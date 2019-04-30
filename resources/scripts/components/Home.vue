<template>
    <b-container>
        <div id="container">
            <b-row class="d-flex">
                <b-col md="3" xl="2" id="nav">
                    <b-form-input :value="query" type="search" placeholder="Search" @input="filter"></b-form-input>

                    <b-nav vertical>
                        <b-nav-item href="#helpers">HELPERS</b-nav-item>

                        <b-nav-item v-for="f in helpers" :key="f.shortName" :href="'#' + f.shortName">
                            {{ f.shortName }}
                        </b-nav-item>

                        <b-nav-item href="#generators">GENERATORS</b-nav-item>

                        <b-nav-item v-for="f in generators" :key="f.shortName" :href="'#' + f.shortName">
                            {{ f.shortName }}
                        </b-nav-item>
                    </b-nav>
                </b-col>

                <b-col md="9" xl="10" id="main">
                    <h2 id="helpers">Helpers</h2>

                    <function-list :functions="helpers"></function-list>

                    <h2 id="generators">Generators</h2>

                    <function-list :functions="generators"></function-list>
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
        }
    }
}
</script>
