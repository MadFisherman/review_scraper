<?php

header('Content-Type: text/javascript');

if(!isset($_COOKIE['stats_user_id'])) {
	$bytes = random_bytes(8);
	$user_id = bin2hex($bytes);
	setcookie('stats_user_id', $user_id, time() + 365 * 86400 * 10);
	echo "cookies";
} else {
	$user_id = $_COOKIE['stats_user_id'];
}

if(isset($_SERVER['HTTP_REFERER']))
{
	$url = parse_url($_SERVER['HTTP_REFERER']);
	if(isset($url['host'])) $config = config_create($url['host']);
}
$config = "{
			cart_item_name_selector: '.cart .item .name',
			page_item_name_selector: '.item-name',
			page_item_category_selector: '.item-category',
			paste_container_selector: '.paste-containter',
		}";
$js = str_replace('%%user_id%%', $user_id, file_get_contents('script.js'));
$js = str_replace('%%config%%', $config, $js);

echo $js;

function config_create($host_name)
{
	switch($host_name) {
		case 'abc.ru' :
			return  "{
			cart_item_name_selector: '.cart .item .nameeeee',
			page_item_name_selector: '.item-name',
			page_item_category_selector: '.item-category',
			paste_container_selector: '.paste-containter',
		}";
		default :
			return "{
			cart_item_name_selector: '.cart .item .name',
			page_item_name_selector: '.item-name',
			page_item_category_selector: '.item-category',
			paste_container_selector: '.paste-containter',
		}";
	}	
}
?>
