function beforeMountSidecomments(){
	fetch('/data/comment/newest', {
		method: 'POST',
		headers: {'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')},
	})
	.then((response) => {
		return response.json();
	})
	.then((data) => {

		app.sideComments = data;

		let formData = new FormData();
		data.forEach(function(id){
			formData.append('ids[]', id);
		});

		fetch('/data/comment/get', {
			method: 'POST',
			headers: {'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')},
			body: formData
		})
		.then((response) => {
			return response.json();
		})
		.then((data) => {
			app.loadComments(data, true);

			let formData = new FormData();

			data.forEach(function(value){
				formData.append('ids[]', value.post_id);
			});

			fetch('/data/post/get-titles-by-ids', {
				method: 'POST',
				body: formData,
				headers: {'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')},
			})
			.then((response) => {
				return response.json();
			})
			.then((data) => {

				app.sideCommentsPostsTitles = data;

			});
		});

	});
}