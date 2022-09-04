<div class="post" v-for="post in posts">
	<template v-if="post['id'] != postViewID">
		<div class="meta">
			<a v-if="post['active']" :href="'/u/' + post['author_id']" class="icon" :style="'background-image: url(' + users[post['author_id']]['picture'] + ')'">
				
			</a>
			<div class="icon" v-if="!post['active']" style="background-image: url(/img/removed.webp);">
							
			</div>
			<a v-if="post['active']" class="author" :href="'/u/' + post['author_id']">@{{ users[post['author_id']]['name'] }}</a>
			<p v-if="post['active']" class="timestamp">@{{ formatTime(post['created_at']) }}</p>
			<template v-if="user.moderator">
				<img v-if="post['active']" src="/img/ban.svg" class="ban" v-on:click="removePosts(post['id'])"/>
				<img v-if="!post['active']" src="/img/unban.svg" class="ban" v-on:click="unremovePosts(post['id'])"/>
			</template>
		</div>
		<div class="post-content" v-on:click="viewPost(post)">
			<h1 class="title" v-if="post['title'] !== null > 0">@{{ post['title'] }}</h1>
			<template v-for="element in post['elements']">
				<h2 class="sub-title" v-if="element['type'] == 'header'" v-html="element['data']['text']"></h2>
				<p class="text" v-if="element['type'] == 'paragraph'" v-html="element['data']['text']"></p>
				<div :class="['image', element['data']['stretched'] == 1 ? 'stretched' : '']" v-if="element['type'] == 'image'" >
					<img :src="element['data']['file']">
				</div>
				<ul v-if="element['type'] == 'list' && element['data']['style'] == 'unordered'">
					<li v-for="item in element['data']['list_items']" v-html="item"></li>
				</ul>
				<ol v-if="element['type'] == 'list' && element['data']['style'] == 'ordered'">
					<li v-for="item in element['data']['list_items']" v-html="item"></li>
				</ol>
			</template>
		</div>
		<div class="data">
			<div class="actions">
				<div class="action" v-on:click="viewPost(post)">
					<img src="/img/comment.svg">
					<p>@{{ post['comments_count'] }}</p>
				</div>
				<div class="action" title="Поделиться" v-on:click="sharePost(post)">
					<img src="/img/share.svg">
				</div>
			</div>
			<div class="rating">
				{!! file_get_contents('img/rating-up.svg') !!}
				<p :class="post['ratingValue'] > 0 ? 'positive' : post['ratingValue'] < 0 ? 'negative' : ''">@{{ post['ratingValue'] }}</p>
				<div class="rating-list" v-if="Object.keys(post['rating']).length > 0">
					<a class="user" v-for="(rate, id) in post['rating']" :href="'/u/' + id">
						<div class="pic" :style="'background-image: url(' + users[id]['picture'] + ');'">
							
						</div>
						<p :class="rate == 1 ? 'positive' : 'negative'">@{{ users[id]['name'] }}</p>
					</a>
				</div>
				{!! file_get_contents('img/rating-down.svg') !!}
			</div>
		</div>
	</template>
</div>