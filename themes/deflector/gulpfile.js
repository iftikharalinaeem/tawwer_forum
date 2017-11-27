'use strict';

var gulp = require('gulp')
    // Load all Gulp tasks matching the `gulp-*` pattern
  , $ = require('gulp-load-plugins')();

gulp.task('styles', function () {
  return gulp.src('scss/*.scss')
    .pipe($.plumber())
    .pipe($.sass({
      errLogToConsole: true
    }))
    .pipe($.autoprefixer({
      "browsers": ["ie > 9", "last 6 iOS versions", "last 4 versions"]
    }))
    .pipe(gulp.dest('design'))
    .pipe($.size({showFiles: true}));
});

gulp.task('scripts', function () {
  var dependencies = require('wiredep')({
        exclude: ["dist/jquery.js"]
      })
    , source = $.filter('js/src/**/*.js');

  return gulp.src((dependencies.js || []).concat([
    'js/src/main.js'
  ]))
    .pipe($.plumber())
    .pipe(source)
    .pipe($.jshint('js/.jshintrc'))
    .pipe($.jshint.reporter('default'))
    .pipe(source.restore())
    .pipe($.concat('custom.js'))
    .pipe(gulp.dest('js'))
    .pipe($.size({showFiles: true}));
});

gulp.task('images', function () {
  return gulp.src('design/images/**/*')
    .pipe($.cache($.imagemin({
      optimizationLevel: 3,
      progressive: true,
      interlaced: true
    })))
    .pipe(gulp.dest('design/images'))
    .pipe($.size({showFiles: true}));
});

gulp.task('wiredep', function () {
  var wiredep = require('wiredep').stream;

  return gulp.src('scss/**/*.scss')
    .pipe(wiredep())
    .pipe(gulp.dest('scss'));
});

gulp.task('default', ['wiredep'], function () {
  gulp.start('styles', 'scripts', 'images');
});

gulp.task('watch', function () {
  var server = $.livereload();

  gulp.watch([
    'design/*.css'
  , 'design/images/**/*'
  , 'js/*.js'
  , 'views/**/*.tpl'
  ], function (file) {
    return server.changed(file.path);
  });

  gulp.watch('scss/**/*.scss', ['styles']);
  gulp.watch('js/src/**/*.js', ['scripts']);
  gulp.watch('design/images/**/*', ['images']);
  gulp.watch('bower.json', ['wiredep']);
});
