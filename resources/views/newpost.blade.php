<!DOCTYPE html>
<html>
	<head>
		@include('basicshead')
		<link rel="stylesheet" type="text/css" href="/css/posts.css">
		<link rel="stylesheet" type="text/css" href="/css/post.css">
		<script src="/javascript/editor.js"></script>
		<script src="/javascript/editor/header.js"></script>
		<script src="/javascript/editor/list.js"></script>
		<script src="/javascript/editor/simple-image.js"></script>


	</head>
	<body id=app v-cloak>

		@include('header')

		<div class="content">

			@include('sidebar')

			<div class="posts">
				<div class="post large">
					<button :class="['new-post', postSended ? 'noactive' : '']" v-on:click="publishPost">Опубликовать</button>
					<h1 class="new-post-title" contenteditable placeholder="Введите заголовок..."></h1>
					<div class="post-content">
						<div id="editorjs"></div>
					</div>
				</div>
			</div>

			

		</div>

		@include('authwindows')
		@include('settings')
		@include('messages')

		<script type="text/javascript" src="/javascript/app.js"></script>
		<script type="text/javascript" src="/javascript/newpost.js"></script>
	</body>
	
</script>
</html>
