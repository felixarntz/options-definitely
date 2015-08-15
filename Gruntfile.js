'use strict';
module.exports = function(grunt) {
	grunt.initConfig({
		pkg: grunt.file.readJSON('package.json'),
		pluginheader: 	'/*\n' +
						'Plugin Name: Options Definitely\n' +
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
			translation: [
				'languages/wpod.pot'
			]
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
					mainFile: 'options-definitely.php',
					domainPath: '/languages',
					exclude: [ 'vendor/.*' ],
					potComments: 'Copyright (c) 2014-<%= grunt.template.today("yyyy") %> <%= pkg.author.name %>',
					potFilename: 'wpod.pot',
					potHeaders: {
						'language-team': '<%= pkg.author.name %> <<%= pkg.author.email %>>',
						'last-translator': '<%= pkg.author.name %> <<%= pkg.author.email %>>',
						'project-id-version': '<%= pkg.name %> <%= pkg.version %>',
						'report-msgid-bugs-to': '<%= pkg.homepage %>',
						'x-generator': 'grunt-wp-i18n 0.5.3',
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
	grunt.loadNpmTasks('grunt-text-replace');
	grunt.loadNpmTasks('grunt-wp-i18n');

	grunt.registerTask('translation', [
		'clean:translation',
		'makepot:translation'
	]);

	grunt.registerTask('plugin', [
		'replace:version',
		'replace:header'
	]);

	grunt.registerTask('default', [
		'translation'
	]);

	grunt.registerTask('build', [
		'translation',
		'plugin'
	]);
};
