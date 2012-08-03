#Hello Slim!

SlimはPHPで作られた比較的新しいフレームワークです。「マイクロフレームワーク」のひとつで、SynfonyやCakePHPなどのフルスタックのものとは異なり、非常にシンプルな構成になっています。機能も、フロントコントローラにフォーカスしているので、モデルやDBへのアクセスといった部分はごっそり省略されています。まさに「Slim」です。

* Slimのサイト - http://www.slimframework.com/
* ダウンロード - http://www.slimframework.com/install
* ソースコード - https://github.com/codeguy/Slim

## Let's start!

何はともあれ、まずはサンプルを作成してみましょう。

GitHubのmasterブランチから[Slimをダウンロード](https://github.com/codeguy/Slim/zipball/master)して、ZIPファイルを解凍します。ここでは、

* .htaccess
* js/
* css/
* php/
	* index.php
	* Slim/

みたいな感じで、ファイルを配置します。(配置の仕方に特に決まりはないので、各人の好みで変えてしまってOKです)

.htaccessで、ファイルが存在しない場合にすべてをphp/index.phpに渡すよう設定しています。※Webサーバの設定によっては、RewriteBaseの行をコメントアウトする必要があります。

```.htaccess
RewriteEngine On
# RewriteBase /
RewriteCond %{REQUEST_FILENAME} !-f
RewriteRule ^(.*)$ php/index.php [QSA,L]
```
それでは、下準備が完了したので、お決まりの「Hello World」をやってみましょう。php/index.phpに次のように書きます。

```php:php/index.php
<?php
require 'Slim/Slim.php'; //ライブラリの読み込み
$app = new Slim(); //インスタンスを初期化
$app->get('/hello/:world', function ($world) { //ルーティング設定
	echo "Hello $world! (GET version)";
});
$app->run(); //実行
```

ブラウザから、http://<ホスト名>/hello/slim にアクセスしてみましょう。画面に

> Hello slim! (GET version)

と表示されていたらOKです。

## Slimでルーティング

Slimでは、

```
$app->get('/path/to/:variable', some_function);
```

の形式でルーティングの設定が可能です。":variable"の部分は、関数に変数として渡されます。それぞれのメソッドに合わせて関数が用意されています。

* GETメソッド : $app->get()
* POSTメソッド : $app->post()
* PUTメソッド : $app->put()
* DELETEメソッド : $app->delete()

また、SlimはPHP5.3以降で無名関数を使って書くと非常に読みやすいコードになります。

```
$app->get('/path/to/:variable', function ($variable) {
	//ここに処理を書く
});
```

無名関数の内部で、Slimのインスタンス($app)にアクセスする必要がある場合は、次のように書く事もできます。

```
$app->get('/path/to/:variable', function ($variable) use ($app) {
	$name = $app->request()->params('name'); //リクエスト変数を取得
	//ここに処理を書く
});
```

(つづく)

この記事は、4/18(水)に開催された「軽量WEBフレームワーク祭り」の発表内容を整理したものです。

* イベントページ - https://www.facebook.com/events/268874156532536/
* Ustream録画 - http://www.ustream.tv/recorded/21935476