<div class="sidecomments" v-if="!sideCommentsHidden">
	<h3 v-on:click="sideCommentsHidden = true">Комменарии</h3>
	<template v-if="sideComments && sideCommentsPostsTitles">
		<div class="comment" v-for="comment in sideComments" v-on:click="viewCommentOrigin(sComments[comment].post_id)">
			<div class="comment-post">
				<p>@{{ sideCommentsPostsTitles[sComments[comment].post_id] }}</p>
			</div>
			<div class="comment-content">
				<div class="meta">
					<a class="icon" :href="'/u/' + sComments[comment].author_id">
						
					</a>
					<div class="name-and-date">
						<a class="name" :href="'/u/' + sComments[comment].author_id">@{{ users[sComments[comment]['author_id']].name }}</a>
						<p class="date">@{{ formatTime(sComments[comment]['created_at']) }}</p>
					</div>
				</div>
				<div class="comment-text">
					<p>@{{ sComments[comment]['content'] }}</p>
				</div>
				<div class="bottom">
					<p class="reply">Ответить</p>
				</div>
			</div>
		</div>
	</template>
</div>
<div class="sidecomments-open" v-if="sideCommentsHidden" v-on:click="sideCommentsHidden = false">
	<h3>Комментарии</h3>
</div>