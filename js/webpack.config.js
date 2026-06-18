const { merge } = require('webpack-merge');
const { CleanWebpackPlugin } = require('clean-webpack-plugin');
const MiniCssExtractPlugin = require('mini-css-extract-plugin');
const CssMinimizerPlugin = require('css-minimizer-webpack-plugin');
const FileManagerPlugin = require('filemanager-webpack-plugin');
const path = require('path');

const baseConfig = require('flarum-webpack-config')();

module.exports = (env, argv) => {
  const isProduction = argv.mode === 'production';

  /** @type {import('webpack').Configuration} */
  const customConfig = {
    output: {
      publicPath: 'auto',
      chunkFilename: `chunk~[name]_[chunkhash].js`,
    },
    plugins: [
      new CleanWebpackPlugin({
        dry: false,
        dangerouslyAllowCleanPatternsOutsideProject: true,
        cleanOnceBeforeBuildPatterns: [path.resolve(process.cwd(), '../assets/*')],
      }),
      new FileManagerPlugin({
        runTasksInSeries: true,
        events: {
          onEnd: {
            copy: [
              { source: 'dist/chunk*', destination: '../assets/' },
              { source: 'node_modules/leaflet/dist/images/*', destination: '../assets/' },
            ],
            delete: ['dist/chunk*'],
          },
        },
      }),
    ],
    module: {
      rules: [
        {
          test: /\.(sa|sc|c)ss$/,
          use: [!isProduction ? 'style-loader' : MiniCssExtractPlugin.loader, 'css-loader'],
        },
      ],
    },
    optimization: {
      minimizer: ['...', new CssMinimizerPlugin()],
    },
  };

  if (isProduction) {
    customConfig.plugins.push(
      new MiniCssExtractPlugin({
        filename: `[name]_[contenthash].css`,
        chunkFilename: `chunk~[name]_[chunkhash].css`,
      })
    );
  }

  return merge(baseConfig, customConfig);
};
