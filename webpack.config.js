// Webpack uses this to work with directories
const path = require('path');
const UglifyJsPlugin = require('uglifyjs-webpack-plugin');
const MiniCssExtractPlugin = require("mini-css-extract-plugin");
const OptimizeCSSAssetsPlugin = require('optimize-css-assets-webpack-plugin');
const FixStyleOnlyEntriesPlugin = require("webpack-fix-style-only-entries");

// This is main configuration object.
// Here you write different options and tell Webpack what to do
module.exports = {

    // Path to your entry point. From this file Webpack will begin his work
    entry: {
        "admin": "./sources/js/admin.js",
        "admin.min": "./sources/js/admin.js",
        "admin-styles": "./sources/css/admin.css",
        "admin-styles.min": "./sources/css/admin.css",
    },

    devtool: "source-map",

    // Path and filename of your result bundle.
    // Webpack will bundle all JavaScript into this file
    output: {
        path: path.resolve(__dirname, 'dist'),
        filename: "[name].js"
    },

    resolve: {
        extensions: ['.js']
    },

    optimization: {
        minimize: true,
        minimizer: [
            new UglifyJsPlugin({
                include: /\.min\.js$/
            }),
            new OptimizeCSSAssetsPlugin({
                assetNameRegExp: /\.min\.css$/g,
            })
        ]
    },

    plugins: [
        new FixStyleOnlyEntriesPlugin(),
        new MiniCssExtractPlugin({
            // Options similar to the same options in webpackOptions.output
            // both options are optional
            filename: '[name].css',
            path: path.resolve(__dirname, 'dist'),
        }),
    ],

    module: {
        rules: [
            {
                test: /\.css$/,
                use: [
                    {
                        loader: MiniCssExtractPlugin.loader,
                    },
                    {
                        loader: 'css-loader',
                    },
                ],
            },
        ],
    },

    mode: "none",
};