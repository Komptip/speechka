<!DOCTYPE html>
<html>
	<head>
		@include('basicshead')
		<link rel="stylesheet" type="text/css" href="/css/newpassword.css">
		<link rel="stylesheet" type="text/css" href="/css/window.css">

		<meta name="token" content="{{ $token }}">
	</head>
	<body id=app v-cloak>

		<div class="content">

			<div class="window">
				<form v-on:submit="newPassword" class="box" v-else>
					<p class="headline">Сброс пароля</p>
					<input placeholder="Новый пароль" name="password" type="password" required minlength="6" maxlength="30">
					<div id="recaptcha"></div>
					<button :class="[authAwaiting ? 'noactive' : '']">Сменить пароль</button>
				</form>
			</div>

		</div>

		@include('messages')

		<script type="text/javascript" src="/javascript/app.js"></script>
	</body>
	
</script>
</html>