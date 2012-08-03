#FQLで関連する複数クエリを実行する

FQLではJOINが使えないため、複数のテーブルが関係するデータを扱うには、複数クエリに分けるケースが多いです。ただ、APIのコール回数はなるべく減らさないと、スピードの点で不利になってしまいます。

ここでは、次のポイントを押さえて、イベント情報と出席者リストを取得する例を紹介します。

0. クエリの発行回数は1回に
0. 直前のクエリの結果を再利用
0. イベント情報に出席者情報を埋め込んでJSONで出力

##単数クエリ

通常のFQLは、次のように発行します。

```php
<?php
$result = $facebook->api(array(
	'method' => 'fql.query',
	'query' => 'SELECT ...',
));
```

ひとつのFQLで、1回APIをコールしてしまうので、PHP側から複数回呼出すと非常に時間がかかるのが難点。特に、一回目のFQLの結果の件数だけ、再度FQLを実行するような実装は避けるべきです。

(後述の例で考えるなら、イベント5件を取得後、それぞれから単数クエリで出席者情報を取得すると、少なくとも6回のAPIコールが必要です)

##複数クエリ

それでは、どのようにするのが望ましいでしょうか? [Facebook PHP SDK](https://github.com/facebook/facebook-php-sdk)には、複数クエリを発行する機能が用意されています。次のように、実行することで...

```php
<?php
$results = $facebook->api(array(
	'method' => 'fql.multiquery',
	'queries' => array(
		'query1' => 'SELECT ..(省略)..',
		'query2' => 'SELECT ..(省略)..',
	),
));
```

複数のクエリの実行結果が、配列として返ってきます。

```php
<?php
array(
	array(
		'name' => 'query1',
		'fql_result_set' => array(
			..(省略)..
		),
	),
	array(
		'name' => 'query2',
		'fql_result_set' => array(
			..(省略)..
		),
	),
)
```

##クエリの再利用

最初のクエリの結果に関係する項目だけ探したい場合、「#query1」のような記述を使うと、簡略化できます。次の例では、query2では、query1の結果をテーブルとみなして、そこからユーザID(uid)の一覧を取り出します。

```php
<?php
$results = $facebook->api(array(
	'method' => 'fql.multiquery',
	'queries' => array(
		'query1' => 'SELECT ..(省略)..',
		'query2' => 'SELECT ..(省略).. FROM user WHERE uid IN (SELECT FROM #query1)',
	),
));
```

##イベント情報と出席者リストを取得する例

以上ふまえて、実際に[下北沢オープンソースCafe](http://www.osscafe.net/)で使われているコードを見てみましょう。処理の流れを箇条書きにしておきます。詳しくは、ソースコード内のコメント参照。

0. Facebookクラスのインスタンスを作成
0. イベント(event)・関係(event_member)・ユーザ(user)それぞれのFQLを構築
0. APIを呼出す
0. 各結果を添字配列に格納しなおす
0. 各イベント情報に、出席者情報を付加
0. 配列をJSON形式で出力

```php
<?php
	$facebook = new Facebook(array(
		'appId'=>$_SERVER['FACEBOOK_APPID'],
		'secret'=>$_SERVER['FACEBOOK_SECRET'],
	));
	
	$page_id = 131130253626713;//下北沢オープンソースCafeのID
	$yesterday = time()-60*60*12;//12時間前を基準に
	$fql = array();

	//イベント情報を取得するクエリ
	$fql['events'] = <<<________FQL
		SELECT description, eid, name, pic, pic_big, start_time
		FROM event
		WHERE
			eid in (
				SELECT eid 
				FROM event_member 
				WHERE uid = $page_id AND $yesterday < start_time)
			AND privacy = 'OPEN'
		ORDER BY start_time
		LIMIT 5
________FQL;

	//イベントと出席者の関係(event_member)を取得するクエリ
	$fql['map'] = <<<________FQL
		SELECT eid, uid
		FROM event_member
		WHERE
			rsvp_status IN ("attending", "unsure") AND
			eid in (SELECT eid FROM #events)
________FQL;

	//出席者情報を取得するクエリ
	$fql['attendees'] = <<<________FQL
		SELECT uid, pic_square, profile_url
		FROM user
		WHERE uid in (SELECT uid FROM #map)
________FQL;

	//Facebook SDK を使って、複数クエリを発行
	$results = $facebook->api(array(
		'method'=>'fql.multiquery',
		'queries'=>$fql,
	));

	$map = array();
	$events = array();
	$attendees = array();

	//$results内は添字無しの配列なので、下記のように処理する必要あり。
	foreach ($results as $result)
		switch ($result['name']) {
			case 'map':
				$map = $result['fql_result_set'];
				break;
			case 'attendees':
				foreach ($result['fql_result_set'] as $row)
					$attendees[$row['uid']] = $row;
				break;
			case 'events':
				foreach ($result['fql_result_set'] as $row)
					$events[$row['eid']] = $row;
				break;
		}

	//$mapに従って、各イベントに出席者(attendees)情報を加える
	foreach ($map as $m){
		$uid = $m['uid']; $eid = $m['eid'];
		if (!isset($events[$eid]['attendees']))
			$events[$eid]['attendees'] = array();
		$events[$eid]['attendees'][] = $attendees[$uid];
	}
	
	//JSON形式で出力
	echo json_encode($events);
```