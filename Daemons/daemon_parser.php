<?php
$postgresql = "host=localhost dbname=bender user=bender password=bender";
while(TRUE) {
	//по идее здесь будет обработчик отзывов для одного конкретного товара, потому в конце можно провести проверку на наличие этого
	//товара в бд и установки значения is_parsed в 1
	$dbconn = pg_connect($postgresql) or die('Не удалось соединиться: ' . pg_last_error());
	$query = 'SELECT asin FROM products WHERE is_asined = 1';
	$result = pg_query($dbconn, $query);
	$rows = pg_fetch_all($result);
	if($rows) {
		foreach($rows as $row) {
			//Отработанный вариант по подключению питоновского скрипта
			$descriptorspec = array(
			   0 => array("pipe", "r"),  // stdin - канал, из которого дочерний процесс будет читать
			   1 => array("pipe", "w"),  // stdout - канал, в который дочерний процесс будет записывать 
			   //2 => array("file", "/var/www/html/parser/data.json", "a") // stderr - файл для записи
			);
			$process = proc_open('python amazon_review_scraper.py ' . $row['asin'], $descriptorspec, $pipes);
		}
	}
	pg_close($dbconn);
	sleep(3600);
}

?>
