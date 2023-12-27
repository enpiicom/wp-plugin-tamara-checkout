/* Set webpack variables */

var webpackParams = {
    // Input file path
    entryPath: {
        main: ['./public-assets/src/js/main.js', './public-assets/src/scss/main.scss'],
        admin: ['./public-assets/src/js/admin.js', './public-assets/src/scss/admin.scss']
    },

    // Output for CSS and JS
    jsOutputPath: './public-assets/dist/js/[name].js',
    cssOutputPath: './public-assets/dist/css/[name].css',

};

module.exports = {webpackParams};

