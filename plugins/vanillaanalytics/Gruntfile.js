'use strict';

module.exports = function (grunt) {
    // Load all Grunt tasks matching the `grunt-*` pattern
    require('load-grunt-tasks')(grunt);

    // Time how long tasks take. Can help when optimizing build times
    require('time-grunt')(grunt);

    grunt.file.mkdir('bower_components');

    // Load Bower dependencies
    var dependencies = require('wiredep')();

    grunt.initConfig({

        pkg: grunt.file.readJSON('package.json'),

        copy: {
            main: {
                files: [
                    {
                        expand: true,
                        flatten: true,
                        cwd: 'bower_components',
                        src: [
                            'c3/**/*.min.js'
                            , 'd3/**/*.min.js'
                            , 'jquery-ui/jquery-ui.min.js'
                            , 'js-cookie/**/*.js'
                            , 'momentjs/**/moment.min.js'
                            , 'keen-js/dist/keen.min.js'
                        ],
                        dest: 'js/vendors'
                    },
                    {
                        expand: true,
                        flatten: true,
                        cwd: 'bower_components',
                        src: [
                            'c3/**/*.min.css'
                        ],
                        dest: 'design/vendors'
                    }
                ]
            }
        },

        concat: {
            css: {
                src: ['design/vendors/*.css'],
                dest: 'design/vendors.css'
            }
        },

        cssmin: {
            css:{
                src: 'design/vendors.css',
                dest: 'design/vendors.min.css'
            }
        },

        uglify: {
            js: {
                options: {
                    sourceMap: true
                },
                files: [{
                    expand: true
                    , src: 'js/src/*.js'
                    , dest: 'js'
                    , ext: '.min.js'
                    , flatten: true
                }]
            }
        },

        watch: {
            gruntfile: {
                files: ['Gruntfile.js']
            }
            , livereload: {
                options: {
                    livereload: true
                }
                , files: [
                    'js/**/*.js'
                ]
            }
            , scripts: {
                files: ['js/src/*.js']
                , tasks: ['uglify']
            }
        },

        imagemin: {
            dist: {
                files: [{
                    expand: true,
                    cwd: 'design/images',
                    src: '**/*.{gif,jpeg,jpg,png,svg}',
                    dest: 'design/images'
                }]
            }
        }
    });

    grunt.registerTask('default', [
        'copy'
        , 'concat'
        , 'cssmin'
        , 'uglify'
        , 'imagemin'
    ]);
};
