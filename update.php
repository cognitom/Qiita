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