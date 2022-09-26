//vars used for global scope availability
var app = app || {};
// noinspection JSVoidFunctionReturnValueUsed
app.BookModel = Backbone.Model.extend(
	{
		initialize : function (options) {
			console.log(options);
		},
		/**
		 * Sets url path for API requests
		 */
		url : function () {
			return document.location.origin + '/wp-json/bookAPI/v1/book';
		},
		/**
		 * Default values<br>
		 * post_id is by default the id of the current book instance
		 */
		defaults: {
			post_id : jQuery( '#get_page_id' ).val(),
			title: '',
			author: '',
			genre: '',
			summary: '',
		},
		/**
		 * Checks if author has 2 words
		 * @param attrs
		 * @param options
		 * @returns {string}
		 */
		validate : function (attrs, options) {
			if (this.get( 'author' ).split( ' ' ).length < 2) {
				return "author must be a valid name";
			}
		},
		/**
		 * AJAX request to delete element from the serialized post meta field<br>
		 */
		deleteFromSerialized : function () {
			let book = this;
			$.ajax(
				{
					//The url has two parameters, book id and post id, both are needed for deletion of a book
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
