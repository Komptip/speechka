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
							<h1>Подтверждение регистрации</h1>
							<p>
								Приветствуем!<br/>
								<br/>
								Для окончания регистрации нажмите на кнопку ниже:
							</p>
							<a href="{{ config('app.url') }}registration-confirm/{{ $token }}">
								<button>Закончить регистрацию</button>
							</a>
						</div>
					</div>
				</th>
			</tr>
		</table>
	</body>
</html>