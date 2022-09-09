<!DOCTYPE html>
<html>
	<head>
		@include('basicshead')
		<link rel="stylesheet" type="text/css" href="/css/posts.css?key={{ uniqid() }}">

		<meta name="feed-type" content="newest">

		<script src="/javascript/feed.js?key={{ uniqid() }}"></script>
	</head>
	<body id=app v-cloak>

		@include('header')

		<div class="content">
			@include('sidebar')

			<div class="posts" v-if="posts">
				
				@include('posts')

			</div>

			@include('sidecomments')

		</div>

		@include('authwindows')
		@include('settings')
		@include('messages')

		<script type="text/javascript" src="/javascript/app.js"></script>
	</body>
</html>