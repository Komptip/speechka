<!DOCTYPE html>
<html>
	<head>
		@include('basicshead')
		<link rel="stylesheet" type="text/css" href="/css/posts.css?key={{ uniqid() }}">
		<link rel="stylesheet" type="text/css" href="/css/post.css?key={{ uniqid() }}">
		<script src="/javascript/editor.js"></script>
		<script src="/javascript/editor/header.js?key={{ uniqid() }}"></script>
		<script src="/javascript/editor/list.js"?key={{ uniqid() }}></script>
		<script src="/javascript/editor/integration.js"?key={{ uniqid() }}></script>
		<script src="/javascript/editor/simple-image.js?key={{ uniqid() }}"></script>
	</head>
	<body id=app v-cloak>

		@include('header')

		<div class="content">

			@include('sidebar')

			<div class="posts">
				<div class="post large">
					<button :class="['new-post', postSended ? 'noactive' : '']" v-on:click="publishPost">Опубликовать</button>
					<div class="new-post-to" v-if="communitiesLoaed">
						<p>Опубликовать в:</p>
						<div class="select">
							<div class="element" v-on:click="newPostCommunity.listHidden = !newPostCommunity.listHidden">
								<div class="item">
									<div class="icon" :style="['background-image: url(' + newPostCommunity.communitiesToShow[newPostCommunity.selected].picture + ');']">
										
									</div>
									<p class="name">@{{ newPostCommunity.communitiesToShow[newPostCommunity.selected].name }}</p>
									<div class="show-more-icon"></div>
								</div>
							</div>
							<div class="list" v-if="!newPostCommunity.listHidden">
								<input class="search" placeholder="Поиск по названию" v-model="newPostCommunity.searchFilter">
								<template v-for="(item, index) in filterCommunities(newPostCommunity.communitiesToShow, newPostCommunity.searchFilter)" >
									<div class="item" v-if="item.display" :key="newPostCommunity.searchFilter" v-on:click="newPostCommunity.selected = index;newPostCommunity.listHidden=true;">
										<div class="icon" :style="['background-image: url(' + item.picture + ');']">
											
										</div>
										<p class="name">@{{ item.name }}</p>
									</div>
								</template>
							</div>
						</div>
					</div>
					<h1 class="new-post-title" contenteditable placeholder="Введите заголовок..."></h1>
					<div class="post-content">
						<div id="editorjs"></div>
					</div>
				</div>
			</div>

			

		</div>

		@include('authwindows')
		@include('communitywindows')
		@include('settings')
		@include('messages')

		<script type="text/javascript" src="/javascript/app.js"></script>
		<script type="text/javascript" src="/javascript/newpost.js"></script>
	</body>
	
</script>
</html>
