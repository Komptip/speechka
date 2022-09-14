<div class="window-background" v-if="communityWindow">
	<div class="window">
		<img class="close" src="/img/close.svg" v-on:click="communityWindow = false;">
		<form class="box centered" v-on:submit="createCommunity">
			<input type="file" name="photo" class="file-uploader" accept=".jpeg,.png,.bmp,.jpg,.gif,.webp,.avif,.svg" v-on:change="onUploadCommunityPhoto">
			<div class="community-photo" v-on:click="uploadPhoto" :style="[newCommunityPhotoPreview ? ('background-image: url(' + newCommunityPhotoPreview + '); background-size: cover;') : '']">
				
			</div>
			<p class="headline">Новый подсайт</p>
			<input placeholder="Название" name="name" required maxlength="50">
			<input placeholder="Описание" name="description"  maxlength="250">
			<button :class="[authAwaiting ? 'noactive' : '']">Создать</button>
		</form>
	</div>
</div>
<div class="window-background" v-if="communitySettingsOpened">
	<div class="window settings">
		<img class="close" src="/img/close.svg" v-on:click="communitySettingsOpened = false;">
		<form class="box centered" v-if="communitiesSettings[currentCommunity]" v-on:submit="saveCommunity">
			<p class="headline">Настроить подсайт</p>
			<input type="file" name="picture" class="file-uploader" accept=".jpeg,.png,.bmp,.jpg,.gif,.webp,.avif,.svg" v-on:change="onChangeCommunityPhoto($event, currentCommunity)">
			<div class="community-photo" v-on:click="uploadPhoto" :style="[communitiesSettings[currentCommunity].picture && !communitiesSettings[currentCommunity].newpicture ? ('background-image: url(' + communitiesSettings[currentCommunity].picture + '); background-size: cover;') : '', communitiesSettings[currentCommunity].newpicture ? ('background-image: url(' + communitiesSettings[currentCommunity].newpicture + '); background-size: cover;') : '']">
				
			</div>
			<input placeholder="Название" v-model="communitiesSettings[currentCommunity].name"  name="name" required maxlength="50">
			<input placeholder="Описание" v-model="communitiesSettings[currentCommunity].description" name="description"  maxlength="250">
			<template v-if="communitiesSettings[currentCommunity].admins">
				<p class="sub-headline">Администраторы</p>
				<div class="list">
					<div class="item">
						<input placeholder="ID или имя">
						<button v-on:click="addCommunityAdmin($event)" type="button">Добавить</button>
					</div>
					<div class="item" v-for="userID in communitiesSettings[currentCommunity].admins">
						<div class="icon" :style="'background-image: url(' + users[userID].picture + ');'">
							
						</div>
						<p class="name">@{{ users[userID].name }}</p>
						<div class="remove" v-on:click="removeCommunityAdmin(userID)" :style="userID == user.id ? 'background-image: url(/img/close.svg);' : ''">
							
						</div>
					</div>
				</div>
			</template>
			<template v-if="communitiesSettings[currentCommunity].blacklist">
				<p class="sub-headline">Черный список</p>
				<div class="list">
					<div class="item">
						<input placeholder="ID или имя">
						<button v-on:click="addUserToCommunityBlacklist($event)" type="button">Добавить</button>
					</div>
					<div class="item" v-for="userID in communitiesSettings[currentCommunity].blacklist">
						<div class="icon" :style="'background-image: url(' + users[userID].picture + ');'">
							
						</div>
						<p class="name">@{{ users[userID].name }}</p>
						<div class="remove" v-on:click="removeUserFromCommunityBlacklist(userID)">
							
						</div>
					</div>
				</div>
			</template>
			<button :class="[communityIsSaving ? 'noactive' : '']">Сохранить</button>
		</form>
	</div>
</div>