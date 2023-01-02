<template v-for="post in posts">
	<div class="post" v-if="!post.hidden && post['id'] != postViewID">
		<div class="meta">
			<a v-if="post['active']" :href="[post['community_id'] ? ('/c/' + post['community_id']) : ('/u/' + post['author_id'])]" class="icon" :style="'background-image: url(' + (post['community_id'] && !currentCommunity ? communities[post['community_id']]['picture'] : users[post['author_id']]['picture']) + ')'">
				
			</a>
			<div class="icon" v-if="!post['active']" style="background-image: url(/img/removed.webp);">
							
			</div>
			<a class="author" :href="'/c/' + post['community_id']" v-if="post['community_id'] && !currentCommunity">@{{ communities[post['community_id']]['name'] }}</a>
			<a v-if="post['active']" :class="['author', post['community_id'] && !currentCommunity ? 'with-community' : '']" :href="'/u/' + post['author_id']">@{{ users[post['author_id']]['name'] }}</a>
			<p v-if="post['active']" class="timestamp">@{{ formatTime(post['created_at']) }}</p>
			<template v-if="user.moderator">
				<img v-if="post['active']" src="/img/ban.svg" class="ban" v-on:click="removePosts(post['id'])"/>
				<img v-if="!post['active']" src="/img/unban.svg" class="ban" v-on:click="unremovePosts(post['id'])"/>
			</template>
			<div class="more">
				<img src="/img/more.svg" class="btn" v-on:click="post.showMore = !post.showMore">
				<div v-if="post.showMore" class="list">
					<p v-on:click="hidePost(post)">Скрыть</p>
					<p v-if="post.author_id === user.id" v-on:click="editPost(post)">Редактировать</p>
					<p v-if="(post.author_id === user.id || adminInCommunities.includes(post['community_id'])) && post['active'] == 1" v-on:click="deletePost(post)">Удалить</p>
					<p v-if="(post.author_id === user.id || adminInCommunities.includes(post['community_id'])) && post['active'] == 0" v-on:click="republishPost(post)">Восстановить</p>
				</div>
			</div>
		</div>
		<div class="post-content" v-on:click="viewPost(post)">
			<h1 class="title" v-if="post['title'] !== null > 0"><a class="hidden" :href="'/p/' + post['id']">@{{ post['title'] }}</a></h1>
			<template v-for="element in post['elements']">
				<h2 class="sub-title" v-if="element['type'] == 'header'" v-html="element['data']['text']"></h2>
				<p class="text" v-if="element['type'] == 'paragraph'" v-html="element['data']['text']"></p>
				<template v-if="element['type'] == 'image'">
					<div :class="['image', element['data']['stretched'] == 1 ? 'stretched' : '']">
						<img :src="element['data']['file']">
					</div>
					<p class="caption"><i>@{{ element['data']['caption'] }}</i></p>
				</template>
				<ul v-if="element['type'] == 'list' && element['data']['style'] == 'unordered'">
					<li v-for="item in element['data']['list_items']" v-html="item"></li>
				</ul>
				<ol v-if="element['type'] == 'list' && element['data']['style'] == 'ordered'">
					<li v-for="item in element['data']['list_items']" v-html="item"></li>
				</ol>
				<template v-if="element['type'] == 'integration'">
					<template v-if="parseURL(element['data']['url'])['type'] == 'youtube'">
						<div class="ytb-video">
							<iframe :src="'https://www.youtube.com/embed/' + parseURL(element['data']['url'])['key']" allowfullscreen></iframe>
						</div>
					</template>
					<template v-else-if="parseURL(element['data']['url'])['type'] == 'twitter'">
						<div class="twitter">
							<blockquote class="twitter-tweet"><a :href="element['data']['url']"></a></blockquote>
						</div>
					</template>
					<template v-else-if="parseURL(element['data']['url'])['type'] == 'telegram'">
						<div class="telegram">
							<component :is="'script'" async src="https://telegram.org/js/telegram-widget.js?19" :data-telegram-post="parseURL(element['data']['url'])['url']" data-width="100%"></component>
						</div>
					</template>
					<template v-else>
						<a class="link-url" :href="element['data']['url']">@{{ element['data']['url'] }}</a>
					</template>
				</template>
			</template>
		</div>
		<div class="data">
			<div class="actions">
				<a class="action hidden" :href="`/p/${post['id']}?comment`">
					<img src="/img/comment.svg">
					<p>@{{ post['comments_count'] }}</p>
				</a>
				<div class="action" title="Поделиться" v-on:click="sharePost(post)">
					<img src="/img/share.svg">
				</div>
			</div>
			<div class="rating">
				{!! file_get_contents('img/rating-down.svg') !!}
				<p :class="post['ratingValue'] > 0 ? 'positive' : post['ratingValue'] < 0 ? 'negative' : ''">@{{ post['ratingValue'] }}</p>
				<div class="rating-list" v-if="Object.keys(post['rating']).length > 0">
					<a class="user" v-for="(rate, id) in post['rating']" :href="'/u/' + id">
						<div class="pic" :style="'background-image: url(' + users[id]['picture'] + ');'">
							
						</div>
						<p :class="rate == 1 ? 'positive' : 'negative'">@{{ users[id]['name'] }}</p>
					</a>
				</div>
				{!! file_get_contents('img/rating-up.svg') !!}
			</div>
		</div>
	</div>
</template>