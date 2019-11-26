<?php
$turbotext_apikey = "xa6iuepfnyk8m7t10rvcjbhwo9gdlz5sq243";
$postgresql = "host=localhost dbname=bender user=bender password=bender";

while(TRUE) {
	$dbconn = pg_connect($postgresql) or die();
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
			$v = text_checking($v);
			pg_update($dbconn, "reviews", ['review_translated_text' => $v, 'id_order' => 0, 'is_translated' => 1], ['id_review' => $k]);
		}
	}
	sleep(3600);
}
function text_checking($text) {
	//функция проверки текста на наличие лишних тегов и мнемоников
	//так же ищет звездочки и добавляет перенос строки
	$pattern = array("/<[a-zA-Zа-яёА-ЯЁ]{1,4}>?/", "/&ls?aquo;/", "/&rs?aquo;/", "/&quot;/");
	$replace = array("", "«", "»", '"');
	$text = preg_replace($pattern, $replace, $text);
	$text = preg_replace("/\*/", "<br>*", $text);
	return $text;
}
function get_order($order_id)
{
	//функция получения информации о заказе
	$api_key = $turbotext_apikey;
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
