<?php
static $postgresql = "host=localhost dbname=bender user=bender password=bender";

class name2ASIN
{
	public static $get_url = 'https://v3.synccentric.com/api/v3/products'; //получение данных обратно
	public static $import_url = 'https://v3.synccentric.com/api/v3/products'; //импортирование идентификаторов от 1 до 1000 штук
	public static $status_url = 'https://v3.synccentric.com/api/v3/product_search/status'; //проверка статуса поиска
	public static $initiate_url = 'https://v3.synccentric.com/api/v3/product_search'; //инициация поиска 
	public static $delete_url = 'https://v3.synccentric.com/api/v3/products/'; //удаление продуктов, чьи ASIN уже занесены (добавить {id}) без указания id удалит вообще все продукты из базы
	public static $sync_token = '6xy5NNz6q5ocPesStvg9HZ41g1yHxVtqmHhAuUwokCYdEwLuWfvV79bEGYyZ'; //токен для доступа в аккаунт
	public static $campaignId = 15169; //номер компании, но в целом не обязяательно его указывать ибо компания доступна только одна
}

while(TRUE) {
	$headers = array('Authorization: Bearer ' . name2ASIN::$sync_token,
	    	'Content-Type: application/json');
	$dbconn = pg_connect($postgresql) or die('Не удалось соединиться: ' . pg_last_error());

	$status = getProducts($headers, $dbconn);
	$query = 'SELECT name FROM products WHERE in_progress = 0';
	$result = pg_query($dbconn, $query);
	$rows = pg_fetch_all($result);
	if($rows) {
		//deleteProducts($headers); //пока что не работает видать, выводит список всех товаров
		importProducts($dbconn, $headers);
		initiateSearch($headers);
	}
	sleep(3600);
}

function getProducts($headers, $dbconn) {
	$status = curlSend($headers, name2ASIN::$status_url , 0);
	$status = json_decode($status, TRUE);
	//foreach($status as $k => $v) {
	//echo 'text: ' . $k . ' => ' . $v . ';<br>';
	//}
	if($status['percentage'] == 100) {
		$response = curlSend($headers, name2ASIN::$get_url, 0);
		$response = json_decode($response, TRUE);
		foreach($response['data'] as $product) {
			$current_id = $product['id'];
			$initial_request = $product['attributes']['initial_identifier'];
			$success = pg_update($dbconn, "products", ['is_asined' => 1, 'asin' => $product['attributes']['asin'], 'in_progress' => 0], ['name' => $initial_request]);
		}
		return TRUE;
	}
	else return FALSE;
}
function deleteProducts($headers) {
	$curl = curl_init();

	curl_setopt_array($curl, array(
		CURLOPT_URL => name2ASIN::$delete_url,
		CURLOPT_HTTPHEADER => $headers,
		CURLOPT_RETURNTRANSFER => true,
		CURLOPT_ENCODING => "",
		CURLOPT_MAXREDIRS => 10,
		CURLOPT_TIMEOUT => 0,
		CURLOPT_FOLLOWLOCATION => false,
		CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
		CURLOPT_CUSTOMREQUEST => "GET",
	));

	$response = curl_exec($curl);
	$err = curl_error($curl);

	curl_close($curl);

	/*if ($err) {
		echo "cURL Error #:" . $err;
	} else {
		echo $response;
	}*/
}
function importProducts($dbconn, $headers) {
	$query = 'SELECT name FROM products WHERE is_asined = 0 AND in_progress = 0';
	$result = pg_query($dbconn, $query);
	$rows = pg_fetch_all($result);
	$name_arr = [];
	$row_count = 0;
	$message_count = 0;
	$identifiers = [];
	if($rows) {
		$row_count = count($rows);
		if($row_count > 1000) {
			$row_count = 1000;
		}
		$ten_pack = floor($row_count / 10);
		$ost = $row_count % 10;
		if($ten_pack != 0) {
			for($i = 0; $i < $ten_pack; $i++) {
				array_push($identifiers, ['identifier' => $rows[0 + ($i * 10)]['name'], 'type' => 'keyword'],
				['identifier' => $rows[1 + ($i * 10)]['name'], 'type' => 'keyword'],
				['identifier' => $rows[2 + ($i * 10)]['name'], 'type' => 'keyword'],
				['identifier' => $rows[3 + ($i * 10)]['name'], 'type' => 'keyword'],
				['identifier' => $rows[4 + ($i * 10)]['name'], 'type' => 'keyword'],
				['identifier' => $rows[5 + ($i * 10)]['name'], 'type' => 'keyword'],
				['identifier' => $rows[6 + ($i * 10)]['name'], 'type' => 'keyword'],
				['identifier' => $rows[7 + ($i * 10)]['name'], 'type' => 'keyword'],
				['identifier' => $rows[8 + ($i * 10)]['name'], 'type' => 'keyword'],
				['identifier' => $rows[9 + ($i * 10)]['name'], 'type' => 'keyword']);
			}
		}
		for($i = 0; $i < $ost; $i++) {
				array_push($identifiers, ['identifier' => $rows[$i + ($ten_pack * 10)]['name'], 'type' => 'keyword']);
			}
	}
	$body = array('campaign_id' => name2ASIN::$campaignId,
  		'identifiers' => $identifiers);
	$result = curlSend($headers, name2ASIN::$import_url, 1, $body);
	foreach($identifiers as $id) {
		pg_update($dbconn, "products", ['in_progress' => 1], ['name' => $id['identifier']]);
	}
}
function initiateSearch($headers) {
	$body = array('search_only_new' => TRUE, 'campaign_id' => name2ASIN::$campaignId);
	curlSend($headers, name2ASIN::$initiate_url, 1, $body);
}
function curlSend($headers, $url, $is_post, $body = null) {
	$curl = curl_init();
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
	curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
	curl_setopt($curl, CURLOPT_URL, $url);
    	curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
    	curl_setopt($curl, CURLOPT_HEADER, 0);
	if($is_post == 1) {
		curl_setopt($curl, CURLOPT_POST, 1);
		if(isset($body)) {
			$body_encode = json_encode($body);
			curl_setopt($curl, CURLOPT_POSTFIELDS, $body_encode);
		}
	}
	$result = curl_exec($curl);
	curl_close($curl);
	return $result;
}
?>
