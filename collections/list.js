var app = app || {};
$       = jQuery;

let Library = Backbone.Collection.extend(
	{
		model: app.BookModel,
		initialize: function () {
			this.load();
		},
		load: function () {
			let library = this;
			$.ajax(
				{
					url: document.location.origin + '/wp-json/bookAPI/v1/books/' + $('#get_page_id').val(),
					type: 'get',
					data: {},
					success: function (response) {
						response = JSON.parse(response);
						console.log(response);
						if (response === false) {
							return;
						}
						for (let key in response) {
								let book  = response[key];
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
