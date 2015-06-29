var paths = {
    'private': 'Resources/Private/',
    'public': 'Resources/Public/'
};

module.exports = {
    // The name of the package, which also respresents the task namespace.
    'name': 'sgcm',

    // The working directory of the package, on which all paths are based upon.
    'basePath': './',

    // Example configuration for sass/css related tasks.
    'sass': {
        'src': paths.private + 'Sass/',
        'dest': paths.public + 'Styles/',

        // The filepattern to watch and compile.
        'filePattern': '**/*.scss',

        // Additional settings for node-sass.
        'settings': {
            // Used by the image-url helper.
            'imagePath': '../Images'
        }
    },

    // Example configuration for images related tasks.
    'images': {
        'src': paths.private + 'Images/',
        'dest': paths.public + 'Images/',

        // The filepattern to compile.
        'filePattern': '**/*.{png,jpg,gif,svg}',

        // Additional settings for the imagemin plugin.
        'settings': {
            'progressive': true,
            'svgoPlugins': [{
                'removeViewBox': false
            }]
        }
    },

    // Example configuration for scripts related tasks.
    'scripts': {
        'src': paths.private + 'JavaScript/',
        'dest': paths.public + 'JavaScript/',

        // The filepattern to watch, compile and lint.
        'filePattern': ['**/*.js', '**/*.jsx', '!Vendor/**/*.js'],

        // The JS bundles to create with browserify, this example creates 2 bundles;
        // The first one inherits all the application logic, the second one bundles all vendor dependencies for faster build times.
        'bundles': [
            {
                // The namespace for the task, f.e.: compile:scripts:main
                'name': 'main',

                // The entry file for browserify, relative to the src path described above.
                'src': 'App.js',

                // The outName for browserify, relative to the dest path described above.
                'dest': 'App.min.js',

                // Additional settings for browserify.
                'settings': {
                    'external': ['lodash']
                },

                'transform': [['babelify', { 'experimental': true }]]
            },
            {
                'name': 'vendor',
                'src': null,
                'dest': 'Vendor.min.js',
                'settings': {
                    'external': null,
                    'require': ['lodash']
                }
            }
        ]
    }
};
