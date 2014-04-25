'use strict';

var gulp    = require('gulp')
  , $       = require('gulp-load-plugins')()
  , wiredep = require('wiredep').stream;

gulp.task('styles', function () {
  return gulp.src('less/main.less')
    .pipe($.plumber())
    .pipe($.less())
    .pipe($.autoprefixer())
    .pipe($.csslint('design/.csslintrc'))
    .pipe($.csslint.reporter('default'))
    .pipe($.csso())
    .pipe($.rename('custom.css'))
    .pipe(gulp.dest('design'))
    .pipe($.size());
});

gulp.task('scripts', function () {
  return gulp.src('js/src/main.js')
    .pipe($.plumber())
    .pipe($.jshint('js/.jshintrc'))
    .pipe($.jshint.reporter('default'))
    .pipe($.include())
    .pipe($.concat('custom.js'))
    .pipe($.uglify({
      preserveComments: 'some'
    }))
    .pipe(gulp.dest('js'))
    .pipe($.size());
});

gulp.task('images', function () {
  return gulp.src('design/images/**/*')
    .pipe($.plumber())
    .pipe($.cache($.imagemin({
      optimizationLevel : 3
    , progressive       : true
    , interlaced        : true
    })))
    .pipe(gulp.dest('design/images'))
    .pipe($.size());
});

gulp.task('fonts', function () {
  return $.bowerFiles()
    .pipe($.plumber())
    .pipe($.filter('**/*.{eot,svg,ttf,woff}'))
    .pipe($.flatten())
    .pipe(gulp.dest('design/fonts'))
    .pipe($.size());
});

gulp.task('wiredep', function () {
  return gulp.src('less/**/*.less')
    .pipe($.plumber())
    .pipe(wiredep({
      directory: 'bower_components'
    }))
    .pipe(gulp.dest('less'));
});

gulp.task('build', ['styles', 'scripts']);

gulp.task('default', ['wiredep'], function () {
  return gulp.start('build');
});

gulp.task('watch',  function () {
  var server = $.livereload();

  gulp.watch([
    'design/*.css'
  , 'js/*.js'
  , 'views/**/*.tpl'
  ], function (file) {
    return server.changed(file.path);
  });

  gulp.watch('less/**/*.less', ['styles']);
  gulp.watch('js/src/**/*.js', ['scripts']);
  gulp.watch('bower.json', ['wiredep']);
});
