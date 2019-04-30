<template>
    <div>
        <h2 id="helpers">Helpers</h2>
        <function-list :functions="helpers"></function-list>

        <h2 id="generators">Generators</h2>
        <function-list :functions="generators"></function-list>
    </div>
</template>

<script>
import { mapGetters } from 'vuex'

export default {
    computed: {
        ...mapGetters([
            'generators',
            'helpers',
        ])
    }
}
</script>
