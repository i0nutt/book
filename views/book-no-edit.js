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
		let html = "<td>" + this.model.get('title') + "</td>";
		html    += "<td>" + this.model.get('author') + "</td>";
		html    += "<td>" + this.model.get('genre') + "</td>";
		html    += "<td>" + this.model.get('summary') + "</td>";
		this.$el.append(html);
		return this;
	},
});
