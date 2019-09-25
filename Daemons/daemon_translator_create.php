<?php
$turbotext_apikey = "xa6iuepfnyk8m7t10rvcjbhwo9gdlz5sq243";
$postgresql = "host=localhost dbname=bender user=bender password=bender";

while(TRUE) {
	$dbconn = pg_connect($postgresql) or die();
	$query = "SELECT id_review, review_text FROM reviews WHERE is_translated = FALSE";
	$result = pg_query($dbconn, $query);
	$rows = pg_fetch_all($result);
	if($rows){
		$id_review_arr = [];
		foreach($rows as $row)
		{
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
	sleep(3600);
}

function create_order($order_original_text)
{
	//Блок отправки даных в турботекст, устанавливаются параметры для перевода
	//Отправлять будем по одному заказу (группе отзывов)
	$api_key = $turbotext_apikey;
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
?>
