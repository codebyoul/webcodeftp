const path = require('path');

module.exports = {
  entry: './src/codemirror-bundle.js',
  output: {
    filename: 'codemirror-bundle.js',
    path: path.resolve(__dirname, 'public/js/dist'),
    library: 'CodeMirrorBundle',
    libraryTarget: 'window',
    libraryExport: 'default'
  },
  resolve: {
    extensions: ['.js']
  },
  optimization: {
    minimize: true
  }
};