#シャドウを画像の内側にかけるCSS/jQuery

CSS3のbox-shadowを使うと、ドロップシャドウだけでなく内側のシャドウをかけることができます。ただ、次の例ように画像に適用しようとすると、ちょっとしたトリックが必要になります。

![内側シャドウ](http://cl.ly/image/2B3T3Y2Y401A/Image%202012.08.23%2010:46:08.png)

例えば、このようなHTML(↓)に対して、

```html
<figure>
    <img class="inset-shadow"
        src="http://www.osscafe.net/images/164x164/shimokita-php.png" />
</figure>
```

次のようなスタイルを適用しても、内側にシャドウをかけることはできません。

```css
img.inset-shadow {
    border-radius:15px;
    box-shadow:inset 0 1px 5px rgba(0,0,0,.5);
}
```

これは、描画順で画像がシャドウの手前に来てしまうためです。

0. background
0. box-shadow
0. content (画像)

回避方法は、主に2つ。

* シャドウだけ別要素で描いて手前に表示する →CSSによる解決
* 画像の内容を背景に移してシャドウを有効にする →スクリプトによる解決

## CSSによる解決

画像をまずクロップして、::after疑似要素で影を重ねます。この例では、figure要素にしていますが、適用するHTML次第でa要素などでも構いません。

```css
figure {
    position:relative;
    display:inline-block;
    line-height:0;
    border-radius:15px;
    overflow:hidden;
}
figure::after {
    content:"";
    position:absolute;
    top:0; right:0; bottom:0; left:0;
    border-radius:15px;
    box-shadow:inset 0 1px 5px rgba(0,0,0,.5);
}
```

## スクリプトによる解決

span要素を新たに作り、背景画像に、img要素のsrc属性の画像を設定して、もともとあった画像要素と差替えます。

```coffee
$.fn.img2span = () ->
    $img = $ this
    $span = $ '<span />'
    $span.attr 'class', $img.attr 'class'
    $span.css
        display: 'inline-block'
        backgroundImage: "url(#{$img.attr('src')})"
        width: $img.width()
        height: $img.height()
    $img.before($span).remove()
    $span

$('img.inset-shadow').img2span()
```

cssで、span要素にもスタイルを適用しておけばOK。

```css
img.inset-shadow,
span.inset-shadow {
    border-radius:15px;
    box-shadow:inset 0 1px 5px rgba(0,0,0,.5);
}
```

## デモ
jsfiddleに置いておきます。

* http://jsfiddle.net/cognitom/QLTMe/