/* laxcomma: true */
'use strict';

var gulp = require('gulp')
  , $ = require('gulp-load-plugins')()
  , del = require('del');

gulp.task('compile', function () {

  return gulp.src([
    'less/style.less'
  , 'less/themes/*.less'
  ], { base: 'less' })
    .pipe($.plumber())
    .pipe($.less())
    .pipe($.autoprefixer())
    .pipe($.csso())
    .pipe(gulp.dest('design'))
    .pipe($.size({showFiles: true}));
});

gulp.task('rename', ['compile'], function() {
  return gulp.src([
      'design/themes/*.css'
    ])
    .pipe($.rename({
      dirname: '/'
      , prefix: 'custom_'
    }))
    .pipe(gulp.dest('design'));
});

gulp.task('styles', ['rename'], function() {
  return del([
    'design/themes'
  ]);
});

gulp.task('scripts', function () {
  var dependencies = require('wiredep')()
    , source = $.filter('js/src/**/*.js');

  return gulp.src((dependencies.js || []).concat([
    'js/src/main.js',
    'bower_components/bootstrap/js/transition.js',
    'bower_components/bootstrap/js/collapse.js'
  ]))
    .pipe($.plumber())
    .pipe($.concat('custom.js'))
    .pipe($.uglify())
    .pipe(gulp.dest('js'))
    .pipe($.size({showFiles: true}));
});

gulp.task('wiredep', function () {
  var wiredep = require('wiredep').stream;

  return gulp.src('less/**/*.less')
    .pipe(wiredep())
    .pipe(gulp.dest('less'));
});

gulp.task('default', ['styles', 'scripts']);

gulp.task('watch',  function () {
  $.livereload.listen();

  gulp.watch([
    'design/*.css'
  , 'js/*.js'
  , 'views/**/*.tpl'
  ], function (file) {
    return $.livereload.changed(file.path);
  });

  gulp.watch('less/**/*.less', ['styles']);
  gulp.watch('js/src/**/*.js', ['scripts']);
  gulp.watch('bower.json', ['wiredep']);
});

// Expose Gulp to external tools
module.exports = gulp;
