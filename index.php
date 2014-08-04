<?php
	// Requested language
	$lang = 'zh_CN';
	$languages = explode("\n", file_get_contents("content/languages"));
	if (isset($_GET['lang']) && in_array($_GET['lang'], $languages)) {
		$lang = $_GET['lang'];
	}

	// Load chapter list
	$navitems = explode("\n", file_get_contents("content/articles-" . $lang . "/index"));
	$navitemTitles = array();
	for ($i = 0; $i < count($navitems); $i++) {
		$navitems[$i] = explode("/", $navitems[$i]);
		$navitemTitles[$navitems[$i][0]] = $navitems[$i][1];
	}

	// Requested content
	$content = $navitems[0][0];
	if (isset($_GET["content"])) $content = $_GET["content"];

	// Determine how to load the requested content
	$notfound = !preg_match("/^[a-z]+$/", $content) || !file_exists("content/articles-" . $lang . "/" . $content . ".md");
	if ($notfound) {
		$contentFile = "content/articles-" . $lang . "/notfound.md";
		$contentTitle = "段错误";
		$contentID = 80000000;
	} else {
		$contentFile = "content/articles-" . $lang . "/" . $content . ".md";
		$contentTitle = $navitemTitles[$content];
		$contentID = 80000001 + array_search($contentTitle, $navitemTitles);
	}
	$contentSource = file_get_contents($contentFile);

	// Cache mechanism
	$last_modified_time = gmdate("r", max(filemtime('index.php'), filemtime($contentFile))) . " GMT";
	$etag = md5(file_get_contents('index.php') . $contentSource);

	if ((isset($_SERVER["HTTP_IF_MODIFIED_SINCE"]) && $_SERVER["HTTP_IF_MODIFIED_SINCE"] == $last_modified_time) ||
		(isset($_SERVER["HTTP_IF_NONE_MATCH"]) && str_replace('"', '', stripslashes($_SERVER['HTTP_IF_NONE_MATCH'])) == $etag))
	{
		header("HTTP/1.1 304 Not Modified");
		exit;
	}

	header("ETag: " . $etag);
	header("Last-Modified: " . $last_modified_time);
	header("Cache-Control: public");
?>
<!DOCTYPE html>
<html>
	<head>
		<meta charset="UTF-8">

		<title>OpenGL - <?php print($contentTitle); ?></title>

		<meta name="description" content="一个宽泛, 但适用于初学者学习在主流平台利用现代OpenGL进行游戏开发的教程." />
		<meta name="author" content="Alexander Overvoorde" />
		<meta name="translator" content="木叶沙子" />
		<meta name="keywords" content="opengl, opengl 3.2, deprecated, non-deprecated, tutorial, guide, cross-platform, game, games, graphics, sfml, sdl, glfw, glut, openglut, beginner, easy, 弃用, 未弃用, 教程, 指南, 跨平台, 游戏, 图形, 图形学, 初学者, 新手, 易用" />

		<link rel="shortcut icon" type="image/png" href="/media/tag.png" />
		<link rel="stylesheet" type="text/css" href="/media/stylesheet.css" />

		<meta name="viewport" content="width=device-width, initial-scale=1.0, minimum-scale=1.0" />
		<link rel="stylesheet" type="text/css" href="/media/mobile.css" media="screen and (max-width: 1024px)" />

		<script type="text/x-mathjax-config">
			// MathJax
			MathJax.Hub.Config( {
			  tex2jax: { inlineMath: [ [ '$', '$' ], [ '\\(', '\\)' ] ] },
			  menuSettings: { context: "Browser" }
			} );
		</script>
		<script type="text/javascript" src="http://cdn.mathjax.org/mathjax/latest/MathJax.js?config=TeX-AMS-MML_HTMLorMML"></script>

		<link rel="stylesheet" href="/includes/zenburn.min.css" />
		<script type="text/javascript" src="http://yandex.st/highlightjs/6.1/highlight.min.js"></script>
		<script type="text/javascript" src="/includes/glmatrix.js"></script>
		<script type="text/javascript">
			// Syntax highlighting
			hljs.initHighlightingOnLoad();

			// WebGL demos
			var requestAnimationFrame = window.requestAnimationFrame || window.mozRequestAnimationFrame || window.webkitRequestAnimationFrame;
			var callbacks = [];
			var frame = function() {
				var time = +new Date() / 1000;

				for (var i = 0; i < callbacks.length; i++) {
					var rect = callbacks[i].canvas.getBoundingClientRect();

					if (rect.bottom > 0 && rect.top < window.innerHeight)
						callbacks[i].callback( time );
				}

				requestAnimationFrame(frame);
			}
			function registerAnimatedCanvas(canvas, callback) {
				callbacks.push({canvas: canvas, callback: callback});
			}
			requestAnimationFrame(frame);
		</script>
		<!--[if lt IE 9]>
		<script src="media/html5shiv.js"></script>
		<![endif]-->
	</head>

	<body>
		<div id="page">
			<!-- Work in progress ribbon -->
			<a href="https://github.com/Overv/Open.GL"><img id="ribbon" src="/media/ribbon_fork.png" alt="Fork me!" /></a>

			<!-- Navigation items -->
			<input type="checkbox" id="nav_toggle" />
			<nav>
				<label for="nav_toggle" data-open="&#x2261;" data-close="&#x2715;"></label>
				<ul>
					<?php
						foreach ($navitems as $navitem)
						{
							if ($navitem[0] == $content)
								print( '<li class="selected">' . $navitem[1] . '</li>' . "\n" );
							else
								print( '<li><a href="/' . $navitem[0] . '?lang=' . $lang . '">' . $navitem[1] . '</a></li>' . "\n" );
						}
					?>
				</ul>

				<blockquote>
					<div style="margin-bottom: 5px; float: left">Language:</div>
					<div style="float: right">
						<?php
							foreach ($languages as $lang) {
								print('<a href="/' . $content . '?lang=' . $lang .'"><img src="/media/' . $lang . '.png" alt="' . $lang . '" /></a> ');
							}
						?>
					</div>
				</blockquote>
			</nav>

			<!-- Content container -->
			<main>
				<article>
					<?php
						include_once("includes/markdown.php");

						print(Markdown($contentSource));
					?>
				</article>
				<!-- 多说评论框 start -->
				<div class="ds-thread" data-thread-key="<?php print( $contentID ); ?>" data-title="<?php print( $content ); ?>" data-url="http://leafnsand.com/opengl/?content=<?php print( $content ); ?>"></div>
				<!-- 多说评论框 end -->
				<!-- 多说公共JS代码 start (一个网页只需插入一次) -->
				<script type="text/javascript">
					var duoshuoQuery = {short_name:"leafnsand"};
					(function() {
						var ds = document.createElement('script');
						ds.type = 'text/javascript';ds.async = true;
						ds.src = (document.location.protocol == 'https:' ? 'https:' : 'http:') + '//static.duoshuo.com/embed.js';
						ds.charset = 'UTF-8';
						(document.getElementsByTagName('head')[0] 
						 || document.getElementsByTagName('body')[0]).appendChild(ds);
					})();
				</script>
				<!-- 多说公共JS代码 end -->
			</main>
		</div>
	</body>
</html>
