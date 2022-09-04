<?php use App\Http\Controllers\TimeController; ?>
<?php use Illuminate\Support\Facades\DB; ?>
<?php use App\Http\Controllers\AuthController; ?>
<!DOCTYPE html>
<html>
	<head>
		@include('basicshead')
		<link rel="stylesheet" type="text/css" href="/css/posts.css">
		<link rel="stylesheet" type="text/css" href="/css/profile.css">
		<link rel="stylesheet" type="text/css" href="/css/comments.css">

		<meta name="user_id" content="{{ $user->id }}">
		<meta name="type" content="{{ $type }}">

		<script src="/javascript/profile.js"></script>
	</head>
	<body id=app v-cloak>

		@include('header')

		<div class="content">
			
			@include('sidebar')

			<?php
				$isUserBanned = AuthController::isUserBanned($user);
			?>

			<div class="posts">
				<div class="profile">
					<div class="picture" style="background-image: url({{ $isUserBanned ? '/img/removed.webp' : $user->picture }});">
						
					</div>
					<p class="username">
						{{ $isUserBanned ? 'Аккаунт заморожен' : $user->username }}
						<template v-if="user.moderator">
							<img v-if="{{ $isUserBanned ? 'false' : 'true' }}" src="/img/ban.svg" class="ban" v-on:click="banUser({{ $user->id }})"/>
							<img v-if="{{ $isUserBanned ? 'true' : 'false' }}" src="/img/unban.svg" class="ban" v-on:click="unbanUser({{ $user->id }})"/>
						</template>
					</p>
					<?php
						$postRating = DB::select('
							SELECT users.id, SUM(IF(ratings.value=0, -1, IF(ratings.value=1, 1, 0))) AS rating
							FROM users LEFT JOIN posts
							    ON users.id = posts.user_id
							LEFT JOIN ratings
								ON posts.id = ratings.entity_id AND ratings.type = 0
							WHERE users.id = ?
							GROUP BY users.id
							ORDER BY rating
							', [$user->id])[0]->rating;

						$commentRating = DB::select('
							SELECT users.id, SUM(IF(ratings.value=0, -1, IF(ratings.value=1, 1, 0))) AS rating
							FROM users LEFT JOIN comments
							    ON users.id = comments.user_id
							LEFT JOIN ratings
								ON comments.id = ratings.entity_id AND ratings.type = 1
							WHERE users.id = ?
							GROUP BY users.id
							ORDER BY rating
							', [$user->id])[0]->rating;

						$rating = $postRating + $commentRating;
					?>
					<div class="statistic">
						<p class="rating {{ $rating == 0 ? '' : ($rating > 0 ? 'positive' : 'negative') }}">{{ $rating == 0 ? '' : ($rating > 0 ? '+' : '') }}{{ $rating }}</p>
					</div>
					<p class="signup-time">На проекте с {{ date('d', $user->created_at) }} {{ TimeController::months()[date('m', $user->created_at) - 1] }} {{ date('Y', $user->created_at) }}</p>
					<div class="tabs">
						<div class="tab {{ $type == 'posts' ? 'active' : '' }}" v-on:click="viewProfileType('posts')">
							<p>Посты</p>
						</div>
						<div class="tab {{ $type == 'comments' ? 'active' : '' }}" v-on:click="viewProfileType('comments')">
							<p>Комментарии</p>
						</div>
					</div>
				</div>

				<div class="profile-content">
					<div class="posts">
						@if($type == 'posts')
							<template v-if="posts">
								@include('posts')
							</template>
						@else
							<template v-if="comments">
								@include('comments')
							</template>
						@endif
					</div>
					<div class="rightbar">
						<div class="subscribers">
							<p>Подписчики</p>
						</div>
					</div>
				</div>
			</div>

		</div>

		@include('authwindows')
		@include('settings')
		@include('messages')

		<script type="text/javascript" src="/javascript/app.js"></script>
	</body>
	
</script>
</html>