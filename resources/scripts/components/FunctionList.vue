<template>
    <div>
        <template v-for="(f, index) in functions">
            <b-card :id="f.shortName" class="card-function">
                <h4 slot="header">
                    <div v-if="index" class="float-right">
                        <b-button size="sm" href="/"><font-awesome-icon icon="arrow-up"/></b-button>
                    </div>
                    {{ f.shortName }}
                </h4>

                <b-card-text v-html="f.summary"></b-card-text>
                <b-card-text v-if="f.description" v-html="f.description"></b-card-text>

                <pre class="lolight"><b-card-body>{{ f.header }}</b-card-body></pre>

                <b-card-text v-if="f.arguments">
                    <h5>Arguments</h5>

                    <dl>
                        <template v-for="a in f.arguments">
                            <dt>
                                <code>
                                    <span v-if="a.type">{{ a.type }}</span>
                                    <span v-if="a.variadic">…</span>${{ a.name }}
                                    <span v-if="a.default">
                                    = {{ a.default }}
                                    </span>
                                </code>
                            </dt>

                            <dd v-if="a.description" v-html="a.description"/>
                        </template>
                    </dl>
                </b-card-text>

                <b-card-text v-if="f.example">
                    <h5>Example</h5>

                    <pre class="lolight">{{ f.example }}</pre>
                </b-card-text>

                <b-card-text v-if="f.output">
                    <h5>Output</h5>

                    <pre>{{ f.output }}</pre>
                </b-card-text>

                <b-card-text v-if="f.gist">
                    <b-input-group>
                        <b-form-input class="flex-grow-1" type="text" :value="'melody run ' + f.gist" readonly />
                        <b-input-group-append>
                            <b-button variant="success" class="btn-copy" v-clipboard:copy="'melody run ' + f.gist" @click="confirmCopy">
                                <font-awesome-icon icon="copy"></font-awesome-icon>
                            </b-button>
                            <b-button variant="outline-info" href="http://melody.sensiolabs.org">
                                <font-awesome-icon icon="question"></font-awesome-icon>
                            </b-button>
                        </b-input-group-append>
                    </b-input-group>
                </b-card-text>
            </b-card>
        </template>
    </div>
</template>

<script>
export default {
    props: [
        'functions'
    ],
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

<style scoped>
pre {
    white-space: -moz-pre-wrap; /* Mozilla, supported since 1999 */
    white-space: -pre-wrap; /* Opera */
    white-space: -o-pre-wrap; /* Opera */
    white-space: pre-wrap; /* CSS3 - Text module (Candidate Recommendation) http://www.w3.org/TR/css3-text/#white-space */
    word-wrap: break-word; /* IE 5.5+ */
    max-width: 870px;
}
.card-function {
    margin-bottom: 12px;
}
.card-function:last-child() {
    margin-bottom: 0;
}
</style>
