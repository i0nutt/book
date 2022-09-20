var app = app || {};

app.BookItem = Backbone.View.extend({
	tagName: 'tr',
	initialize: function () {

	},
	render: function () {
		let data         = this.model.toJSON();
		let html         = "<td id = 'book" + data.id + "'>" + data.title + "</td>";
		html            += "<td>" + data.author + "</td>";
		html            += "<td>" + data.genre + "</td>";
		html            += "<td>" + data.summary + "</td>";
		let editButton   = "<button class = 'edit' data-id = " + data.id + " style = 'margin-right : 5px'> Edit </button>";
		let deleteButton = "<button class = 'delete' data-id = " + data.id + "> Delete </button>";
		html            += "<td>" + editButton + deleteButton + "</td>";
		this.$el.append(html);
		return this;
	}
});
