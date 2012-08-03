#CSS内の画像埋込で高速化(Data URI)

スタイルシートの中で画像を多数呼出していると、HTTPリクエストが大量発生してページの読み込みが遅くなります。このような場合、CSS Spriteを使って回避することが一般的ですが、Data URIを使うと運用はもっと簡単です。

![Reduce HTTP Request with Data URI](http://cl.ly/image/0o2I313y3T43/cssembed.png)

## CSSファイルへの埋込

例えばOSSCafeの場合、[サイトのCSS](http://www.osscafe.net/style/images.css)内で16ほどの画像ファイルを読込んでいます。

```style/images.css
body { background-image:url(images/body.png) }
body>header { background-image: url(images/header.png) }
body>header div.center>h1 { background-image:url(images/logo.png) }
...(略)
```

このCSSファイル内の画像ファイルを、Data URIに置き換えると、

```style/images.css?inline
body { background-image:url(data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAEAAAABACAYAAACqaXHeAAAABHNCSVQICAgIfAhkiAAAAAlwSFlzAAAK8AAACvABQqw0mAAAABx0RVh0U29mdHdhcmUAQWRvYmUgRmlyZXdvcmtzIENTM5jWRgMAAAQRdEVYdFhNTDpjb20uYWRvYmUueG1wADw/eHBhY2tldCBiZWdpbj0iICAgIiBpZD0iVzVNME1wQ2VoaUh6cmVTek5UY3prYzlkIj8+Cjx4OnhtcG1ldGEgeG1sbnM6eD0iYWRvYmU6bnM6bWV0YS8iIHg6eG1wdGs9IkFkb2JlIFhNUCBDb3JlIDQuMS1jMDM0IDQ2LjI3Mjk3NiwgU2F0IEphbiAyNyAyMDA3IDIyOjExOjQxICAgICAgICAiPgogICA8cmRmOlJE
...(略)
```

のように、画像部分がBASE64エンコードで埋め込まれた形になります。

## 埋め込みの自動化 ("?inline"を付けたときだけ)

ただ、これを手動で実行するのは面倒です。ここでは、CSSファイルの読み込み時に["?inline"とつけた場合](http://www.osscafe.net/style/images.css?inline)のみ、自動的に埋込むことにします。HTML内の表記としてはこんな感じ。

```index.html
<link rel="stylesheet" type="text/css" href="images.css?inline" />
```

ちなみに、想定しているファイル構成は次の通り。

* index.html
* style/
	* **.htaccess**
	* **images**/ - *CSSから参照する画像を格納するディレクトリ*
	* **images.css** - *画像指定のみのCSS*
	* **other.css** - *画像指定以外のCSS*
	* **embed-images.php** - *自動変換スクリプト*

.htaccessにてmod_rewriteを使って、"?inline"をつけた場合だけ、PHPのスクリプトを介するよう設定します。後半の```FileMatch```の部分は、スクリプトの直接起動を制限するためのものです。

```style/.htaccess
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} -f
RewriteCond %{QUERY_STRING} inline
RewriteRule \.css$ embed-images.php [QSA,L]

<FilesMatch "embed-images.php">
Order Deny,Allow
Deny from All
Allow from env=REDIRECT_STATUS
</FilesMatch>
```

##Data-URIに置換え

CSSファイル内の画像ファイルを、自動的にData URIに置き換えるPHPスクリプト(embed-images.php)を作成します。といっても、該当するCSSファイルを読込んで、画像ファイル名の部分をData URIに置きかえているだけ。

```style/embed-images.php
<?php
$mime_types = array('png'=>'image/png', 'jpg'=>'image/jpeg', 'gif'=>'image/gif');
header('Content-type: text/css');
echo preg_replace_callback(
	'/url\((.*?\.(' . implode('|', array_keys($mime_types)) . '))\)/im',
	function ($matches) use ($mime_types) {
		$url = trim($matches[1], '\'\"');
		return file_exists($url)
			? 'url(data:' . $mime_types[strtolower($matches[2])] . ';base64,' . base64_encode(file_get_contents($url)) . ')'
			: $matches[0];
	},
	file_get_contents($_SERVER['DOCUMENT_ROOT'] . str_replace('?inline', '', $_SERVER['REQUEST_URI']))
);
```

##メリット / デメリット
冒頭に書いたように、HTTPリクエストが減って、ページの読み込みが高速になるはずです。このような構成を取ることで、運用面でも次のメリットがあります。

* ?inline を外せば、通常のCSSにすぐ戻せる
* HTTPサーバがローカルに無い場合でも、元のCSSが表示される

後者は、デザイナがCSSを変更する場合に、ローカルファイルとしてそのまま扱える点で有利です。

Data URIにした場合、BASE64エンコードのため、画像のバイナリデータと比べてファイルサイズが大きくなります。このこと自体は、デメリットなのですが、リクエスト数を減らす方がどちらかというと重要です。また、HTTPサーバからの送出時にgzip圧縮するのであれば、サイズはバイナリとほぼ変わらなくなります。

## Appendix

### ソースのダウンロード

GitHubにも上げておきました。https://github.com/cognitom/wagon

### IEどうするのん?

IEは、バージョン8以降でData URIに対応です。なので、IE7以下をケアする場合は、IEのバージョンで条件分岐。IE7以下で"?inline"を外せばOKです。このあたり、自由にできるのはCompassの"inline-image"より使いやすいところ。

```index.html
<!--[if lte IE 7]><link rel="stylesheet" type="text/css" href="images.css" /><![endif]-->
<!--[if gte IE 8]><!--><link rel="stylesheet" type="text/css" href="images.css?inline" /><!--<![endif]-->
```


### 他の方法

Compass/SASS を使っているのであれば、```inline-image```という機能を使って、同様のことが属性単位で可能です。[詳しくはこちら](http://compass-style.org/reference/compass/helpers/inline-data/#inline-image)

```sass
body
	background-image:inline-image(images/body.png)
```