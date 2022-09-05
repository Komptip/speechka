<!DOCTYPE html>
<html>
	<head>
		@include('basicshead')
		<link rel="stylesheet" type="text/css" href="/css/posts.css">
		<link rel="stylesheet" type="text/css" href="/css/comments.css">

		<meta name="post-id" content="{{ $post->id }}">
		<meta name="feed-type" content="popular">

		<script src="/javascript/viewpost.js"></script>
		<script src="/javascript/feed.js"></script>
	</head>
	<body id=app v-cloak>

		@include('header')

		<div class="content">

			@include('sidebar')

			<div class="posts" v-if="posts">
				
				<div class="post large">
					<div class="meta">
						<a v-if="posts[0]['active']" :href="'/u/' + posts[0]['author_id']" class="icon" :style="'background-image: url(' + users[posts[0]['author_id']]['picture'] + ')'">
				
						</a>
						<div class="icon" v-if="!posts[0]['active']" style="background-image: url(/img/removed.webp);">
							
						</div>
						<a v-if="posts[0]['active']" class="author" :href="'/u/' + posts[0]['author_id']">@{{ users[posts[0]['author_id']]['name'] }}</a>
						<p v-if="posts[0]['active']" class="timestamp">@{{ formatTime(posts[0]['created_at']) }}</p>
						<template v-if="user.moderator">
							<img v-if="posts[0]['active']" src="/img/ban.svg" class="ban" v-on:click="removePosts(posts[0]['id'])"/>
							<img v-if="!posts[0]['active']" src="/img/unban.svg" class="ban" v-on:click="unremovePosts(posts[0]['id'])"/>
						</template>
						<div class="more">
							<img src="/img/more.svg" class="btn" v-on:click="posts[0].showMore = !posts[0].showMore">
							<div v-if="posts[0].showMore" class="list">
								<p v-on:click="hidePost(posts[0])">Скрыть</p>
								<p v-if="posts[0].author_id === user.id && posts[0]['active'] == 1" v-on:click="deletePost(posts[0])">Удалить</p>
								<p v-if="posts[0].author_id === user.id && posts[0]['active'] == 0" v-on:click="republishPost(posts[0])">Восстановить</p>
							</div>
						</div>
					</div>
					<div class="post-content" v-if="posts[0]['active']">
						<h1 class="title" v-if="posts[0]['title'] !== null > 0">@{{ posts[0]['title'] }}</h1>
						<template v-for="element in posts[0]['elements']">
							<h1 class="title" v-if="element['type'] == 'header'" v-html="element['data']['text']"></h1>
							<p class="text" v-if="element['type'] == 'paragraph'" v-html="element['data']['text']"></p>
							<div :class="['image', element['data']['stretched'] == 1 ? 'stretched' : '']" v-if="element['type'] == 'image'">
								<img :src="element['data']['file']">
								<p class="caption"><i>@{{ element['data']['caption'] }}</i></p>
							</div>
							<ul v-if="element['type'] == 'list' && element['data']['style'] == 'unordered'">
								<li v-for="item in element['data']['list_items']" v-html="item"></li>
							</ul>
							<ol v-if="element['type'] == 'list' && element['data']['style'] == 'ordered'">
								<li v-for="item in element['data']['list_items']" v-html="item"></li>
							</ol>
							<template v-if="element['type'] == 'link'">
								<a class="link-url" :href="element['data']['url']">@{{ element['data']['url'] }}</a>
								<template v-if="parseURL(element['data']['url'])['type'] == 'youtube'">
									<div class="ytb-video">
										<iframe :src="'https://www.youtube.com/embed/' + parseURL(element['data']['url'])['key']" allowfullscreen></iframe>
									</div>
								</template>
								<template v-if="parseURL(element['data']['url'])['type'] == 'twitter'">
									<div class="twitter">
										<blockquote class="twitter-tweet"><a :href="element['data']['url']"></a></blockquote>
									</div>
								</template>
							</template>
						</template>
					</div>

					<div class="post-content" v-if="!posts[0]['active']">
						<h1 class="title">Пост удален</h1>
					</div>
					<div class="data">
						<div class="actions">
							<div class="action">
								<img src="/img/comment.svg">
								<p>@{{ comments ? Object.keys(comments).length : posts[0]['comments_count'] }}</p>
							</div>
							<div class="action" v-on:click="sharePost(posts[0])">
								<img src="/img/share.svg">
							</div>
						</div>
						<div class="rating">
							{!! file_get_contents('img/rating-down-postview.svg') !!}
							<p :class="posts[0]['ratingValue'] > 0 ? 'positive' : posts[0]['ratingValue'] < 0 ? 'negative' : ''">@{{ posts[0]['ratingValue'] }}</p>
							<div class="rating-list" v-if="Object.keys(posts[0]['rating']).length > 0">
								<a class="user" v-for="(rate, id) in posts[0]['rating']" :href="'/u/' + id">
									<div class="pic" :style="'background-image: url(' + users[id]['picture'] + ');'">
										
									</div>
									<p :class="rate == 1 ? 'positive' : 'negative'">@{{ users[id]['name'] }}</p>
								</a>
							</div>
							{!! file_get_contents('img/rating-up-postview.svg') !!}
						</div>
					</div>
				</div>

				<div class="comments-section">
					<div class="comments">
						<p class="counter">@{{ Object.keys(comments).length }} комментария</p>

						<div class="comment-editor">
							<p class="text" contenteditable placeholder="Написать комментарий..."></p>
							<div class="attachment" v-if="commentsreplies['start']['attachment']" :style="'background-image: url(' + commentsreplies['start']['attachment']  + ')'">
								<div class="remove" v-on:click="removeAttachment($event, 'start')"></div>
							</div>
							<div class="bottom">
								<input type="file" accept=".jpeg,.png,.bmp,.jpg,.gif,.webp,.avif,.svg" class="file-uploader" v-on:change="onUploadAttachment($event, 'start')" required>
								<img src="/img/image.svg" :class="['image', commentsreplies['reply']['start'] ? 'noactive' : '']" v-on:click="uplaodAttachment">
								<button :class="[commentsreplies['start']['sended'] ? 'noactive' : '']" v-on:click="publishComment($event, 'start')">Отправить</button>
							</div>
						</div>

						<comments :app="this"></comments>
						
					</div>
				</div>

				<div class="posts">
					@include('posts')
				</div>
			</div>
			
		</div>
		@include('authwindows')
		@include('settings')
		@include('messages')
		<script type="text/javascript" src="/javascript/app.js"></script>
	</body>
</html>