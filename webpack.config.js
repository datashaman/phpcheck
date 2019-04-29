const path = require('path')
const VueLoaderPlugin = require('vue-loader/lib/plugin')

module.exports = {
    entry: './resources/scripts/app.js',
    output: {
        path: path.join(__dirname, 'build/docs'),
        filename: 'app.js'
    },
    module: {
        rules: [{
            test: /\.scss$/,
            use: [
                'style-loader',
                'css-loader',
                'sass-loader'
            ]
        },{
            test: /\.css$/,
            use: [
                'vue-style-loader',
                'css-loader'
            ]
        },{
            test: /\.vue$/,
            loader: 'vue-loader'
        }]
    },
    plugins: [
        new VueLoaderPlugin()
    ]
}
