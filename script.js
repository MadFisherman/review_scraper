var stats_config = %%config%%;

(function() {
	var user_id = "%%user_id%%";

	var cart_items = document.querySelectorAll(stats_config.cart_item_name_selector ? stats_config.cart_item_name_selector : '');
	var cart_items_names = [];
	for(var i = 0; i < cart_items.length; i++) {
		cart_items_names[i] = cart_items[i].innerText;
	}

	var xhr = new XMLHttpRequest();
	xhr.open('POST', 'https://staging.realytics.ru/reviews/handler.php', true);
	xhr.send(JSON.stringify({
		user_id: user_id,
		page_url: document.location.href,
		cart: cart_items_names,
	}));

})();

(function() {

        var item_name = document.querySelectorAll(stats_config.page_item_name_selector ? stats_config.page_item_name_selector : '')[0].innerText;
        var item_category = document.querySelectorAll(stats_config.page_item_category_selector ? stats_config.page_item_category_selector : '')[0].innerText;
        var paste_container = document.querySelectorAll(stats_config.paste_container_selector ? stats_config.paste_container_selector : '')[0];
	console.log(item_name, paste_container);
        var xhr = new XMLHttpRequest();
        xhr.open('POST', 'https://staging.realytics.ru/reviews/handler.php', true);

        xhr.onreadystatechange = function () {
        if(xhr.readyState === XMLHttpRequest.DONE && xhr.status === 200) {
            paste_container.innerHTML = xhr.responseText;
        };
    };

        xhr.send(JSON.stringify({
                item_name: item_name,
                item_category: item_category,
        }));

})();

