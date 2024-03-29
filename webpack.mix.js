const mix = require('laravel-mix');

/*
 |--------------------------------------------------------------------------
 | Mix Asset Management
 |--------------------------------------------------------------------------
 |
 | Mix provides a clean, fluent API for defining some Webpack build steps
 | for your Laravel applications. By default, we are compiling the CSS
 | file for the application as well as bundling up all the JS files.
 |
 */

mix
    .js('resources/js/general.js', 'public/js')
    .sass('resources/sass/newtemplate.scss', 'public/css')
    .sass('resources/sass/welcome.scss', 'public/css')
    .sass('resources/sass/general.scss', 'public/css')
    .sass('resources/sass/tasklist.scss', 'public/css')
    .sass('resources/sass/startnewdeal.scss', 'public/css')
    .sass('resources/sass/calendar.scss', 'public/css')
    .sass('resources/sass/board.scss', 'public/css');