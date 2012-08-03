#ISO8601形式の時刻をUNIXタイムスタンプに

FacebookのAPI変更で、FQLの日時フィールドの戻り値がISO8601になってしまいました。(2012/7/5現在)

というわけで、表題のことをやる方法です。簡単なのは、strtotimeに渡すだけ。

```
<?php
$dt = '2012-07-05T22:09:28+09:00';
$ts = strtotime($dt);
echo $ts; // 1341493768 と表示
```

[関数のリファレンス](http://jp2.php.net/manual/ja/function.strtotime.php)に言及がないので、ちょっとびくびくしてしまいますが、[こちら](http://jp2.php.net/manual/ja/datetime.formats.compound.php)を見ると、大丈夫そうです。

より明示的に変換するのであれば、

```
<?php
$dt = '2012-07-05T22:09:28+09:00';
$ts = DateTime::createFromFormat(DateTime::ISO8601, $dt)->getTimestamp();
echo $ts; // 1341493768 と表示
````

と書くこともできます。

## 付記
FacebookのAPIについて、Facebookアプリによって戻り値がISO8601だったり、UNIXタイムスタンプだったりするようです。下記のようにしておくと無難ですね。

```
<?php
$dt = /* FacebookからFQLで日時フィールドを取得 */;
$ts = preg_match('/^\d+$/', $dt)
	? $dt - 0
	: strtotime($dt);
echo $ts; // 1341493768 と表示
```

別解↓ ※「@」が先頭に付くと、UNIXタイムスタンプとして解釈されます。

```
<?php
$dt = /* FacebookからFQLで日時フィールドを取得 */;
$ts = strtotime((preg_match('/^\d+$/', $dt) ? '@' : '') . $dt);
echo $ts; // 1341493768 と表示
```