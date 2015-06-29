let ComponentDomParser = require('componentdomparser');

let appParser = new ComponentDomParser({
  dataSelector: 'sgcmapp',
  componentIndex: {
    'Sitegeist.CriticalMass:Collection': require('./Apps/Collection/App')()
  }
});

document.addEventListener('DOMContentLoaded', function() {
  appParser.parse();
});
