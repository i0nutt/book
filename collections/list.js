//var used for global scope availability
var app = app || {};
$       = jQuery;

// noinspection JSVoidFunctionReturnValueUsed
let Library = Backbone.Collection.extend(
	{
		model: app.BookModel,
		initialize: function () {
			this.load();
		},
		/**
		 * Gets AJAX request to load the data from wp post meta using post id in route
		 */
		load: function () {
			let library = this;
			$.ajax(
				{
					url: document.location.origin + '/wp-json/bookAPI/v1/books/' + $('#get_page_id').val(),
					type: 'get',
					data: {},
					success: function (response) {
						response = JSON.parse(response);
						if (response === false) {
							return;
						}
						//assoc array, need for this kind of for
						for (let key in response) {
								let book = response[key];
								//used to find a non-used id
								global    = Math.max(global,book.id);
								let model = new app.BookModel(
									{
										id: book.id,
										title: book.title,
										author: book.author,
										genre: book.genre,
										summary: book.summary
									}
								);
								library.add(model);
						}
					}
				}
			);
		}
	}
);
app.Library = new Library();
