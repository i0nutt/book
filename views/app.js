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
		//load localized data
		this.load();
	},
	events: {
		'submit': 'onSubmit', 'err': 'alertMe', 'click .delete': 'delete'
	},
	/**
	 * Creates a model and saves it everywhere
	 * @param event
	 */
	onSubmit: function (event) {
		event.preventDefault();
		let model = new app.BookModel({
			title: this.$('form .title').val(),
			author: this.$('form .author').val(),
			genre: this.$('form .genre').val(),
			summary: this.$('form .summary').val()
		});
		if ( ! model.isValid() ) {
			this.err(BookGlobalText.text.invalidAuthor);
			return;
		}
		//keep a copy of this
		let myThis = this;
		model.save(null, {
			success: function () {
				myThis.Library.add(model);
			},
			error: function () {
				myThis.err(BookGlobalText.text.badInput);
			}
		});

	},
	/**
	 * Deletes a book on delete event
	 * @param event
	 */
	delete: function (event) {
		let id    = event.target.dataset.id;
		let model = this.Library.get(id);
		// custom url for destroy, id + post_id are needed
		let destroyUrl = document.location.origin + '/wp-json/bookAPI/v1/book/' + id + '/' + Book_Info.post_id;
		model.destroy( { url: destroyUrl});
	},
	/**
	 * Appends a view for a book model to the table
	 * @param model
	 */
	renderItem: function (model) {
		let item = new app.BookItem({model: model});
		this.$('#book table').append(item.render().el);
	},
	/**
	 * Deletes a view from the table
	 * @param model
	 */
	deleteItem: function (model) {
		new app.BookItem({model: model});
		this.$('#book table #book' + model.id).parent().remove();
	},
	/**
	 * Alerts with a message<br>
	 * Used for simple error handling
	 * @param message
	 */
	err: function (message) {
		window.alert(message);
	},
	/**
	 * Populates the collection with localized data
	 */
	load : function (){
		for (let key in Book_Info.data) {
			let book  = Book_Info.data[key];
			let model = new app.BookModel(
				{
					id: key,
					title: book.title,
					author: book.author,
					genre: book.genre,
					summary: book.summary
				}
			);
			console.log(model);
			this.Library.add(model);
		}
	}
});
