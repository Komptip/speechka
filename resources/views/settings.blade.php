<div class="window-background" v-if="settings.opened">
	<div class="window settings">
		<img class="close" src="/img/close.svg" v-on:click="settings.opened = close;">
		<div class="box">
			<p class="headline">Настройки</p>
			<p class="sub-headline">Фото</p>
			<div class="photo-preview" v-if="settings.data.photoPreview || user.picture !== null" :style="'background-image: url(' + (settings.data.photoPreview ? settings.data.photoPreview : user.picture) + ')'">
				
			</div>
			<input type="file" class="file-uploader" accept=".jpeg,.png,.bmp,.jpg,.gif,.webp,.avif,.svg" v-on:change="onUploadPhoto">
			<button class="upload-photo" type="button" v-on:click="uploadPhoto">Загрузить</button>
			<p class="sub-headline">Смена пароля</p>
			<input placeholder="Новый пароль" name="new_password" type="password" minlength="6"  maxlength="30">
			<input placeholder="Старый пароль" name="old_password" type="password" minlength="6"  maxlength="30">
			<button :class="settingsIsLoading ? 'noactive' : ''" v-on:click="saveSettings">Сохранить</button>
		</div>
	</div>
</div>