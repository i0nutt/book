//var used for global scope availability
var app = app || {};
// noinspection JSVoidFunctionReturnValueUsed
app.BookItem = Backbone.View.extend({
	//<tr> because I am inserting element in a table
	tagName: 'tr',
	//when one of the input boxes corresponding to this model is changed update model
	events: {
		"change input": 'updateModel',
	},
	/**
	 * Renders the html for a book
	 * @returns {app.BookItem}
	 */
	render: function () {
		let data = this.model.toJSON();
		let html = "<td>" + data.title + "</td>";
		html    += "<td>" + data.author + "</td>";
		html    += "<td>" + data.genre + "</td>";
		html    += "<td>" + data.summary + "</td>";
		this.$el.append(html);
		return this;
	},
});
