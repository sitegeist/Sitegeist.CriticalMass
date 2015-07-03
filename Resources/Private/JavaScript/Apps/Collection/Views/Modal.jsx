let React = require('react');
class ModalView extends React.Component {

  constructor() {
    super();
    this.handleChange = this.handleChange.bind(this);
    this.handleCancel = this.handleCancel.bind(this);
    this.handleSave = this.handleSave.bind(this);
  }

  render() {
    return <div className="sgcm-createNodePrompt u-cf">
      Please Enter a title for your new entry.
      <input className="sgbtextInput" type="text" name="title" placeholder="Enter title..." onChange={this.handleChange} />

      <footer>
        <button className="sgbbtn sgbbtn--positive u-floatRight" onClick={this.handleSave}>Save</button>
        <button className="sgbbtn u-floatLeft" onClick={this.handleCancel}>Cancel</button>
      </footer>
    </div>;
  }

  getInitialState() {
    return {
      title: ''
    };
  }

  handleChange(event) {
    this.setState({
      title: event.target.value
    });
  }

  handleCancel() {
    this.props.deferred.reject();
  }

  handleSave() {
    this.props.deferred.resolve(this.state);
  }
}

module.exports = {
  Component: ModalView
}
