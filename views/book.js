var app = app || {};
// view of one book
app.BookItem = Backbone.View.extend({
	//<tr> because I am inserting element in a table
	tagName: 'tr',
	//when one of the input boxes corresponding to this model is changed update model
	events : {
		"change input" : 'updateModel',
	},
	//render the html element corresponding to a book
	render: function () {
		let data         = this.model.toJSON();
		let html         = "<td id = 'book" + data.id + "'><input type='text' name = 'title' class = 'title' placeholder='Title' value='" + data.title + "' required></td>";
		html            += "<td><input type ='text'  name = 'author'  class = 'author'  placeholder = 'author'  value = ' " + data.author + " ' required></td>";
		html            += "<td><input type ='text'  name = 'genre'   class = 'genre'   placeholder = 'Genre'   value = ' " + data.genre + " ' required></td>";
		html            += "<td><input type = 'text' name = 'summary' class = 'summary' placeholder = 'Summary' value = ' " + data.summary + " ' required></td>";
		let deleteButton = "<button class = 'delete' data-id = " + data.id + "> Delete </button>";
		html            += "<td>" + deleteButton + "</td>";
		this.$el.append(html);
		return this;
	},
	//update model using patch request, only one field will be submitted at a time when editing
	updateModel : function (e) {
		//save all potential patch data into a dictionary
		let data     = {};
		data.post_id = this.model.get('post_id');
		switch (e.target.className) {
			case 'title' :
				data.title = this.$('input.title').val();
				break;
			case 'author' :
				data.author = this.$('input.author').val();
				break;
			case 'genre' :
				data.genre = this.$('input.genre').val();
				break;
			case 'summary' :
				data.summary = this.$('input.summary').val();
				break;
		}
		this.model.save(data, {patch : true});
	}
});
