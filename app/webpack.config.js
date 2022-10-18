var path = require('path');
var webpack = require('webpack');
var filename = 'amf-cbc-app.js';
var publicPath = '/modules/custom/amf_cbc/app/dist/';
const mode = process.env.NODE_ENV;
const VueLoaderPlugin = require('vue-loader/lib/plugin');

module.exports = {
  mode: mode,
  entry: './src/main.js',
  optimization: {
    minimize: false //Update this to true or false
  },
  output: {
    path: path.resolve(__dirname, './dist'),
    publicPath: publicPath,
    filename: filename
  },
  module: {
    rules: [
      {
        test: /\.vue$/,
        loader: 'vue-loader',
        options: {
          loaders: {
            'scss': 'vue-style-loader!css-loader!sass-loader',
            'sass': 'vue-style-loader!css-loader!sass-loader?indentedSyntax'
          },
        }
      },
      {
        test: /\.js$/,
        loader: 'babel-loader',
        exclude: /node_modules/
      },
      {
        test: /\.css$/,
        loader: 'file-loader',
        options: {
          name: '[name].[ext]?[hash]',
          esModule: false,
        },
      },
      {
        test: /\.(png|jpg|gif|svg)$/,
        loader: 'file-loader',
        options: {
          name: '[name].[ext]?[hash]',
          esModule: false,
        },
      },
      {
        test: /\.s(c|a)ss$/,
        use: [
          'vue-style-loader',
          'css-loader',
          {
            loader: 'sass-loader',
            options: {
              implementation: require('sass'),
              sassOptions: {
                fiber: require('fibers'),
              },
            },
          },
        ],
      },
    ]
  },
  resolve: {
    alias: {
      'vue$': 'vue/dist/vue.esm.js'
    }
  },
  devServer: {
    historyApiFallback: true,
    noInfo: true
  },
  performance: {
    hints: false
  },
  plugins: [
    new VueLoaderPlugin(),
  ],
  devtool: '#eval-source-map'
};

if (mode === 'production') {
  module.exports.optimization.minimize = true;
  module.exports.devtool = '#source-map';
  module.exports.plugins = (module.exports.plugins || []).concat([
    new webpack.DefinePlugin({
      'process.env': {
        NODE_ENV: '"production"'
      }
    }),
    new webpack.LoaderOptionsPlugin({
      minimize: true
    })
  ]);
  module.exports.externals = {};
}
