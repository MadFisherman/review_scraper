var stats_config = %%config%%;

(function() {
	var user_id = "%%user_id%%";

	var cart_items = document.querySelectorAll(stats_config.cart_item_name_selector ? stats_config.cart_item_name_selector : '');
	var cart_items_names = [];
	for(var i = 0; i < cart_items.length; i++) {
		cart_items_names[i] = cart_items[i].innerText;
	}

	var xhr = new XMLHttpRequest();
	xhr.open('POST', 'handler.php', true);
	xhr.send(JSON.stringify({
		user_id: user_id,
		page_url: document.location.href,
		cart: cart_items_names,
	}));

})();
