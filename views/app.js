$       = jQuery;
var app = app || {};
//app, here is the main processing/event-handler place of the entire collection
app.MyApp = Backbone.View.extend({
	el: $('#book-app'),
	//initialize the app view
	initialize: function () {
		this.Library   = app.Library;
		this.BookModel = app.BookModel;
		this.BookItem  = app.BookItem;
		//listener for create, append item to table
		this.listenTo(this.Library, 'add', (model) => {
			this.renderItem(model);
		});
		//listener for delete, delete item from table
		this.listenTo(this.Library, 'remove', (model) => {
			this.deleteItem(model);
		});
	},
	//buttons actions and error alert message
	events: {
		'submit': 'onSubmit', 'err': 'alertMe', 'click .delete': 'delete'
	},
	//save model on form submit
	onSubmit: function (e) {
		e.preventDefault();
		let model = new this.BookModel({
			title: this.$('form .title').val(),
			author: this.$('form .author').val(),
			genre: this.$('form .genre').val(),
			summary: this.$('form .summary').val()
		});
		if ( ! model.isValid() ) {
			this.err('Author format is probably wrong or there was a server problem');
			return;
		}
		//keep a copy of this
		let myThis = this;
		model.save(null, {
			success: function () {
				myThis.Library.add(model);
			}
		}, {
			error: function () {
			}
		});

	},
	//delete model when delete button is clicked
	delete: function (e) {
		let id    = e.target.dataset.id;
		let model = this.Library.get(id);
		// use my delete method because in order to delete a id and a post_id is necessary
		model.deleteFromSerialized();
		this.Library.remove(model);
	},
	// add item to table on create
	renderItem: function (model) {
		let item = new this.BookItem({model: model});
		this.$('#book table').append(item.render().el);
	},
	//delete item from table on delete
	deleteItem: function (model) {
		let item = new this.BookItem({model: model});
		this.$('#book table #book' + model.id).parent().remove();
	},
	//just a simple error handling method
	err: function (message) {
		window.alert(message);
	}
});
