<?php

	ini_set('display_errors', 1);
	ini_set('display_startup_errors', 1);
	error_reporting(E_ALL ^ E_DEPRECATED);
	
	require_once './vendor/autoload.php';
	use Symfony\Component\DomCrawler\Crawler;
	use GuzzleHttp\Client;
	use GuzzleHttp\Pool;
	use GuzzleHttp\Psr7\Request;

	$search_query_text = isset($_POST['q']) ? $_POST['q'] : '';
	if ($search_query_text !== null) {
		$trimmed_string = trim($search_query_text);
		$formatted_string = str_replace(' ', '+', $trimmed_string);
	} else {
		$formatted_string = '';
	}

	$search_results = [
		"https://www.ebay.com/sch/i.html?_from=R40&_nkw={$formatted_string}&_sacat=0&rt=nc&_ipg=240&_pgn=1",
		"https://www.ebay.com/sch/i.html?_from=R40&_nkw={$formatted_string}&_sacat=0&rt=nc&_ipg=240&_pgn=2",
		"https://www.ebay.com/sch/i.html?_from=R40&_nkw={$formatted_string}&_sacat=0&rt=nc&_ipg=240&_pgn=3",
		"https://www.ebay.com/sch/i.html?_from=R40&_nkw={$formatted_string}&_sacat=0&rt=nc&_ipg=240&_pgn=4",
		"https://www.ebay.com/sch/i.html?_from=R40&_nkw={$formatted_string}&_sacat=0&rt=nc&_ipg=240&_pgn=5",
		"https://www.ebay.com/sch/i.html?_from=R40&_nkw={$formatted_string}&_sacat=0&rt=nc&_ipg=240&_pgn=6" 
	];

	$all_links = array();
	$client = new Client();
	
	$pool = new Pool($client, array_map(function ($url) {
		return new Request('GET', $url);
	}, $search_results), [
		'concurrency' => 6,
		'fulfilled' => function ($response, $index) use ($client, &$all_data) {
			$html = $response->getBody()->getContents();
			$crawler = new Crawler($html);
			$item_urls = $crawler->filter('.s-item__wrapper')->each(function ($node) use (&$all_data) {
			$url = $node->filter('a.s-item__link')->attr('href');
			$title_ = str_replace("Opens in a new window or tab", "", $node->filter('a.s-item__link')->text());
			$title = str_replace("New Listing", "", $title_);
			$price = $node->filter('span.s-item__price')->text();
			$img_node = $node->filter('.s-item__image-wrapper.image-treatment img');
			if ($img_node->count() > 0) {
				$img_url = $img_node->attr('src');
			} else {
				$img_url = '';
			}
			$all_data[$url] = array(
				'url' => $url,
				'title' => $title,
				'price' => $price,
				'img_url' => $img_url,
				'location' => '',
			);
			return $url;
		});
		
			$item_pool = new Pool($client, array_map(function ($url) {
				return new Request('GET', $url);
			}, $item_urls), [
				'concurrency' => 6,
				'fulfilled' => function ($response, $index) use (&$all_data, $item_urls) {
					$html = $response->getBody()->getContents();
					$doc = new DOMDocument();
					@$doc->loadHTML($html);
					$xpath = new DOMXPath($doc);
					$nodes = $xpath->query('//span[contains(@class, "ux-textspans ux-textspans--SECONDARY") and contains(., "Located in:")]');
					if ($nodes->length > 0) {
						$all_data[$item_urls[$index]]['location'] = str_replace("Located in: ", "", $nodes->item(0)->nodeValue);
					}
				},
			]);
			$item_pool->promise()->wait();
			
		},
	]);
	$pool->promise()->wait();
	
header('Content-Type: application/json');
echo json_encode($all_data);
?>