//var used for global scope availability
var app = app || {};
$       = jQuery;

// noinspection JSVoidFunctionReturnValueUsed
let Library = Backbone.Collection.extend(
	{
		model: app.BookModel,
		initialize: function () {
		}
	}
);
app.Library = new Library();
