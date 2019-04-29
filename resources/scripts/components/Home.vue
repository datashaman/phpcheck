<template>
    <b-container class="p-3">
        <div class="d-flex flex-row">
            <b-nav vertical>
                <b-form-input :value="query" type="search" placeholder="Search" @input="filter"></b-form-input>

                <b-nav-item href="#helpers">HELPERS</b-nav-item>

                <b-nav-item v-for="f in helpers" :key="f.shortName" :href="'#main-' + f.shortName">
                    {{ f.shortName }}
                </b-nav-item>

                <b-nav-item href="#generators">GENERATORS</b-nav-item>

                <b-nav-item v-for="f in helpers" :key="f.shortName" :href="'#main-' + f.shortName">
                    {{ f.shortName }}
                </b-nav-item>
            </b-nav>

            <div id="main" class="flex-grow-1 px-3">
                <h2 id="helpers">Helpers</h2>

                <function-list :functions="helpers"></function-list>

                <h2 id="generators">Generators</h2>

                <function-list :functions="generators"></function-list>
            </div>
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
