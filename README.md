Tool for Qiita
=====

Qiitaへの投稿をGitHubにバックアップするスクリプト。(for Mac)

##はじめに

* Kobitoを利用して投稿しているものとします。
* PHPのコマンドライン版が使えること。(シェル版もそのうち公開されるかも)

##使い方

0. まず、自分のGitHubに新規にリポジトリを作成します。
	* "Repository Name"を「Qiita」に。(別の名称でも構わないですが、以降適宜読み替えて下さい)
	* "Initialize this repository with a README"のオプションをチェック。
	* "Create Repository"をクリック。
0. Macにクローン。
	* "Clone in Mac"ボタンをクリック。
0. 次のコマンドを実行。

```sh
$ cd ~/github/Qiita
$ curl -s https://raw.github.com/cognitom/Qiita/tool/update.php | php
```

##Appendix

### Q. Gitでユーザ名・パスワードを聞かれてめんどくさい
A. GitのモードがHTTPになっているのかも。次のコマンドでSSHに切り替えると、いちいち聞かれなくなるはず。

```
git config remote.origin.url git@github.com:your_user_name/repository_name.git
```

###Q. 毎回curlでダウンロードするのもどうかと...
A. 適当なところに[ダウンロード](https://raw.github.com/cognitom/Qiita/tool/update.php)して使って下さい。ダウンロードしたら、↓こんな感じで。

```
$ php ./update.php
```