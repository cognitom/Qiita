#Qiitaの投稿をGitHubにバックアップ

最近、何か思いつくとQiitaに書いてばかりいます。やっぱり、KobitoのMarkdown + ローカル編集は楽ちんですね。

Qiita + Kobito への依存度が上がるにつれて、気になるのがバックアップ。そこで、Qiitaへの投稿をサクッとGitHubにバックアップするスクリプトを作りました。(for Mac)

* 例: [cognitom/Qiita](https://github.com/cognitom/Qiita) *←こんな感じにGitHubにコミットされます。*

##はじめに (前提条件)

* Kobitoを利用して投稿していること。
* PHPのコマンドライン版が使えること。(シェル版もそのうち公開されるかも)
* 例外処理とか書いてません。;-)

##使い方

0. まず、自分のGitHubに新規にリポジトリを作成します。
	* "Repository Name"を「Qiita」に。(別の名称でも構わないですが、以降適宜読み替えて下さい)
	* "Initialize this repository with a README"のオプションをチェック。
	* "Create Repository"をクリック。
0. Macにクローン。
	* "Clone in Mac"ボタンをクリック。
	* ~/github/Qiita にクローン。
0. 次のコマンドを実行。

```bash
$ cd ~/github/Qiita
$ curl -s https://raw.github.com/cognitom/Qiita/tool/update.php | php
```

※実行そのものも自動化したい場合は、dailyとかでcronを回して下さい。

##注意
コマンドを実行すると、カレントディレクトリの内容を一旦削除して、KobitoのデータベースからMarkdownファイルを再構築します。なので、必ずリポジトリのディレクトリに移動してから、実行すること。

##スクリプト解説

コードは、[GitHub](https://github.com/cognitom/Qiita/tree/tool)にも置いてあります。処理の流れはこんな感じ。

0. KobitoのデータはSQLiteに保存されているので、その内容をSQLで取得。
0. 1レコードずつ、ファイルに保存
0. 目次ファイルを保存 (README.md)
0. GitHubに自動コミット

なお、ここではQiitaに公開済みの投稿だけをバックアップするようにしました。(「限定共有」も除く)

下記ソースのコメントも参照。

```update.php
<?php
//各種設定
$dir = getcwd();
$dbfile = getenv('HOME') . '/Library/Kobito/Kobito.db';
$message = 'Automatic Update';

//ファイル名のサニタイズ関数
function sanitize($string) {
	return trim(str_replace(str_split("~`!@#$%^&*=+{}\\|;:\"',<.>/?"), '', strip_tags($string)));
}

//ローカルリポジトリのファイルを一度クリア
$files = glob("$dir/*");
foreach ($files as $file)
	if (is_file($file) && 'README.md' != basename($file))
		unlink($file);

//Kobitoからデータを取得 (SQLite)
$db = new SQLite3($dbfile);
$query = "select ZUID, ZTITLE, ZRAW_BODY, ZURL from ZITEM where ZUID is not null and ZPRIVATE = 0 ORDER BY ZPOSTED_AT DESC";
$results = $db->query($query);

//1レコードずつファイルに保存
$md = "Posts on Qiita\n=====\n\n";
echo "Collecting your posts from Kobito...\n";
while ($row = $results->fetchArray()){
	$title = sanitize($row['ZTITLE']);
	echo "* $title\n";
	$md .= "* [{$row['ZTITLE']}]({$row['ZURL']} \"see on Qiita\")\n";
	file_put_contents("$dir/$title.md", $row['ZRAW_BODY']);
}

//目次がわりのREADME.mdを作成
file_put_contents("$dir/README.md", $md);

//GitHubにコミット
echo "\nConnecting to GitHub...\n";
chdir($dir);
exec("git add -A");
exec("git commit -a -m '$message'");
exec("git push origin master");
```

##Appendix

### Q. Gitでユーザ名・パスワードを聞かれてめんどくさい
A. GitのモードがHTTPになっているのかも。次のコマンドでSSHに切り替えると、いちいち聞かれなくなるはず。

```bash
git config remote.origin.url git@github.com:your_user_name/repository_name.git
```

###Q. 毎回curlでダウンロードするのもどうかと...
A. 適当なところに[ダウンロード](https://raw.github.com/cognitom/Qiita/tool/update.php)して使って下さい。ダウンロードしたら、↓こんな感じで。

```bash
$ cd ~/github/Qiita
$ php ./update.php
```

###Q. Gist連携でいいじゃん
A. オンラインからの編集だと、その機能があるんですよね。最近気付きました。Kobitoからはその機能使えないのと、文章のバックアップも必要なので。GitHubのリポジトリの方がまとまってる感もありますし。

###Q. 投稿数が増えて来るとちょっとつらい?
A. 差分をとるような方向にプログラム修正した方が良いかも。コメント、フォーク歓迎です。