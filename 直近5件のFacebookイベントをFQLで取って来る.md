直近5件のFacebookイベントをFQLで取って来る

Graph APIだと取れないものがあったりするので、FQLを使うサンプル。

```php
<?php
function get_events ($fid, $appid, $secret, $limit = 5) {
	$fb = new Facebook(array('appId'=>$appid, 'secret'=>$secret));
	$yesterday = time()-60*60*24;//24時間前のタイムスタンプ
	$fql = <<<____FQL
		SELECT creator, description, eid, end_time, location, name, pic, pic_big, pic_small, start_time
		FROM event
		WHERE
			eid in (
				SELECT eid 
				FROM event_member 
				WHERE uid = $fid AND $yesterday < start_time)
			AND privacy = 'OPEN'
		ORDER BY start_time ASC
		LIMIT $limit
____FQL;
	return $fb->api(array('method'=>'fql.query','query'=>$fql));
}
```

実動例はこちら。

* 下北沢オープンソースCafeのイベント一覧 - http://www.osscafe.net/ja/meetups.html