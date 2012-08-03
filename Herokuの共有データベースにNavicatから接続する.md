Herokuの共有データベースにNavicatから接続する

Herokuの場合、通常のホスティングサービスのつもりで、データベースにアクセスしようとしてもうまくいきません。Herokuの標準DBはPostgreSQLですので、接続には

* DBサーバのURL
* データベース名
* ユーザ
* パスワード

などが必要になります。残念ながら、コントロールパネルに親切に書かれていたりはしないので、herokuコマンドで調べたり…が必要です。

## DBの有無をチェック

まず、WEBアプリの置かれたローカルフォルダに移動しましょう。

```
> cd ~/heroku/yourapp
```

```heroku config```コマンドで、データベースの有無をチェックします。

```
> heroku config

=== Config Vars for zabuton
YOUR_SOME_VAR: (環境変数を定義済みの場合、その変数の内容)
```

もし、Config Varsに```DATABASE_URL```が表示されていればそのま使えます。次項を飛ばして「DBに接続する」に進んで下さい。

## DBを作成する

しつこいようですが、Herokuで標準で使えるデータベースはPostgresになります。(MySQLではありません!)

* Shared Database
* Dedicated Databases

の2パターンがあり、大きなアプリにならない限りは、共有(Shared)の方でOKです。Sharedにも2種類あって、

* 5MB : $0
* 20GB : $15/月

という、なんとも大雑把な料金体系になっています。つまり、無料でHerokuを使い続けるにはこの5MBをうまくやりくりする必要があるわけです。無料のDBについても、デフォルトでは使えるようになっていません。

```
> heroku addons:add shared-database
```

をコマンドラインから打ち込んで共有データベースのアドオンを追加します。もう一度、```heroku config```を呼出してみましょう。

```
> heroku config

=== Config Vars for zabuton
DATABASE_URL: postgres://uuuuu:xxxxx@yyyyy.amazonaws.com/zzzzz
SHARED_DATABASE_URL: postgres://uuuuu:xxxxx@yyyyy.amazonaws.com/zzzzz
YOUR_SOME_VAR: (環境変数を定義済みの場合、その変数の内容)
```

こんどは、```DATABASE_URL```が表示されたはずです。

## DBに接続する

共有データベース(Shared Database)の場合、残念ながら外部からの接続が認められていません。

1. pgAdminを使う
2. NavicatのHTTPトンネルを使う

のどちらかの方法を取ることになります。pgAdminについては、安藤さんがHeroku向けの改造をしているので、そちらをどうぞ :-)

* 「[Heroku用に魔改造したphpPgAdminをHerokuで動かす](http://blog.candycane.jp/archives/1489)」*via candycane.jp*

以下では、Navicatを使う方法を紹介します。

### Navicatの導入
[Navicat](http://www.navicat.com/)はデータベース管理アプリの老舗で、MySQLやOracleのほか[Postgres向けのもの](http://www.navicat.com/en/products/navicat_pgsql/pgsql_overview.html)が用意されています($79〜、Mac/Win/Linux対応)。なお、Mac版については、App Storeで¥400のEssential版もあります。ひとまず使う分にはこれで十分なのでお勧めです。

* [Navicat Essential for PostgreSQL](http://itunes.apple.com/jp/app/navicat-essentials-for-postgresql/id466725643?l=en&mt=12) *Mac App Store*

### 接続パラメータ

さきほど調べた、```DATABASE_URL```の値に接続情報が埋め込まれています。

* postgres://uuuuu:xxxxx@yyyyy.amazonaws.com/zzzzz

のような形式になっているはずです。

* postgres://**ユーザ名**:**パスワード**@**DBサーバURL**/データベース名

上記の対応をみて、自分の環境を確認します。(下記は例)

* DBサーバのURL : yyyyy.amazonaws.com
* データベース名 : zzzzz
* ユーザ : uuuuu
* パスワード : xxxxx

### Navicatの設定

Navicatを起動して、メニューから New Connection を実行します。表示されたダイアログで設定するのは、GeneralとHTTPのタブです。

![Generalタブのスクリーンショット](http://cl.ly/2A1n1k463n1R130a081b/Connection%20Properties%20-%20PostgreSQL.png)

* Connection Name: 好きな名前に
* Host Name/IP Address: DBサーバのURL
* Port: 5432のまま
* Default Database: データベース名
* User Name: ユーザ
* Password: パスワード ※Save passwordにチェックを入れておくと接続時のパスワード入力を省略できます。


![HTTPタブのスクリーンショット](http://cl.ly/321S0S3f0C27292h043Y/Connection%20Properties%20-%20PostgreSQL.png)

* Use HTTP tunnel: チェックを入れる
* Tunnel URL: プロクシしてくれるPHPスクリプトを置くURL

ここまで、設定したら Save Tunnel Script As… のボタンをクリックして、Webアプリのローカルフォルダ内の適当な位置に保存します。※上記設定したTunnel URLと合わせておく必要があります。

トンネル用スクリプト(ntunnel_pgsql.php)を、Gitからコミットします。

その上で、ダイアログのTest Connectionボタンをクリックしてみましょう。Connection Successful と表示されたら大丈夫です。OKボタンをクリックして設定を保存しておきます。

### NavicatのGUIからDBにアクセス

保存済みの接続設定が、Navicatのメイン画面の左側に並んでいます。そこから先ほど作成したものをダブルクリックして開きます。ずらっと、他のDB含めて並びますが、その中から自分のDBを探し出してダブルクリックします。

以上でアクセス成功です。

![データベースを開いたところ](http://cl.ly/3j0L3f3a3K3S1M2p292T/Navicat%20Essentials%20for%20PostgreSQL1.png)



## Appendix 

### A - セキュリティ

トンネルファイルを置き続けるのは本番環境だとちょっと不安です。使わないときは、削除しておいた方が無難。

NavicatはBasic認証に対応しているので、トンネルファイルを置いたURLについて認証をかけておくのも手です。詳しくはNavicatのヘルプをどぞ。

あと、HTTPSでアクセするのも大事。独自ドメインの場合も、Piggybag SSLが https://myapp.herokuapp.com/path/to/ntunnel_pgsql.php といったアドレスで使えるはず。

### B - トラブルシューティング 

上手くいかない場合は、スクリプトを置いたパスの設定など確認して下さい。

1. ユーザ名、パスワードなどは合ってますか?
2. データベース名は変更されることがある(?)ようです。最新のconfigを確認して下さい。
3. Tunnel URLにブラウザからアクセスした際に、下記のような画面が表示されていますか?

![Tunnel URLにアクセスしたスクリーンショット](http://cl.ly/0u3X3V2A3u0N2m3G3V1l/Navicat%20HTTP%20Tunnel%20Tester.png)

### C - 大量に表示されるDBを自分のものだけに制限する

他のユーザのものも含めて、大量のDBが表示されるので、毎回自分のものを探し出すのは面倒です。接続のプロパティ設定のAdvancedタブで、Use Advanced Connections にチェックして、自分のDBだけチェックしておきましょう。これで、関係ないDBが一覧に出なくなります。

![Advancedタブのスクリーンショット](http://cl.ly/3D3Q1b0s3J17300A0I0x/Connection%20Properties%20-%20PostgreSQL1.png)