<?php
$postgresql = "host=localhost dbname=bender user=bender password=bender";
while(TRUE) {
	//по идее здесь будет обработчик отзывов для одного конкретного товара, потому в конце можно провести проверку на наличие этого
	//товара в бд и установки значения is_parsed в 1
	$dbconn = pg_connect($postgresql) or die('Не удалось соединиться: ' . pg_last_error());
	$query = 'SELECT id_product, asin FROM products WHERE is_asined = 1 AND is_parced = 0';
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
			//echo $row['asin'] . 'asin' . $row['id_product'] . ';';
			$process = proc_open('python amazon_review_scraper.py ' . $row['asin'] . ' ' . $row['id_product'], $descriptorspec, $pipes);
			$data = file_get_contents('data.json');
			$data = json_decode($data);
			if($data[0] == 1) {
				$query = "UPDATE products SET is_parced = 1 WHERE id_product = " . $row['id_product'];
				$result = pg_query($dbconn, $query);
			}
		}
	}
	//для переформатирования даты ( по хорошему надо сделать сразу в питоне, но там какие то непонятки с update, так что пока так
	$query = 'SELECT id_review, id_site, review_posted_date FROM reviews WHERE is_tr$
        $result = pg_query($dbconn, $query);
        $rows = pg_fetch_all($result);
        if($rows) {
                foreach($rows as $row) {
                        $id = $row['id_review'];
                        if(strpos($row['review_posted_date'], " ") !== false) {
                                $elements = explode(" ", $row['review_posted_date']);
                        //$elements = explode(" ", "");
                        switch($elements[1]) {
                                case 'Jan': $elements[1] = "01";
                                        break;
                                case 'Feb': $elements[1] = "02";
                                        break;
                                case 'Mar': $elements[1] = "03";
                                        break;
                                case 'Apr': $elements[1] = "04";
                                        break;
                                case 'May': $elements[1] = "05";
                                        break;
                                case 'Jun': $elements[1] = "06";
                                        break;
                                case 'Jul': $elements[1] = "07";
                                        break;
                                case 'Aug': $elements[1] = "08";
                                        break;
                                case 'Sep': $elements[1] = "09";
                                        break;
                                case 'Oct': $elements[1] = "10";
                                        break;
                                case 'Nov': $elements[1] = "11";
                                        break;
                                case 'Dec': $elements[1] = "12";
                                        break;
                        }
                                $correct_date = $elements[0] . "." . $elements[1] . "." $
                                $cd = explode(".", $correct_date);
                                $correct_date = $cd[0] . "." . $cd[1] . "." . $cd[2];
                                //echo $correct_date;
                                $query = "UPDATE reviews SET review_posted_date = '" . $$
                                $result = pg_query($dbconn, $query);
                        }
                }
        }
	pg_close($dbconn);
	sleep(3600);
}

?>
