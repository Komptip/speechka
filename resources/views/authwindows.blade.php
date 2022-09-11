<div class="window-background" v-if="auth">
	<div class="window">
		<img class="close" src="/img/close.svg" v-on:click="auth = false;">
		<form v-on:submit="logIn" class="box" v-if="auth == 'login'">
			<p class="headline">Вход</p>
			<input placeholder="Почта" name="email" type="email" required maxlength="50">
			<input placeholder="Пароль" name="password" type="password" required minlength="6" maxlength="30">
			<div id="recaptcha"></div>
			<button :class="[authAwaiting ? 'noactive' : '']">Войти</button>
		</form>
		<form v-on:submit="passwordReset" class="box" v-else-if="auth == 'password-reset'">
			<p class="headline">Восстановление пароля</p>
			<input placeholder="Почта" name="email" type="email" required maxlength="50">
			<div id="recaptcha"></div>
			<button :class="[authAwaiting ? 'noactive' : '']">Восстановить пароль</button>
		</form>
		<form v-on:submit="signUp" class="box" v-else>
			<p class="headline">Регистрация</p>
			<input placeholder="Ваше имя" name="name" required maxlength="30">
			<input placeholder="Почта" name="email" type="email" required maxlength="50">
			<input placeholder="Пароль" name="password" type="password" required minlength="6" maxlength="30">
			<div id="recaptcha"></div>
			<button :class="[authAwaiting ? 'noactive' : '']">Зарегистрироваться</button>
		</form>
		<p class="switcher password-reset" v-if="auth == 'login'"><span v-on:click="auth = 'password-reset';">Забыли пароль?</span></p>
		<p class="switcher" v-if="auth == 'login'">Ещё нет аккаунта? <span v-on:click="auth = true;">Зарегистрировать</span></p>
		<p class="switcher" v-else>Есть аккаунт? <span v-on:click="auth = 'login';">Войти</span></p>
	</div>
</div>
