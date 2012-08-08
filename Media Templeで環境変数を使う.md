#Media Templeで環境変数を使う

先日書いた「[SAKURAの共有サーバで環境変数を使う](http://qiita.com/items/e74cfee1af8ef16278a5)」のMedia Temple (mt) 版です。といっても、Media Templeの場合は.htaccess内で設定できるのであまり面倒はありません。下記のように、書けば良いだけ。(変数名は任意です)

```.htaccess
SetEnv HTTP_DB_NAME "db000000_your_db_name"
SetEnv HTTP_DB_USER "db000000_yourname"
SetEnv HTTP_DB_PASS "*********"
```

ただし、変数名を`HTTP_`で始めていることに注目して下さい。セキュリティ上の理由から、`HTTP_`のつかない変数は受け付けないとのこと。

##置き場所には注意

ただ、htmlディレクトリ内に含めてしまうと、Gitリポジトリに入ってしまうため、ひとつ上位のディレクトリに入れます。

* /home/000000/domains/
	* yourdomain.com/
		* html
			* .htaccess : *ここはGitのリポジトリ内*
		* cgi
		* .htaccess : *ここならOK*

##変数の呼出し方

通常通りこんな感じで。(PHPの場合)

```php
$dbname = $_ENV['HTTP_DB_NAME'];//db000000_your_db_name
$dbuser = $_ENV['HTTP_DB_USER'];//db000000_yourname
$dbpass = $_ENV['HTTP_DB_PASS'];//*********
```

なお、データベースサーバのURIはMedia Temple側で環境変数として用意してくれています。`DATABASE_SERVER`を使いましょう。

```php
$dbhost = $_ENV['DATABASE_SERVER'];//internal-db.s000000.gridserver.com
```

