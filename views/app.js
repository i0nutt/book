$       = jQuery;
var app = app || {};

app.MyApp = Backbone.View.extend({
	el: $('#book-app'),
	initialize: function () {
		this.Library   = app.Library;
		this.BookModel = app.BookModel;
		this.BookItem  = app.BookItem;
		this.listenTo(this.Library, 'add', (model) => {
			this.renderItem(model);
		});
		this.listenTo(this.Library, 'remove', (model) => {
			this.deleteItem(model);
		});
	},

	events: {
		'submit': 'onSubmit', 'err': 'alertMe', 'click .edit': 'edit', 'click .delete': 'delete'
	},

	onSubmit: function (e) {
		e.preventDefault();
		let model = new this.BookModel({
			//id : this.Library.models.length,
			title: this.$('.title').val(),
			author: this.$('.author').val(),
			genre: this.$('.genre').val(),
			summary: this.$('.summary').val()
		});
		if ( ! model.isValid() ) {
			this.err('Author format is probably wrong or there was a server problem');
			return;
		}
		let myThis = this;
		model.save(null, {
			success: function () {
				myThis.Library.add(model);
			}
		}, {
			error: function () {
			}
		});

	}, edit: function (e) {
		let id    = e.target.dataset.id;
		let model = this.Library.get(id);
		console.log(model);
	}, delete: function (e) {
		let id    = e.target.dataset.id;
		let model = this.Library.get(id);
		model.deleteFromSerialized();
		this.Library.remove(model);
	}, renderItem: function (model) {
		let item = new this.BookItem({model: model});
		this.$('#book table').append(item.render().el);
	}, deleteItem: function (model) {
		let item = new this.BookItem({model: model});
		this.$('#book table #book' + model.id).parent().remove();
	},err: function (message) {
		window.alert(message);
	}
});
