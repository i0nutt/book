//var used for global scope availability
var app = app || {};
$       = jQuery;

// noinspection JSVoidFunctionReturnValueUsed
app.MyApp = Backbone.View.extend({
	el: $('#book-app'),
	/**
	 * Initializes MyApp the app with models, views, collections and listeners
	 */
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
	events: {
		'submit': 'onSubmit', 'err': 'alertMe', 'click .delete': 'delete'
	},
	/**
	 * Creates a model and saves it everywhere
	 * @param e
	 */
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
	/**
	 * Deletes a book on delete event
	 * @param e
	 */
	delete: function (e) {
		let id    = e.target.dataset.id;
		let model = this.Library.get(id);
		// use my delete method because in order to delete a id and a post_id is necessary
		model.deleteFromSerialized();
		this.Library.remove(model);
	},
	/**
	 * Appends a view for a book model to the table
	 * @param model
	 */
	renderItem: function (model) {
		let item = new this.BookItem({model: model});
		this.$('#book table').append(item.render().el);
	},
	/**
	 * Deletes a view from the table
	 * @param model
	 */
	deleteItem: function (model) {
		let item = new this.BookItem({model: model});
		this.$('#book table #book' + model.id).parent().remove();
	},
	/**
	 * Alerts with a message<br>
	 * Used for simple error handling
	 * @param message
	 */
	err: function (message) {
		window.alert(message);
	}
});
