<template v-for="commentID in commentsOrder">
	<div class="post" v-if="comments[commentID] !== undefined">
		<div class="comment">
			<div class="meta">
				<div class="icon" :style="'background-image: url(' + users[comments[commentID]['author_id']]['picture'] + ')'" v-on:click="viewUserProfile(comments[commentID]['author_id'])">
					
				</div>
				<div class="name-and-date">
					<p class="name" v-on:click="viewUserProfile(comments[commentID]['author_id'])">@{{ users[comments[commentID]['author_id']]['name'] }}</p>
					<p class="date">@{{ formatTime(comments[commentID]['created_at']) }}</p>
				</div>
				<div class="rating">
					<p :class="comments[commentID]['rating_value'] > 0 ? 'positive' : comments[commentID]['rating_value'] < 0 ? 'negative' : ''">@{{ comments[commentID]['rating_value'] }}</p>
					<div class="rating-list" v-if="Object.keys(comments[commentID]['rating']).length > 0">
						<a class="user" v-for="(rate, id) in comments[commentID]['rating']" :href="'/u/' + id">
							<div class="pic" :style="'background-image: url(' + users[id]['picture'] + ');'">
								
							</div>
							<p :class="rate == 1 ? 'positive' : 'negative'">@{{ users[id]['name'] }}</p>
						</a>
					</div>
				</div>
			</div>
			<div class="comment-text">
				<p>
					@{{ comments[commentID]['content'] }}
				</p>
			</div>
			<div class="attachment" v-if="comments[commentID]['attachment']" >
				<img :src="comments[commentID]['attachment']" />
			</div>
			<div class="bottom">
				<a class="reply hidden" :href="`/p/${comments[commentID]['post_id']}?comment=${commentID}`">Ответить</a>
			</div>
		</div>
	</div>		
</template>