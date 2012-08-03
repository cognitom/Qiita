#FQLで非indexableカラムを検索条件に加える方法

FQLは、SQLと違いFacebook側で"indexable"と指定されているカラムしか、基本的にはWHERE節の条件に使えません。(日時カラムなど一部例外)
https://developers.facebook.com/docs/reference/fql/

実際、次のようなクエリーにはエラーが帰ってきます。

```sql
SELECT eid, rsvp_status, start_time, uid
FROM event_member
WHERE
	uid = 131130253626713 AND
	1342585934 < start_time AND
	rsvp_status = "attending"
```

これは、[ドキュメント](https://developers.facebook.com/docs/reference/fql/event_member/)にあるように、event_memberテーブルでは```uid```と```eid```のみがindexable(インデックス可能)なためです。```rsvp_status```は検索条件の対象外ということになります。

※```start_time```は、indexableになってませんが例外のようです。ドキュメントにも使える旨書いてありますね。ちょっと不思議...。
※ちなみに、131130253626713は[下北沢オープンソースCafe](https://www.facebook.com/shimokitazawa.osscafe)の```uid```です。

## 解決策

しかし、これはあまりにも不便! ということで、何とかする方法が一応ありました。

```sql
SELECT eid, rsvp_status, start_time, uid
FROM event_member
WHERE
	rsvp_status = "attending" AND
	eid IN (
		SELECT eid 
		FROM event_member 
		WHERE uid = 131130253626713 AND 1342585934 < start_time)
```

このように、同じテーブルの検索なんですが、わざわざ2段階にするとOKです。

## 何でOKなの?

蛇足ですが、ドキュメントにあまり書かれていないので、以下推察。

### 推察1

FQLではindexableかどうかと同時に、パーミッションの有無が重要です。

0. いきなり検索条件に```rsvp_status```を加えると、(レコードによっては)パーミッションがなくて怒られる。
0. そこで、先に```uid = 131130253626713```として、パーミッション的に問題ない範囲に限定。
0. そうすると、```rsvp_status```指定しても、パーミッションのエラーが返らないので、クエリーが通る。

といった感じなのかなと。

### 推察2

もう一案。こっちかも。

0. ```IN (SELECT ...)```が条件節に設定されると、実はテンポラリテーブルが生成されている。
0. テンポラリテーブル内は、どのカラムも検索可能。

※くどいようですが、推察です。