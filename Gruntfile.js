module.exports = function(grunt) {
    grunt.initConfig({
        pkg: grunt.file.readJSON('package.json'),
        uglify: {
            options: {
                banner: '/* <%= pkg.name %> <%= grunt.template.today("yyyy-mm-dd") %> */\n'
            },
            dist: {
                files: {
                    './static/js/<%= pkg.name %>.min.js': [ './static/js/<%= pkg.name %>.js' ]
                }
            }
        },
        sass: {
            dist: {
                files: {
                    './static/css/<%= pkg.name %>.css': './static/css/dev/index.scss'
                }
            }
        },
        browserify: {
            options: {
                transform: [
                    [ 'babelify', { 'presets': 'es2015' } ],
                    [ 'browserify-handlebars' ]
                ],
                require: [
                    './node_modules/underscore/underscore.js:underscore',
                    './node_modules/jquery/dist/jquery.js:jquery',
                    './node_modules/moleculejs/src/molecule.js:molecule'
                ]
            },
            dist: {
              files: {
                  './static/js/<%= pkg.name %>.js': [ './static/js/dev/app.js', './views/*.hbs' ],
                  './tests/tests.js': [ './tests/specs/index.js', './views/*.hbs' ]
              }
            }
        },
        watch: {
            css: {
                files: [
                    './static/css/dev/**/*.scss'
                ],
                tasks: [ 'sass' ]
            },
            js: {
                files: [
                    './static/js/dev/**/*.js',
                    './views/**/*.hbs',
                    './tests/specs/**/*.js'
                ],
                tasks: [ 'browserify' ],
            },
            configFiles: {
                files: [ 'Gruntfile.js' ],
                options: {
                    reload: true
                }
            }
        }
    });

    grunt.loadNpmTasks('grunt-contrib-uglify');
    grunt.loadNpmTasks('grunt-contrib-watch');
    grunt.loadNpmTasks('grunt-browserify');
    grunt.loadNpmTasks('grunt-sass');

    grunt.registerTask('default', ['browserify', 'sass', 'uglify']);

};