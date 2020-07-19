const MinifyPlugin = require('babel-minify-webpack-plugin');
const CopyPlugin = require('copy-webpack-plugin');
const MiniCssExtractPlugin = require('mini-css-extract-plugin');
const path = require('path');

const webpackConfig = require('./webpack.config');

module.exports = {
  ...webpackConfig,
  mode: 'production',
  devtool: 'source-map',
  output: {
    filename: 'static/brakkit.min.js',
    path: path.resolve(__dirname, 'dist')
  },
  plugins: [
    new MinifyPlugin(),
    new MiniCssExtractPlugin({
      filename: 'static/brakkit.min.css'
    }),
    new CopyPlugin({
      patterns: [
        { from: path.resolve(__dirname, 'static/images/'), to: 'static/images/' }
      ],
    })
  ]
}
