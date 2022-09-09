<!DOCTYPE html>
<html>
	<head>
		@include('basicshead')
		<link rel="stylesheet" type="text/css" href="/css/posts.css">
		<link rel="stylesheet" type="text/css" href="/css/post.css">
		<script src="/javascript/editor.js?key={{ uniqid() }}"></script>
		<script src="/javascript/editor/header.js?key={{ uniqid() }}"></script>
		<script src="/javascript/editor/list.js?key={{ uniqid() }}"></script>
		<script src="/javascript/editor/integration.?key={{ uniqid() }}js"></script>
		<script src="/javascript/editor/simple-image.js?key={{ uniqid() }}"></script>

		<meta name="post-id" content="{{ $post->id }}">
	</head>
	<body id=app v-cloak>

		@include('header')

		<div class="content">

			@include('sidebar')

			<div class="posts">
				<div class="post large">
					<button class="new-post-return" v-on:click="viewCommentOrigin(postForEdit['id'])">Вернуться к посту</button>
					<button :class="['new-post', postSended ? 'noactive' : '']" v-on:click="savePost">Сохранить</button>
					<h1 class="new-post-title" v-html="postForEdit['title']" contenteditable placeholder="Введите заголовок..."></h1>
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
		<script type="text/javascript" src="/javascript/editpost.js"></script>
	</body>
	
</script>
</html>
