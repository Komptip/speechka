<header>
	<div class="left">
		<img src="/img/sidebar.svg" class="sidebar-btn" v-on:click="sidebarHidded = !sidebarHidded">
		<img src="/img/sidebar.svg" class="sidebar-btn mobile" v-on:click="sidebarHiddenMobile = !sidebarHiddenMobile">
		<a class="logo" href="/">
			<img src="/img/logo.svg" />
			<p>Спичка</p>
		</a>
	</div>
	<div class="right" v-if="!user">
		<div class="new-post" v-on:click="auth = true;">
			<img src="/img/enter.svg">
			<p>Войти</p>
		</div>
	</div>
	<div class="right" v-if="user">
		<div class="new-post" v-on:click="newPost">
			<img src="/img/write.svg">
			<p>Написать пост</p>
		</div>

		<div class="account">
			<div class="usr-pic" :style="'background-image: url(' + user.picture + ')'" v-on:click="accountDropdownHidden = !accountDropdownHidden">
				
			</div>
			<div class="drop-down" v-if="!accountDropdownHidden">
				<p class="username">@{{ user.name }}</p>
				<div class="button" v-on:click="myProfile">
					<div class="button">
						<img src="/img/profile.svg">
						<p>Мой профиль</p>
					</div>
				</div>
				<div class="button" v-on:click="settings.opened = true; accountDropdownHidden = true">
					<div class="button">
						<img src="/img/settings.svg">
						<p>Настройки</p>
					</div>
				</div>
				<div class="button" v-on:click="logOut">
					<div class="button">
						<img src="/img/exit.svg">
						<p>Выйти</p>
					</div>
				</div>
			</div>
		</div>

	</div>
</header>