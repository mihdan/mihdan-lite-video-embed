'use strict';

const { src, dest, watch, series, parallel } = require('gulp');
const sass = require('gulp-sass')(require('sass'));
const autoprefixer = require('gulp-autoprefixer');
const rename = require("gulp-rename");
const sourcemaps = require('gulp-sourcemaps');
const concat = require('gulp-concat');
const uglify = require('gulp-uglify-es').default;
const livereload = require('gulp-livereload');

const files = {
    frontend_styles: {
        src: ['assets/src/frontend/scss/lite-yt-embed.scss'],
        watch: ['assets/src/frontend/scss/*.scss']
    },
    admin_styles: {
        src: ['assets/src/admin/scss/lite-yt-embed.scss'],
        watch: ['assets/src/admin/scss/*.scss']
    },
    frontend_javascript: {
       src: ['assets/src/frontend/js/lite-yt-embed.js'],
       watch: ['assets/src/frontend/js/lite-yt-embed.js']
    },
    frontend_lozad: {
        src: ['assets/src/frontend/js/lozad.js'],
        watch: ['assets/src/frontend/js/lozad.js']
    },
    html: {
        src: ['*.php'],
        watch: ['*.php']
    }
};

function frontendStylesTask() {
    return src(files.frontend_styles.src)
        .pipe(sourcemaps.init())
        .pipe(sass({outputStyle: 'compressed'}).on('error', sass.logError))
        .pipe(autoprefixer())
        .pipe(rename('assets/dist/css/frontend.css'))
        .pipe(sourcemaps.write('.'))
        .pipe(dest('.'))
        .pipe(livereload());
}

function adminStylesTask() {
    return src(files.admin_styles.src)
        .pipe(sourcemaps.init())
        .pipe(sass({outputStyle: 'compressed'}).on('error', sass.logError))
        .pipe(autoprefixer())
        .pipe(rename('assets/dist/css/admin.css'))
        .pipe(sourcemaps.write('.'))
        .pipe(dest('.'))
        .pipe(livereload());
}

function frontendJavaScriptTask() {
    return src(files.frontend_javascript.src)
        .pipe(concat('frontend.js'))
        .pipe(uglify())
        .pipe(dest('assets/dist/js'))
        .pipe(livereload());
}

function frontendJavaScriptLozadTask() {
    return src(files.frontend_lozad.src)
        .pipe(concat('lozad.js'))
        .pipe(uglify())
        .pipe(dest('assets/dist/js'))
        .pipe(livereload());
}

function htmlTask() {
    return src(files.html.src)
        .pipe(livereload());
}

function watchTask() {
    livereload.listen();
    watch(files.frontend_styles.watch, parallel(frontendStylesTask));
    watch(files.admin_styles.watch, parallel(adminStylesTask));
    watch(files.frontend_javascript.watch, parallel(frontendJavaScriptTask));
    watch(files.frontend_lozad.watch, parallel(frontendJavaScriptLozadTask));
    watch(files.html.watch, parallel(htmlTask));
}

exports.styles = series(
    frontendStylesTask,
    adminStylesTask,
);

exports.js = series(
    frontendJavaScriptTask,
    frontendJavaScriptLozadTask
);

exports.html = series(
    htmlTask
);

exports.watch = series(
    watchTask
);

exports.default = series(
    frontendStylesTask,
    adminStylesTask,
    frontendJavaScriptTask,
    frontendJavaScriptLozadTask,
    htmlTask
);