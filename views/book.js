//var used for global scope availability
var app = app || {};
// noinspection JSVoidFunctionReturnValueUsed
app.BookItem = Backbone.View.extend({
	//<tr> because I am inserting element in a table
	tagName: 'tr',
	//when one of the input boxes corresponding to this model is changed update model
	events : {
		"change input" : 'updateModel',
	},
	template: _.template(BookItem.HTMLtemplate),
	/**
	 * Renders the html for a book
	 * @returns {app.BookItem}
	 */
	render: function () {
		let html = this.template((this.model.attributes));
		this.$el.append(html);
		return this;
	},
	/**
	 * Updates model using patch request, only one field will be submitted at a time when editing
	 * @param event
	 */
	updateModel : function (event) {
		//save all potential patch data into a dictionary
		let data                = {};
		data.post_id            = this.model.get('post_id');
		data[event.target.name] = event.target.value;
		this.model.save(data, {patch : true ,
			error: function () {
				app.err('Bad input, check that your fields have only letters');
			}
		});
	}
});
