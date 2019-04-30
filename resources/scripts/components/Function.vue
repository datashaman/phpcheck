<template>
    <b-card :id="f.shortName" class="card-function">
        <h4 slot="header">{{ f.shortName }}</h4>
        <b-card-text v-html="f.summary"></b-card-text>
        <b-card-text v-if="f.description" v-html="f.description"></b-card-text>

        <b-card-text v-if="f.arguments">
            <h5>Arguments</h5>

            <dl>
                <template v-for="a in f.arguments">
                    <dt>
                        <code>
                            <span v-if="a.type && a.type.length">{{ a.type }}</span>
                            <span v-if="a.variadic">…</span>${{ a.name }}
                            <span v-if="a.default">
                            = {{ a.default }}
                            </span>
                        </code>
                    </dt>

                    <dd v-if="a.description">{{ a.description }}</dd>
                </template>
            </dl>
        </b-card-text>

        <b-card-text v-if="f.example">
            <h5>Example</h5>

            <pre v-highlightjs><code class="php">{{ f.example }}</code></pre>
        </b-card-text>

        <b-card-text v-if="f.output">
            <h5>Output</h5>

            <pre v-highlightjs><code class="php">{{ f.output }}</code></pre>
        </b-card-text>

        <b-card-text v-if="f.gist">
            <b-input-group>
                <b-form-input class="flex-grow-1" type="text" :value="'melody run ' + f.gist" readonly />
                <b-input-group-append>
                    <b-button variant="success" class="btn-copy" v-clipboard:copy="'melody run ' + f.gist" @click="confirmCopy">
                        <i class="fas fa-copy"></i>
                    </b-button>
                    <b-button variant="outline-info" href="http://melody.sensiolabs.org">
                        <i class="fas fa-question"></i>
                    </b-button>
                </b-input-group-append>
            </b-input-group>
        </b-card-text>
    </b-card>
</template>

<script>
export default {
    props: [
        'shortName'
    ],
    computed: {
        f: function () {
            return this.$store.getters.func(this.shortName)
        }
    },
    methods: {
        confirmCopy() {
            this.$bvToast.toast('Copied!', {
                title: 'PHPCheck',
                autoHideDelay: 500,
                isStatus: true
            })
        }
    }
}
</script>
