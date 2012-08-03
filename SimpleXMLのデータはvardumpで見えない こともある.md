SimpleXMLのデータはvar_dumpで見えない! (こともある)

SimpleXMLでATOMをパースしようとして、ちょっと手こずったのでメモ。(PHPでXML扱うのが久しぶり)

* SimpleXMLのデータはvar_dumpなどで、見えないことがある。(←重要)
* foreach じゃないと言うことを聞かないかも。
* 解析したデータを使いたい場合、文字列にキャストすること。

```php
<?php
//フィードを解析して、記事データを取得する関数
function get_articles($url, $max = 5){
	$atom = simplexml_load_file($url);
	$articles = array();
	$n = 0;
	foreach ($atom->entry as $item){
		$attr = $item->link->attributes();
		$articles[] = array(
			'title' => (string)$item->title,
			'url' => (string)$attr['href'],
			'date' => (string)$item->published,
		);
		if ($max <= ++$n) break;
	}
	return $articles;
}

//使用例 : JSONで出力
$feed_url = 'http://somewhere/atom.xml';//フィードのURL
$articles = get_articles($feed_url);//記事データを取得
header('Cache-Control: public,max-age=3600');
echo json_encode($articles);
```

## おまけ
PHPの命名規則から逸脱するプロパティ名は、

```
$xml->{'sample-prop'}
```

のようにすればOK。