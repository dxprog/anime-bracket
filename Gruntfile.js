module.exports = function(grunt) {
    grunt.initConfig({
        pkg: grunt.file.readJSON('package.json'),
        concat: {
            options: {
                separator: ';'
            },
            dist: {
                src: [
                    'view/js/handlebars.runtime.js',
                    'view/js/templates.js',
                    'view/js/model/*.js',
                    'view/js/bracket_display.js'
                ],
                dest: 'view/<%= pkg.name %>.js'
            }
        },
        uglify: {
            options: {
                banner: '/* <%= pkg.name %> <%= grunt.template.today("yyyy-mm-dd") %> */\n'
            },
            dist: {
                files: {
                    'view/<%= pkg.name %>.min.js': ['<%= concat.dist.dest %>']
                }
            }
        },
        watch: {
            files: [
                'view/js/bracket_display.js',
                'view/js/model/*.js',
                'view/css/styles.scss',
                'view/css/mixins.scss',
                'view/handlebars/*.handlebars'
            ],
            tasks: [ 'handlebars', 'concat', 'sass' ]
        },
        handlebars: {
            compile: {
                options: {
                    namespace: 'Templates',
                    wrapped:true,
                    processName: function(filename) {
                        filename = filename.split('/');
                        filename = filename[filename.length - 1];
                        return filename.split('.')[0];
                    }
                },
                files: {
                    'view/js/templates.js': 'view/handlebars/*.handlebars'
                }
            }
        },
        sass: {
            dist: {
                files: {
                    'view/css/styles.css': 'view/css/styles.scss'
                }
            }
        }
    });

    grunt.loadNpmTasks('grunt-contrib-uglify');
    grunt.loadNpmTasks('grunt-contrib-jshint');
    grunt.loadNpmTasks('grunt-contrib-watch');
    grunt.loadNpmTasks('grunt-contrib-concat');
    grunt.loadNpmTasks('grunt-contrib-handlebars');
    grunt.loadNpmTasks('grunt-sass');

    grunt.registerTask('default', ['handlebars', 'concat', 'uglify', 'sass']);

};