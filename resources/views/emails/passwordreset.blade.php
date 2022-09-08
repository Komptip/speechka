<!DOCTYPE html>
<html>
	<head>
		<meta charset="utf-8">
		<link href="https://fonts.googleapis.com/css2?family=Roboto:wght@700&display=swap" rel="stylesheet">
	</head>
	<body>
		<table width="100%">
			<tr>
				<th>
					<div class="body">
						<div class="logo">
							{!! file_get_contents('img/favicon.svg') !!}
							<p>{{ config('app.name') }}</p>
						</div>
					</div>
				</th>
			</tr>
			<tr>
				<th>
					<div class="body">
						<div class="message">
							<h1>Восстановление пароля</h1>
							<p>
								Приветствуем! На ваш аккаунт поступил запрос о восстановлении пароля.<br/>
								<br/>
								Если вы не отправляли его - Просто игнорируйте это письмо.<br/>
								<br/>
								Для сброса пароля нажмите на кнопку ниже:
							</p>
							<a href="{{ url('password-reset/' . $token) }}">
								<button>Восстановить пароль</button>
							</a>
						</div>
					</div>
				</th>
			</tr>
		</table>
	</body>
</html>