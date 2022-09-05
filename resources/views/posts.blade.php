<template v-for="post in posts">
	<div class="post" v-if="!post.hidden">
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
				<div class="more">
					<img src="/img/more.svg" class="btn" v-on:click="post.showMore = !post.showMore">
					<div v-if="post.showMore" class="list">
						<p v-on:click="hidePost(post)">Скрыть</p>
						<p v-if="post.author_id === user.id" v-on:click="deletePost(post)">Удалить</p>
					</div>
				</div>
			</div>
			<div class="post-content" v-on:click="viewPost(post)">
				<h1 class="title" v-if="post['title'] !== null > 0">@{{ post['title'] }}</h1>
				<template v-for="element in post['elements']">
					<h2 class="sub-title" v-if="element['type'] == 'header'" v-html="element['data']['text']"></h2>
					<p class="text" v-if="element['type'] == 'paragraph'" v-html="element['data']['text']"></p>
					<div :class="['image', element['data']['stretched'] == 1 ? 'stretched' : '']" v-if="element['type'] == 'image'" >
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
		</template>
	</div>
</template>