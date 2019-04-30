<template>
    <div>
        <template v-for="f in functions">
            <function :shortName="f.shortName" :key="f.shortName" />
        </template>
    </div>
</template>

<script>
export default {
    props: [
        'functions'
    ]
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
