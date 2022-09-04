<div class="sidebar" v-if="!sidebarHidded">
	<div class="buttons">
		<a class="button" href="/popular">
			<img src="/img/popular.svg">
			<p>Популярное</p>
		</a>
		<a class="button" href="/new">
			<img src="/img/new.svg">
			<p>Свежее</p>
		</a>
	</div>
</div>

<div class="sidebar mobile" v-if="!sidebarHiddenMobile">
	<div class="left">
		<div class="buttons">
			<a class="button" href="/popular">
				<img src="/img/popular.svg">
				<p>Популярное</p>
			</a>
			<a class="button" href="/new">
				<img src="/img/new.svg">
				<p>Свежее</p>
			</a>
		</div>
	</div>
</div>