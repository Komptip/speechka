var scrollListener = function(e){
	let bottomOfWindow = document.documentElement.scrollTop + window.innerHeight >= (document.querySelector('body').offsetHeight - 100);
      if (bottomOfWindow) {
      	let blackList = [];
      	let profileViewType = document.querySelector('meta[name="type"]').getAttribute('content');
		if(profileViewType == 'posts'){
	      	Object.keys(app.posts).forEach(function(postID){
	      		blackList.push(app.posts[postID].id);
	      	});
	        loadMorePosts(blackList);
	       } else {
	       	Object.keys(app.comments).forEach(function(commentID){
	      		blackList.push(app.comments[commentID]['id']);
	      	});
	        loadMoreComments(blackList);
	       } 
      }
};
function loadMorePosts(blackList){
	let formData = new FormData();

	if(app !== undefined){
		if(app.postsIsLoading){
			return;
		}
		app.postsIsLoading = true;
	}
	
	formData.append('user_id', document.querySelector('meta[name="user_id"]').getAttribute('content'));
	blackList.forEach(function(id){
		formData.append('black-list[]', id);
	});

	fetch('/data/post/get-by-user', {
		method: 'POST',
		headers: {'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')},
		body: formData
	})
	.then((response) => {
		return response.json();
	})
	.then((data) => {
		
		let formData = new FormData();
		data.forEach(function(id){
			formData.append('ids[]', id);
		});

		fetch('/data/post/get', {
			method: 'POST',
			headers: {'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')},
			body: formData
		})
		.then((response) => {
			return response.json();
		})
		.then((data) => {
			
			if(data.length === undefined){
				window.removeEventListener('scroll', scrollListener);
			} else {
				app.loadPosts(data);
			}

		});

	});
}

function loadMoreComments(blackList){
	let formData = new FormData();

	if(app !== undefined){
		if(app.commentsIsLoading){
			return;
		}
		app.commentsIsLoading = true;
	}
	
	formData.append('user_id', document.querySelector('meta[name="user_id"]').getAttribute('content'));
	blackList.forEach(function(id){
		formData.append('black-list[]', id);
	});

	fetch('/data/comment/get-by-user', {
		method: 'POST',
		headers: {'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')},
		body: formData
	})
	.then((response) => {
		return response.json();
	})
	.then((data) => {

		if(app.commentsOrder === false){
			app.commentsOrder = data;
		} else {
			data.forEach(function(commentOrder){
				app.commentsOrder.push(commentOrder);
			});
		}
		
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
			
			if(data.length === undefined){
				window.removeEventListener('scroll', scrollListener);
			} else {
				app.loadComments(data);
			}

		});

	});
}

function beforeMountProfile(){
	let profileViewType = document.querySelector('meta[name="type"]').getAttribute('content');
	if(profileViewType == 'posts'){
		loadMorePosts([]);
	} else {
		loadMoreComments([]);
	}
	
	window.addEventListener('scroll', scrollListener);
}