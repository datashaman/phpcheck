const path = require('path')
const CompressionPlugin = require('compression-webpack-plugin')
const VueLoaderPlugin = require('vue-loader/lib/plugin')

module.exports = {
    entry: './resources/scripts/app.js',
    output: {
        chunkFilename: '[name].bundle.js',
        path: path.join(__dirname, 'dist'),
        filename: '[name].bundle.js'
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
        },{
            test: /\.woff(2)?(\?v=[0-9]\.[0-9]\.[0-9])?$/,
            loader: "url-loader?limit=10000&mimetype=application/font-woff"
        },{
            test: /\.(ttf|eot|svg)(\?v=[0-9]\.[0-9]\.[0-9])?$/,
            loader: "file-loader"
        }]
    },
    plugins: [
        new CompressionPlugin(),
        new VueLoaderPlugin()
    ],
    stats: {
        maxModules: Infinity,
        optimizationBailout: true
    },
    devServer: {
        contentBase: path.join(__dirname, 'dist')
    }
}
