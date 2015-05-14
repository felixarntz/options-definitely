'use strict';
module.exports = function(grunt) {
	grunt.initConfig({
		pkg: grunt.file.readJSON('package.json'),
		banner: '/*!\n' +
				' * <%= pkg.pluginName %> - Version <%= pkg.version %>\n' +
				' * \n' +
				' * <%= pkg.author.name %> <<%= pkg.author.email %>>\n' +
				' */',
		pluginheader: 	'/*\n' +
						'Plugin Name: <%= pkg.pluginName %>\n' +
						'Plugin URI: <%= pkg.homepage %>\n' +
						'Description: <%= pkg.description %>\n' +
						'Version: <%= pkg.version %>\n' +
						'Author: <%= pkg.author.name %>\n' +
						'Author URI: <%= pkg.author.url %>\n' +
						'License: <%= pkg.license.name %>\n' +
						'License URI: <%= pkg.license.url %>\n' +
						'Text Domain: wpod\n' +
						'Domain Path: /languages/\n' +
						'Tags: wordpress, plugin, framework, library, developer, options, admin, backend, ui\n' +
						'*/',
		fileheader: '/**\n' +
					' * @package WPOD\n' +
					' * @version <%= pkg.version %>\n' +
					' * @author <%= pkg.author.name %> <<%= pkg.author.email %>>\n' +
					' */',

		clean: {
			admin: [
				'assets/admin.css',
				'assets/admin.min.css',
				'assets/admin.min.js'
			],
			translation: [
				'languages/wpod.pot'
			]
		},

		jshint: {
			options: {
				boss: true,
				curly: true,
				eqeqeq: true,
				immed: true,
				noarg: true,
				quotmark: "single",
				undef: true,
				unused: true,
				browser: true,
				globals: {
					jQuery: false,
					console: false,
					wp: false,
					_wpod_admin: false,
					ajaxurl: false
				}
			},
			admin: {
				src: [
					'assets/admin.js'
				]
			}
		},

		uglify: {
			options: {
				preserveComments: 'some',
				report: 'min'
			},
			admin: {
				src: 'assets/admin.js',
				dest: 'assets/admin.min.js'
			}
		},

		recess: {
			options: {
				compile: true,
				compress: false,
				noIDS: true,
				noJSPrefix: true,
				noOverqualifying: false,
				noUnderscores: true,
				noUniversalSelectors: false,
				strictPropertyOrder: true,
				zeroUnits: true
			},
			admin: {
				files: {
					'assets/admin.css': 'assets/admin.less'
				}
			}
		},

		autoprefixer: {
			options: {
				browsers: [
					'Android 2.3',
					'Android >= 4',
					'Chrome >= 20',
					'Firefox >= 24',
					'Explorer >= 8',
					'iOS >= 6',
					'Opera >= 12',
					'Safari >= 6'
				]
			},
			admin: {
				src: 'assets/admin.css'
			}
    	},

		cssmin: {
			options: {
				compatibility: 'ie8',
				keepSpecialComments: '*',
				noAdvanced: true
			},
			admin: {
				files: {
					'assets/admin.min.css': 'assets/admin.css'
				}
			}
		},

		usebanner: {
			options: {
				position: 'top',
				banner: '<%= banner %>'
			},
			admin: {
				src: [
					'assets/admin.min.css',
					'assets/admin.min.js'
				]
			}
		},

		replace: {
			header: {
				src: [
					'options-definitely.php'
				],
				overwrite: true,
				replacements: [{
					from: /((?:\/\*(?:[^*]|(?:\*+[^*\/]))*\*+\/)|(?:\/\/.*))/,
					to: '<%= pluginheader %>'
				}]
			},
			version: {
				src: [
					'options-definitely.php',
					'inc/**/*.php'
				],
				overwrite: true,
				replacements: [{
					from: /\/\*\*\s+\*\s@package\s[^*]+\s+\*\s@version\s[^*]+\s+\*\s@author\s[^*]+\s\*\//,
					to: '<%= fileheader %>'
				}]
			}
		},

		makepot: {
			translation: {
				options: {
					domainPath: '/languages',
					exclude: [ 'vendor/.*' ],
					potComments: 'Copyright (c) 2014-<%= grunt.template.today("yyyy") %> <%= pkg.author.name %>',
					potFilename: 'wpod.pot',
					potHeaders: {
						'language-team': '<%= pkg.author.name %> <<%= pkg.author.email %>>',
						'last-translator': '<%= pkg.author.name %> <<%= pkg.author.email %>>',
						'project-id-version': '<%= pkg.name %> <%= pkg.version %>',
						'report-msgid-bugs-to': '<%= pkg.homepage %>',
						'x-generator': 'grunt-wp-i18n 0.4.5',
						'x-poedit-basepath': '.',
						'x-poedit-language': 'English',
						'x-poedit-country': 'UNITED STATES',
						'x-poedit-sourcecharset': 'uft-8',
						'x-poedit-keywordslist': '__;_e;_x:1,2c;_ex:1,2c;_n:1,2; _nx:1,2,4c;_n_noop:1,2;_nx_noop:1,2,3c;esc_attr__; esc_html__;esc_attr_e; esc_html_e;esc_attr_x:1,2c; esc_html_x:1,2c;',
						'x-poedit-bookmars': '',
						'x-poedit-searchpath-0': '.',
						'x-textdomain-support': 'yes'
					},
					type: 'wp-plugin'
				}
			}
		}

 	});

	grunt.loadNpmTasks('grunt-contrib-clean');
	grunt.loadNpmTasks('grunt-contrib-jshint');
	grunt.loadNpmTasks('grunt-contrib-uglify');
	grunt.loadNpmTasks('grunt-recess');
	grunt.loadNpmTasks('grunt-autoprefixer');
	grunt.loadNpmTasks('grunt-contrib-cssmin');
	grunt.loadNpmTasks('grunt-banner');
	grunt.loadNpmTasks('grunt-text-replace');
	grunt.loadNpmTasks('grunt-wp-i18n');

	grunt.registerTask('admin', [
		'clean:admin',
		'jshint:admin',
		'uglify:admin',
		'recess:admin',
		'autoprefixer:admin',
		'cssmin:admin',
	]);

	grunt.registerTask('translation', [
		'clean:translation',
		'makepot:translation'
	]);

	grunt.registerTask('plugin', [
		'usebanner',
		'replace:version',
		'replace:header'
	]);

	grunt.registerTask('default', [
		'admin'
	]);

	grunt.registerTask('build', [
		'admin',
		'translation',
		'plugin'
	]);
};
