<?php
include_once "simple_html_dom.php";
include_once "connection.php";
use connection\Connect;

header('Content-Type: text/html; charset=utf-8');

function product_handler($input)
{

	$postgresql = "host=localhost dbname=bender user=bender password=bender";
	$current_product = preg_replace("/[а-яёА-ЯЁ]+.{1,3}? ?/u", "", $input['item_name']);
	$current_product = rtrim($text);
	$dbconn = pg_connect(Connect::$postgresql) or die();
	$query = "SELECT * FROM reviews as r INNER JOIN reviews_products as rp ON r.id_review = rp.id_review INNER JOIN products AS p ON rp.id_product = p.id_product WHERE p.name LIKE '%" . $current_product . "%' and r.is_translated = TRUE";
	$result = pg_query($dbconn, $query);
	$row = pg_fetch_all($result);
	$html_content = "<div class='container'> No reviews found or translated now! </div>";
	if($row) {
		$html_content = "<style type = 'text/css'>
.rating {
    float:left;
}
.rating:not(:checked) > input {
    position:absolute;
    top:-9999px;
}

.rating:not(:checked) > label {
    float:right;
    width:1em;
    padding:0 .1em;
    overflow:hidden;
    white-space:nowrap;
    font-size:200%;
    line-height:1.2;
    color: gold;
    text-shadow:1px 1px #bbb, 2px 2px #666, .1em .1em .2em rgba(0,0,0,.5);
}

.rating:not(:checked) > label:before {
    content: '★ ';
}

.rating > input:checked ~ label {
    color: #f70;
    text-shadow:1px 1px #c60, 2px 2px #940, .1em .1em .2em rgba(0,0,0,.5);
}

.full {
    float:left;
}
.full:not(:checked) > input {
    position:absolute;
    top:-9999px;
    clip:rect(0,0,0,0);
}

.full:not(:checked) > label {
    float:right;
    width:1em;
    padding:0 .1em;
    overflow:hidden;
    white-space:nowrap;
    font-size:200%;
    line-height:1.2;
    color: white;
    text-shadow:1px 1px #bbb, 2px 2px #666, .1em .1em .2em rgba(0,0,0,.5);
}

.full:not(:checked) > label:before {
    content: '★ ';
}

.full > input:checked ~ label {
    color: #f70;
    text-shadow:1px 1px #c60, 2px 2px #940, .1em .1em .2em rgba(0,0,0,.5);
}

				</style>
				<div class='container'
				style = 'overflow: auto; overflow-x: hidden; width:100%; height: 500px; border: 0px solid green; border-radius: 20px;
				font: 12pt/18pt serif;'>
				";
		foreach($row as $element)
		{
			$star_count = (float)$element['review_rating'];
			$half_star = $star_count - floor($star_count);
			$white_stars = 5 - $star_count;
			$rating_html = "<div class = 'rating-2-stars'><div class = 'rating'>";
			for( $i = 0, $j = 5; $i < $star_count; $i++, $j--) {
				$rating_html .="<input type='radio' id='star" .$j . "' name='rating' value=". $j ."/><label for='star" .$j . "'></label>";
			}
			$rating_html .= "</div><div class = 'full'>";
			for($i = 0, $j = $white_stars; $i < $white_stars; $i++, $j--) {
				$rating_html .= "<input type='radio' id='star" .$j . "' name='rating' value=". $j ."/><label for='star" .$j . "'></label>";
			}
			$rating_html .= "</div></div><br>";
			$html_content .= "<div class='single_review' style = 'padding: 10px;'>
					<div>
						<span class=''>" . $element['review_posted_date'] . "</span><br>" . $rating_html . "
					</div>
					<br><div style = 'text-align: justify; width: 90%;'>
					<p><span>". $element['review_translated_text'] . "</span></div>
					<br><div style = 'display: inline-block; width: 90%'>
					<div class = 'review-caption' style = 'width: 50%; display: inline-block;'>
						<span style = ' text-align: right; color: gray;'>Отзыв с Amazon </span>
					</div>
					<div style = 'text-align: right; display: inline-block; width: 50%; font-weight: bolder;'>
					</div>
					</div>
					</div>
				";
		}
		$html_content .= "</div>
				<div style = 'display: inline-block; width: 90%'>
                                        <div class = 'review-caption' style = 'width: 50%; display: inline-block;'>
                                        </div>
					<div style = 'float:right; text-align: right; display: inline-block; width: 50%; font: bolder 14pt/18pt serif;'>
                                                Getreviews
                                         </div>
				</div>
				<hr>";
	}
	echo $html_content;
	//not right query, for example Mi band and Mi band 2 will be the same for %Mi band%
	$query = "SELECT * FROM products WHERE name LIKE '%" . $current_product . "%'";
	$result = pg_query($dbconn, $query);
	$row = pg_fetch_all($result);
	if(!$row)
                {
			pg_insert($dbconn, "products", ['is_parced' => 0, 'name' => $current_product, 'is_asined' => 0, 'in_progress' => 0]);
                        $product_result = pg_select($dbconn, "products", ['name' => $current_product]);
                }
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
			pg_insert($dbconn, "products", ['is_parced' => 0, 'name' => $current_product_name, 'is_asined' => 0, 'in_progress' => 0]);
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
?>
