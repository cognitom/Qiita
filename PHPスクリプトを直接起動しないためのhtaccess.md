#PHPスクリプトを直接起動しないための.htaccess

シナリオとしては、拡張子がjsonのリクエストがあった場合に、一度index.phpを通す、という想定。index.phpはコントローラではなく、変換スクリプトなので直接アクセスを避けたいとします。(コントローラであっても、URLを統一するために隠蔽したいケースもありますね)

よく使われるのは、PHPで特定の定数定義がないと、受け付けない形。ただ、アクセスを制限すべき全ファイルに必要になり、あまりキレイに書けません。

ここでは、それは避けて.htaccessでなんとかしてみたいと思います。もっとスマートな書き方があれば、ぜひコメント下さい。

##方法A: FilesMatchを使う

多分、これがオススメ。ちょっと長いけど。

```.htaccess
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} -f
RewriteRule \.json$ index.php [QSA,L]

<FilesMatch "index.php">
Order Deny,Allow
Deny from All
Allow from env=REDIRECT_STATUS
</FilesMatch>
```

##方法B: RewriteCondでREDIRECT_STATUSをチェック

ときどき見かける方法。ただ、環境によって「200」じゃないことがあり、その場合動かず。

```.htaccess
RewriteEngine On
RewriteCond %{ENV:REDIRECT_STATUS} !=200
RewriteRule index.php - [F]
RewriteCond %{REQUEST_FILENAME} -f
RewriteRule \.json$ index.php [QSA,L]
```

##方法C: RewriteCondでREQUEST_URIをチェック

こちらも、サーバ次第。REQUEST_URIが途中のルール適用で書き変わるケースがあり、そうするとNG。(Apacheのドキュメントサイトによれば、SCRIPT_URIを使えば途中で書き変わらないよ、とあるのだけれどそちらも動かない?)

```.htaccess
RewriteEngine On
RewriteCond %{REQUEST_URI} index.php
RewriteRule ^ - [F]
RewriteCond %{REQUEST_FILENAME} -f
RewriteRule \.json$ index.php [QSA,L]
```

## 結論

nginxな昨今、Apacheで頑張るのもどうかと思いつつ、Herokuで動く方法を探しました。動いたのは、方法Aのみ。Aを使います。
