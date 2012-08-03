#HTML5 Canvasの描画メソッドを拡張

JavaScriptでCanvasを操作する際に、2Dコンテキストに予め定義されているrectなど基本的なものだけだと、いろいろ足りなくなってきます。[paper.js](http://paperjs.org/)や、[Kinetic.js](http://www.kineticjs.com/)など、Canvasの抽象ライブラリを導入して、そちらを拡張するのも手です。ただ、そこまでしなくても良い場合は、2Dコンテキストを拡張してしまうのが簡単です。

通常、2DコンテキストはCanvasエレメントから取得します(jQueryの場合)。

```javascript
var ctx = $('canvas')[0].getContext('2d');
```

この2Dコンテキストのプロトタイプを取得するには....

```javascript
Object.getPrototypeOf($('canvas')[0].getContext('2d'))
// あるいは、より一般的にして
// Object.getPrototypeOf(document.createElement('canvas').getContext('2d'))
```

としても取れますが、まどろっこしいですよね。[W3Cのワーキングドラフト](http://www.w3.org/TR/2dcontext/)によれば、CanvasRenderingContext2D として定義されているので、

```javascript
CanvasRenderingContext2D.prototype
```

とした方が、シンプルです。

以下、コードの例。グラフを

 ●●●●●○○○○○
 ●●●○○○○○○○
 ●●●●●●●○○○

のような形式で、Canvas上に描画できるメソッドを2Dコンテキストに追加しています。(インフォグラフィクスの一部として使う想定)

```javascript
CanvasRenderingContext2D.prototype.drawDotGraph = function(num, max, size, color, x, y) {
	this.lineWidth = 1;
	this.strokeStyle = '#999';
	for (var i = 0; i < max; i++){
		this.beginPath();
		this.fillStyle = (num > i) ? color : '#FFFFFF';
		this.arc(x + (size + 2)*i, y, 0.5*size, 0, 2*Math.PI, false);
		this.fill();
		this.stroke();
	}
};

var ctx = $('canvas')[].getContext('2d');
ctx.drawDotGraph(3, 10, 8, '#FF0000', 50, 50);
```


## 付記

1. ビルトインオブジェクトを拡張することになるので、[Googleのスタイルガイド](http://google-styleguide.googlecode.com/svn/trunk/javascriptguide.xml)などに従うのであれば、ラッパークラスを書くのがベターです。