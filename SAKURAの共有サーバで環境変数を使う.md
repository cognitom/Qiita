#SAKURAの共有サーバで環境変数を使う

Gitに入れられない固有の値は「環境変数」に入れておく、というのがPaaSを使い慣れた人の共通認識だと思います。でも、[SAKURAの共有サーバ](http://www.sakura.ne.jp/rentalserver/)で同じことをやろうとすると、Apacheの[suEXEC](http://httpd.apache.org/docs/2.2/suexec.html)が有効になっているらしく、上手くいきません。

(結論から言うと、環境変数を取り出すのは色々難があるのですが、既存のコードにちょっと細工して、それっぽく使う方法を紹介します)

##普通のやり方
Apacheの設定経由で、```SetEnv```を使うのが一般的でしょうか。

```httpd.conf
SetEnv DB_HOST localhost
SetEnv DB_NAME my_db_name
SetEnv DB_USER db_admin
SetEnv DB_PASS *******
```

※もちろん、単純にシェルから環境変数を入れておく手もあります。

##でも...
前述の通りSAKURA(の共有サーバ)では...

* Apacheの設定ができない (※.htaccessではGitリポジトリに含まれてしまう)
* 設定で来たとしても、suEXECに阻まれる。

> suEXEC は、安全な環境変数のリスト (これらは設定時に作成されます) 内の変数として渡される安全な PATH 変数 (設定時に指定されます) を設定することで、 プロセスの環境変数をクリアします。
> *cf. [suEXECサポート](http://httpd.apache.org/docs/2.2/ja/suexec.html)*

##仕様がないので。
環境変数をエミュレートする方法を考えます。

まず、前提としてディレクトリ構成が次のようになっているとします。(太字は元々SAKURAで用意されているディレクトリ)

* **/home/username/**
	* **www**
		* yourwebsite.com/ : *virtual hostのルート*
			* index.php
			* evlfs.php
	* env_vars
		* yourwebsite.com : *環境変数がiniファイル形式で納められたテキストファイル*

SAKURAのデフォルトでは、**/home/username/www/**がドキュメントルートになっていますが、マルチドメインで使うケースが多い(と思う)ので、上記のようにwwwの下にディレクトリを置いています。

## iniファイル形式で値を書く

PHPには、iniファイル形式をパースする便利な関数があります。環境変数にセットしたい内容も、iniで書くことにしましょう。

```ini:yourwebsite.com
DB_HOST = localhost
DB_NAME = my_db_name
DB_USER = db_admin
DB_PASS = *******
```

これを、env_varsディレクトリ内に置きます。他ドメインでも利用する場合は、ファイル名をドメイン名にしてドメイン毎に用意します。

## $_SERVERにセット

後は、PHPからiniファイルを読込めばOKです。evlfs.phpファイルの内容は次のような感じに。

```evlfs.php
<?php
/**
 * Enviroment Variable Loader for SAKURA
 */
$path = "{$_SERVER['DOCUMENT_ROOT']}/../env_vars/{$_SERVER['HTTP_HOST']}";
if ('support@sakura.ad.jp' == $_SERVER['SERVER_ADMIN'] && file_exists($path))
	$_SERVER = array_merge($_SERVER, parse_ini_file($path));
```

* SAKURAのサーバ上で実行されているか、
* iniファイルが存在するか、

をチェックして、iniファイルの内容を$_SERVER配列に追加します。

最後、フロントコントローラ(index.php)から evlfs.php を読込みます。

```index.php
require_once 'evlfs.php';
```

これで、```$_SERVER['DB_HOST']```で'localhost'を取得できるようになりました。PaaS的な書き方をしてしまったコードも、この一行だけで無事動くようになるはず!
