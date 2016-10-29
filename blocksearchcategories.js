var instantSearchQueries = [];
$(document).ready(function()
{
	if (typeof blocksearch_type == 'undefined')
		return;

	var $input = $("#search_query_" + blocksearch_type);

	var width_ac_results = 	$input.parent('form').outerWidth();
	if (typeof ajaxsearch != 'undefined' && ajaxsearch) {
		$input.autocomplete(
			search_url,
			{
				minChars: 3,
				max: 10,
				width: (width_ac_results > 0 ? width_ac_results : 500),
				selectFirst: false,
				scroll: false,
				dataType: "json",
				formatItem: function(data, i, max, value, term) {
					return value;
				},
				parse: function(data) {
					var mytab = [];
					for (var i = 0; i < data.length; i++)
						mytab[mytab.length] = { data: data[i], value: data[i].cname + ' > ' + data[i].cdesc };
					return mytab;
				},
				extraParams: {
					ajaxSearch: 1,
					id_lang: id_lang
				}
			}
		)
		.result(function(event, data, formatted) {
			$input.val(data.cname);
			document.location.href = data.category_link;
		});
	}
});
