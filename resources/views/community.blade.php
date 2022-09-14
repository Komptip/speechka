<html>
	<head>
		@include('basicshead')
		<link rel="stylesheet" type="text/css" href="/css/posts.css?key={{ uniqid() }}">
		<link rel="stylesheet" type="text/css" href="/css/profile.css?key={{ uniqid() }}">
		<link rel="stylesheet" type="text/css" href="/css/community.css?key={{ uniqid() }}">
		<link rel="stylesheet" type="text/css" href="/css/comments.css?key={{ uniqid() }}">

		<meta name="community_id" content="{{ $id }}">

		<script src="/javascript/community.js?key={{ uniqid() }}"></script>
	</head>
	<body id=app v-cloak>

		@include('header')

		<div class="content" v-if="communities[currentCommunity] !== undefined">
			
			@include('sidebar')

			<div class="posts">
				<div class="profile">
					<div class="picture" :style="'background-image: url(' + communities[currentCommunity].picture + ')'">
						
					</div>
					<p class="username">
						@{{ communities[currentCommunity].name }}

					<template v-if="user.moderator">
							<img src="/img/ban.svg" v-if="communities[currentCommunity].active == 1" class="ban" v-on:click="deleteCommunity(currentCommunity)"/>
							<img src="/img/unban.svg" v-else class="ban" v-on:click="undeleteCommunity(currentCommunity)"/>
						</template>
					</p>
					<p class="signup-time">@{{ communities[currentCommunity].description }}</p>
					<div class="bottom" v-if="communities[currentCommunity].mode == 0">
						<a class="new-post" :href="'/post/new?community_id=' + currentCommunity">Написать пост</a>
						<div class="open-settings" v-if="adminInCommunities.includes(currentCommunity)" v-on:click="showCommunitySettings"></div>
					</div>
					<div class="bottom" v-else>
						<p>В этом сообществе могут писать только его администраторы</p>
						<div class="open-settings" v-if="adminInCommunities.includes(currentCommunity)" v-on:click="showCommunitySettings"></div>
					</div>
				</div>

				<div class="profile-content">
					<div class="posts">
						@include('posts')
					</div>
					<div class="rightbar">
						<div class="subscribers">
							<p>Подписчики</p>
						</div>
					</div>
				</div>
			</div>

			@include('sidecomments')

		</div>

		@include('authwindows')
		@include('communitywindows')
		@include('settings')
		@include('messages')

		<script type="text/javascript" src="/javascript/app.js"></script>
	</body>
	
</script>
</html>