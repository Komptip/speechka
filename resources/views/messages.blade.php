<div class="messages" v-if="message" v-on:click="message = false">
	<img :src="message['type'] == 'error' ? '/img/error.svg' : '/img/success.svg'">
	<p>@{{ message['text'] }}</p>
</div>