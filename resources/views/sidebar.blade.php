<div class="sidebar" v-if="!sidebarHidded">
	<div class="buttons">
		<a :class="['button', feedType == 'popular' ? 'active' : '']" href="/popular">
			<img src="/img/popular.svg">
			<p>Популярное</p>
		</a>
		<a :class="['button', feedType == 'newest' ? 'active' : '']" href="/new">
			<img src="/img/new.svg">
			<p>Свежее</p>
		</a>
	</div>
	<div class="communities" v-if="sideCommunitiesOrder">
		<p class="title">Подсайты</p>
		<a v-for="communityID in (sideCommunitiesOrder.length > communitiesListLimit ? (viewAllCommunities ? sideCommunitiesOrder : sideCommunitiesOrder.slice(0, communitiesListLimit)) : sideCommunitiesOrder)" class="button" :href="'/c/' + communityID">
			<div class="icon" :style="'background-image: url(' + communities[communityID].picture + ');'">
				
			</div>
			<p>@{{ communities[communityID].name }}</p>
		</a>
		<div class="view-all" v-if="sideCommunitiesOrder.length > communitiesListLimit" v-on:click="viewAllCommunities = !viewAllCommunities">
			<template v-if="viewAllCommunities">
				<img src="/img/arrow-up.svg">
				<p>Свернуть</p>
			</template>
			<template v-else>
				<img src="/img/arrow-down.svg">
				<p>Ещё @{{ sideCommunitiesOrder.length - communitiesListLimit }}</p>
			</template>
		</div>
		<button class="community-new" v-on:click="user === false ? (throwMessage('Для этого действия нужно авторизоваться', 'error'),auth = 1) : communityWindow = 1;">
			<div class="icon">
				
			</div>
			<p>Создать</p>
		</button>
	</div>
</div>

<div class="sidebar mobile" v-if="!sidebarHiddenMobile" v-on:click="hideMobileSidebar">
	<div class="left">
		<div class="buttons">
			<a :class="['button', feedType == 'popular' ? 'active' : '']" href="/popular">
				<img src="/img/popular.svg">
				<p>Популярное</p>
			</a>
			<a :class="['button', feedType == 'newest' ? 'active' : '']" href="/new">
				<img src="/img/new.svg">
				<p>Свежее</p>
			</a>
		</div>
	<div class="communities" v-if="sideCommunitiesOrder">
		<p class="title">Подсайты</p>
		<a v-for="communityID in (sideCommunitiesOrder.length > communitiesListLimit ? (viewAllCommunities ? sideCommunitiesOrder : sideCommunitiesOrder.slice(0, communitiesListLimit)) : sideCommunitiesOrder)" class="button" :href="'/c/' + communityID">
			<div class="icon" :style="'background-image: url(' + communities[communityID].picture + ');'">
				
			</div>
			<p>@{{ communities[communityID].name }}</p>
		</a>
		<div class="view-all" v-if="sideCommunitiesOrder.length > communitiesListLimit" v-on:click="viewAllCommunities = !viewAllCommunities">
			<template v-if="viewAllCommunities">
				<img src="/img/arrow-up.svg">
				<p>Свернуть</p>
			</template>
			<template v-else>
				<img src="/img/arrow-down.svg">
				<p>Ещё @{{ sideCommunitiesOrder.length - communitiesListLimit }}</p>
			</template>
		</div>
		<button class="community-new" v-on:click="user === false ? (throwMessage('Для этого действия нужно авторизоваться', 'error'),auth = 1) : communityWindow = 1;">
			<div class="icon">
				
			</div>
			<p>Создать</p>
		</button>
	</div>
	</div>
</div>