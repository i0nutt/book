//vars used for global scope availability
var app = app || {};
// noinspection JSVoidFunctionReturnValueUsed
app.BookModel = Backbone.Model.extend(
	{
		initialize : function (options) {
		},
		/**
		 * Sets url path for API requests
		 */
		url: function () {
			let myURL = document.location.origin + '/wp-json/bookAPI/v1/book';
			if (this.id) {
				myURL += '/' + this.id;
			}
			return myURL;
		},
		/**
		 * Default values<br>
		 * post_id is by default the id of the current book instance
		 */
		defaults: {
			post_id : Book_Info.post_id,
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
	}
);
