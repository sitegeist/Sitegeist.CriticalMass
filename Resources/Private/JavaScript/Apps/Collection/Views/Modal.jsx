let React = require('react');
class ModalView extends React.Component {

  constructor() {
    super();
    this.handleChange = this.handleChange.bind(this);
    this.handleCancel = this.handleCancel.bind(this);
    this.handleSave = this.handleSave.bind(this);
  }

  render() {
    return <div class="sgcm-createNodePrompt">
      <input type="text" name="title" placeholder="Enter title..." onChange={this.handleChange} />

      <footer>
        <button class="btn" onClick={this.handleCancel}>Cancel</button>
        <button class="btn" onClick={this.handleSave}>Save</button>
      </footer>
    </div>;
  }

  getInitialState() {
    return {
      title: ''
    };
  }

  setPromise(promise) {
    this.setProps({ promise });
  }

  handleChange(event) {
    this.setState({
      title: event.target.value
    });
  }

  handleCancel() {
    this.props.promise.reject();
  }

  handleSave() {
    this.props.promise.resolve(this.state);
  }
}

module.exports = {
  Component: ModalView
}
