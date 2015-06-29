var build = require('./Build/Index.js');

// Add package your configurations here.
// The tasks for each package will be automatically created.
build.addPackages([
    require('./PackageConfig.js')
]);
