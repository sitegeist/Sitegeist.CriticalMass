let React = require('react');
class Collection {

  constructor(el, dic) {
    this.el = el;
    this.dic = dic;
    this.footerPanelView = null;
    this.modalView = null;

    this.dic.SharedScope.expect('Sitegeist.NeosBackendBridge:Controller.FooterPanel')
      .then(this.initializeFooterPanel.bind(this));
  }

  initializeFooterPanel(footerPanel) {
    this.footerPanelView || (this.footerPanelView = require('./Views/FooterPanel.jsx').Element(this));
    console.log(this.footerPanelView);
    footerPanel.pushContent('CriticalMass', this.footerPanelView);
  }

  createNode() {
    // Open Modal Dialog
    let data;
    this.dic.SharedScope.expect('Sitegeist.NeosBackendBridge:Controller.Modal')
      .then((Modal) => {
        this.modalView || (this.modalView = require('./Views/Modal.jsx').Component);
        let modal = new Modal(this.modalView);

        console.log(modal);

        return modal.promptResult();
      }).then(function(result) {
        data = result;
        return this.dic.SharedScope.expect('TYPO3.Neos:NeosServiceAPI');
      }).then((NeosServiceAPI) => {

      })
      .catch((reason) => {
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
