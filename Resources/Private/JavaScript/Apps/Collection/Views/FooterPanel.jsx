let React = require('react');
class FooterPanelView extends React.Component {

  /**
   * @constructor
   */
  constructor() {
    super();
    this.handleClickOnCreate = this.handleClickOnCreate.bind(this);
  }

  getDefaultProps() {
    return {
      controller: {
        createNode: () => {}
      }
    };
  }

  /**
   * @return {DOMElement}
   */
  render() {
    return <button onClick={this.handleClickOnCreate}>
      Create New Entry
    </button>
  }

  /**
   * @return {void}
   */
  handleClickOnCreate() {
    this.props.controller.createNode();
  }
}

module.exports = {

  /**
   * Standalone Component, without controller context
   */
  Component : FooterPanelView,

  /**
   * Workaround to immediately create an element out of the Component
   * and attach a controller to it
   */
  Element : function(controller) {
    let element = React.createElement(FooterPanelView, {
      controller: controller
    });

    return element;
  }
}
