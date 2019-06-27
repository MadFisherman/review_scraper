<?php

include_once "simple_html_dom.php";
include_once "connection.php";
use connection\Connect;

header('Content-Type: text/html; charset=utf-8');

function product_handler($input)
{
	//$postgresql = "host=localhost dbname=bender user=bender password=bender";
	$current_product = $input['item_name'];
	$dbconn = pg_connect(Connect::$postgresql) or die();
	$query = "SELECT * FROM reviews INNER JOIN reviews_products as rp ON reviews.id_review = rp.id_review INNER JOIN products AS p ON rp.id_product = p.id_product WHERE name = '" . $current_product . "' AND reviews.is_translated = TRUE";
	$result = pg_query($dbconn, $query);
	$row = pg_fetch_all($result);
	$html_content = "<div class='container'>";
	if($row) {

		foreach($row as $element)
		{
			$html_content .= "<div class='single_review' 
					style='border: solid 1px black; border-radius: 8px; box-sizing: border-box;padding: 20px; margin: auto;'>
					<div><span class=''>". $element['review_author'] . "</span></div>
					"//<div><span class=''>". $element['review_header'] . "</span></div>
					."<div><span class=''>". $element['review_posted_date'] . "</span>
					<span class=''>". $element['review_rating'] . "</span></div>
					<div><span class=''>". $element['review_translated_text'] . "</span></div>
				</div>";
		}
	}
	$html_content .= "</div>";
	echo $html_content;
	check_db($dbconn);
	translator_handler();
	parser_handler();
	pg_close($dbconn);	
}
function cookie_handler($input)
{
	
}
function parser_handler()
{
	//по идее здесь будет обработчик отзывов для одного конкретного товара, потому в конце можно провести проверку на наличие этого
	//товара в бд и установки значения is_parsed в 1
	$dbconn = pg_connect(Connect::$postgresql) or die('Не удалось соединиться: ' . pg_last_error());
	$data = file_get_contents('data.json');
	$parsed_data = json_decode($data, TRUE);
	foreach($parsed_data[0]['reviews'] as $elem)
	{
		$total_array = [
			'id_site' => 1,
			'is_translated' => 0,
			'review_header' => $elem['review_header'],
			'review_text' => $elem['review_text'],
			'review_posted_date' => $elem['review_posted_date'],
			'review_rating' => $elem['review_rating'],
			'review_author' => $elem['review_author'],
			'review_translated_text' => NULL
		];
		$total_array['review_text'] = str_replace(['\'', "’"], ' ', $total_array['review_text']);
		$result = pg_select($dbconn, "reviews", ['review_text' => $total_array['review_text']]);
		//Определение наличия товара в базе и в случае его отсутствия занесение его в соответствующую таблицу
		$current_product_name = $parsed_data[0]['name'];
		$product_result = pg_select($dbconn, "products", ['name' => $current_product_name]);
		if(!$product_result)
		{
			pg_insert($dbconn, "products", ['is_parced' => 0, 'name' => $current_product_name]);
			$product_result = pg_select($dbconn, "products", ['name' => $current_product_name]);
		}
		$current_product_id = $product_result[0]['id_product'];
		//Определение наличия отзыва, в случае его отсутствия занесение его туда, а так же связывание через смежную таблицу
		//с продуктами
		if(!$result)
		{	
			/*если кто то когда то это будет читать, я хуй знает почему каждый раз пишет что column header is missing если блять задаю я другое название. Если этот коммент так и остался здесь, значит я забИ(Ы)л
//$query = "INSERT INTO reviews (id_site,is_translated,review_header,review_text,review_posted_date,review_rating,review_author) VALUES (" . $total_array['id_site'] . "," . $total_array['is_translated'] . "," . $total_array['review_header']. "," .$total_array['review_text']. ",'" .$total_array['review_posted_date']. "'," .$total_array['review_rating']. "," .$total_array['review_author']. ") RETURNING id";*/
			pg_insert($dbconn, "reviews", $total_array);
			$last_insert_id = pg_select($dbconn, "reviews", ['review_text' => $total_array['review_text']])[0]['id_review'];
			pg_insert($dbconn, "reviews_products", ['id_review' => $last_insert_id, 'id_product' => $current_product_id]);
			pg_update($dbconn, "products", ['is_parced' => 1], ['id_product' => $current_product_id]);
		}
		if(gettype($result) != 'boolean' && gettype($result) != 'array') 
			pg_free_result($result);
	}
	pg_close($dbconn);
}

function translator_handler()
{
	//Выбираем из таблицы ревью, которые не были переведены 
	$dbconn = pg_connect(Connect::$postgresql) or die();
	$query = "SELECT id_review, review_text FROM reviews WHERE is_translated = FALSE";
	$result = pg_query($dbconn, $query);
	$rows = pg_fetch_all($result);
	if($rows){
		$id_review_arr = [];
		foreach($rows as $row)
		{	
			//Судя по всему нужен возврат ключа, для того что бы получать данные обратно, ибо как мы узнаем какая ревью когда переведена (по любому перевод будет не по порядку
			$total_order_text .= '##id' . $row['id_review'] . '<br>' . $row['review_text'] . '<br>';
			$id_review_arr[] = $row['id_review'];
		}
		$current_order_id = create_order($total_order_text);
		if ($current_order_id)
		{
			foreach($id_review_arr as $elem)
			{
				pg_update($dbconn, "reviews", ['id_order' => $current_order_id], ['id_review' => $elem]);
			}
		}
	}
	pg_close($dbconn);
}

function create_order($order_original_text)
{
	//Блок отправки даных в турботекст, устанавливаются параметры для перевода
	//Отправлять будем по одному заказу (одному отзыву)
	$api_key = Connect::$turbotext_apikey; // ваш API-ключ
	$order_size_to = iconv_strlen($order_original_text) + 100;
	$parameters = [
		'api_key' => $api_key,
		'action' => 'create_translate_order',
		'order_title' => 'Перевод отзыва',
		'order_original_text' => $order_original_text,
		'lang1' => 2,
		'lang2' => 1,
		'order_description' => 'Перевод отзывов к некоторому товару с английского языка на русский. <p>Отзывы разделены тегом вида ##id. Все отзывы после перевода обязательно должны быть разделены так же, как в оригинальной статье!!! </p>',
		'order_who_can_work' => 3,
		'order_who_can_work_value' => 369128,
		'order_price_for_total' => 0,
		'order_price' => 49,
		'order_size_from' => 300,
		'order_size_to' => $order_size_to
	];
	
	$ch = curl_init("https://www.turbotext.ru/api");
	curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($parameters));
	curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_error($ch);
	$t = curl_exec($ch);
	$t = json_decode('['.$t.']');
	if ($t[0]->{"success"} == "false")
		return FALSE;
	else
		return $t[0]->{"order_id"};
	
}
function check_db($dbconn) 
{
	/*проверка базы данных на наличие указанных id заказа на сайте turbotext
	правильно добавлять будет только отзывы конкретно с turbotext и поясню почему
	по идее все теги в тексте это просто символы, однако я не знаю (и мб не скоро узнаю) почему < и > у турботекста это на самом деле $|< и >gt& видимо там какие то маркеры или тэги остаются лишние
	*/
	$query = "SELECT id_order FROM reviews WHERE id_order != 0 AND is_translated = FALSE GROUP BY id_order";
	$result = pg_query($dbconn, $query);
	$rows = pg_fetch_all($result);
	if($rows)
	{
		foreach($rows as $row)
		{
			$translated_reviews = get_order($row['id_order']);
		}
		$translated_reviews = preg_split("/[#][#]/u", $translated_reviews);
		foreach($translated_reviews as $elem){
			//$elem = explode("br /", $elem);

			$elem = preg_split("/[^a-z]br [^a-z]{2}gt;/u", $elem);
			$elem[0] = preg_split("/id/u", $elem[0]);
			$elem[0][1] = substr($elem[0][1], 0, -3);

			echo '<br>';
			$translated_arr[$elem[0][1]] = substr($elem[1], 0, -3);
		}
		foreach($translated_arr as $k => $v)
		{
			pg_update($dbconn, "reviews", ['review_translated_text' => $v, 'id_order' => 0, 'is_translated' => 1], ['id_review' => $k]);
		}
	}
}
function get_order($order_id)
{
	//функция получения информации о заказе (срабатывает при загрузке скрипта)
	$api_key = Connect::$turbotext_apikey; // ваш API-ключ
	$parameters = [
		'api_key' => $api_key,
		'action' => 'get_order',
		'order_id' => $order_id
	];

	$ch = curl_init("https://www.turbotext.ru/api");
	curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($parameters));
	curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_error($ch);
	$t = curl_exec($ch);
	$t = json_decode('['.$t.']');
	if ($t[0]->{"success"} == "false")
	{
		die($t[0]->{"errors"});
	}
	return $t[0]->{"text"};
}
?>
