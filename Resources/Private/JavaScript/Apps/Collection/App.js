let React = require('react');
let modalView;

class Collection {

  constructor(el, dic) {
    this.el = el;
    this.dic = dic;
    this.footerPanelView = null;
    this.modalView = null;

    this.dic.SharedScope.expect('Sitegeist.NeosBackendBridge:Controller.FooterPanel')
      .then(this.initializeFooterPanel.bind(this));

    [].forEach.call(this.el.querySelectorAll('input[data-namespace="Sitegeist.CriticalMass:Collection"]'), (input) => {
      if (input.dataset.key && input.value) {
        this[ input.dataset.key ] = input.value;
      }
    });
  }

  initializeFooterPanel(footerPanel) {
    this.footerPanelView || (this.footerPanelView = require('./Views/FooterPanel.jsx').Element(this));
    footerPanel.pushContent(this.footerPanelView);
  }

  createNode() {
    let data;
    this.dic.SharedScope.expect('Sitegeist.NeosBackendBridge:Controller.Modal')
      .then((Modal) => { // Open Modal Dialog
        modalView || (modalView = require('./Views/Modal.jsx').Component);
        return Modal.prompt('Create new Entry', modalView);
      }).then((result) => { // When there is a modal result, try to load NeosServiceAPI
        data = result;
        return this.dic.SharedScope.expect('TYPO3.Neos:NeosServiceAPI');
      }).then((NeosServiceAPI) => { // With NeosServiceAPI: Create node
        return NeosServiceAPI.createNode(this.referenceNode, {
          nodeType: this.nodeType,
          properties: data
        }, this.insertPosition || 'into');
      }).then((result) => { // if everything is in place, redirect to the resulting page
        window.location.href = result.data.nextUri;
      })
      .catch((reason) => { // Handle Errors
        console.log(reason);
        if (reason instanceof Error) {
          // Handle error
        }
      });
  }
}

/**
 * Export a bootstrap function, that offers to insert a Dependency Injection
 * Container.
 *
 * @param {object} dic
 * @return {function} Initialization function for the App
 */
module.exports = function bootstrap(dic = {}) {
  dic.SharedScope || (dic.SharedScope = require('pflibs--shared-scope'));
  return function create(el) {
    return new Collection(el, dic);
  };
};
