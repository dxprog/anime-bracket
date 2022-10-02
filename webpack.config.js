const path = require('path');
const MiniCssExtractPlugin = require('mini-css-extract-plugin');
const MinifyPlugin = require('babel-minify-webpack-plugin');
const CopyPlugin = require('copy-webpack-plugin');

module.exports = (env, argv) => {
  const isDev = argv.mode !== 'production';
  const plugins = [];

  if (!isDev) {
    plugins.push(new MinifyPlugin());
  }

  return {
    mode: isDev ? 'development' : 'production',
    entry: './static/js/app.js',
    devtool: isDev ? 'inline-source-map' : 'source-map',
    module: {
      rules: [
        {
          test: /\.(js|jsx)$/,
          use: 'babel-loader',
          exclude: /node_modules/,
        },
        {
          test: /\.hbs$/,
          loader: 'handlebars-loader',
          options: {
            partialResolver(partial, callback) {
              callback(null, path.resolve(__dirname, 'views/partials', `_${partial}.hbs`));
            },
            precompileOptions: {
              knownHelpersOnly: false,
            },
          },
        },
        {
          test: /\.scss$/,
          use: [
            isDev ? 'style-loader' : MiniCssExtractPlugin.loader,
            'css-loader',
            'sass-loader',
          ],
        },
      ],
    },
    resolve: {
      extensions: [ '.js' ],
      alias: {
        '@views': path.resolve(__dirname, 'views'),
        '@scss': path.resolve(__dirname, 'static/scss'),
        '@src': path.resolve(__dirname, 'static/js'),
      },
    },
    output: {
      filename: `static/brakkit${isDev ? '' : '.min'}.js`,
      path: path.resolve(__dirname, 'dist'),
    },
    plugins: [
      ...plugins,
      new MiniCssExtractPlugin({
        filename: `static/brakkit${isDev ? '' : '.min'}.css`
      }),
      new CopyPlugin({
        patterns: [
          { from: path.resolve(__dirname, 'static/images/'), to: 'static/images/' }
        ],
      }),
    ],
  };
}
