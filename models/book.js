//vars used for global scope availability
var app    = app || {};
var global = 1;

app.BookModel = Backbone.Model.extend(
	{
		initialize : function () {
			this.set( 'id', global );
			global += 1;
		},
		//set url path for API requests
		url : function () {
			return document.location.origin + '/wp-json/bookAPI/v1/book/' + this.get( "id" );
		},
		//default values, post_id is by default the id of the current post
		defaults: {
			post_id : jQuery( '#get_page_id' ).val(),
			title: '',
			author: '',
			genre: '',
			summary: '',
		},
		//check if author has 2 words
		validate : function (attrs, options) {
			if (this.get( 'author' ).split( ' ' ).length < 2) {
				return "author must be a valid name";
			}
		},
		// ajax request to delete element from the serialized post meta field
		// the url has two parameters, book id and post id, both are needed for deletion of a book
		deleteFromSerialized : function () {
			let book = this;
			$.ajax(
				{
					url: document.location.origin + '/wp-json/bookAPI/v1/book/' + book.get('id') + '&' + book.get('post_id'),
					type: 'delete',
					success: function (response) {
						app.Library.remove(book);
					}
				}
			);
		}
	}
);
