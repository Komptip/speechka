function loadComments(){
	let commentsFormData = new FormData();

	commentsFormData.append('post-id', document.querySelector('meta[name="post-id"]').getAttribute('content'));

	fetch('/data/comment/get-by-post', {
		method: 'POST',
		headers: {'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')},
		body: commentsFormData
	})
	.then((response) => {
		return response.json();
	})
	.then((data) => {

		app.commentsOrder = data;

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
			app.loadComments(data);
		});

	});
}

function beforeMountViewpost(){
	let formData = new FormData();

	formData.append('ids[]', document.querySelector('meta[name="post-id"]').getAttribute('content'));
	formData.append('full', 1);

	fetch('/data/post/get', {
		method: 'POST',
		headers: {'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')},
		body: formData
	})
	.then((response) => {
		return response.json();
	})
	.then((data) => {

		app.loadPosts(data);

	});

	loadComments();
}