const path = require('path');
const MiniCssExtractPlugin = require('mini-css-extract-plugin');

module.exports = {
  mode: 'development',
  entry: './static/js/app.js',
  devtool: 'inline-source-map',
  module: {
    rules: [
      {
        test: /\.ts$/,
        use: 'ts-loader',
        exclude: /node_modules/
      },
      {
        test: /\.hbs$/,
        loader: 'handlebars-loader',
        query: {
          partialResolver(partial, callback) {
            callback(null, path.resolve(__dirname, 'views/partials', `_${partial}.hbs`));
          },
          precompileOptions: {
            knownHelpersOnly: false
          }
        }
      },
      {
        test: /\.scss$/,
        use: [ MiniCssExtractPlugin.loader, 'css-loader', 'sass-loader' ]
      }
    ]
  },
  resolve: {
    extensions: [ '.ts', '.js' ],
    alias: {
      '@views': path.resolve(__dirname, 'views'),
      '@scss': path.resolve(__dirname, 'static/scss')
    }
  },
  output: {
    filename: 'brakkit.js',
    path: path.resolve(__dirname, 'dist')
  },
  plugins: [
    new MiniCssExtractPlugin({
      filename: 'styles.css'
    })
  ]
}
