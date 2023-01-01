var scrollListener = function(e){
	let bottomOfWindow = (window.pageYOffset || document.documentElement.scrollTop || document.body.scrollTop || 0) + window.innerHeight >= (document.querySelector('body').offsetHeight - 100);
      if (bottomOfWindow) {
      	let blackList = [];
		Object.keys(app.posts).forEach(function(postID){
      		blackList.push(app.posts[postID].id);
      	});
        loadMorePosts(blackList);
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
	
	formData.append('community_id', document.querySelector('meta[name="community_id"]').getAttribute('content'));
	blackList.forEach(function(id){
		formData.append('black-list[]', id);
	});

	fetch('/data/post/get-by-community', {
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

function beforeMountCommunity(app){
	app.currentCommunity = parseInt(document.querySelector('meta[name="community_id"]').getAttribute('content'));

	let request = app.getCommunitiesByID([app.currentCommunity]);

	Promise.all([request]).then((results) => {
	 	
		Object.keys(results[0]).forEach(function(id){
			app.communities[id] = results[0][id];
		});

	});


	fetch('/data/community/get-admined', {
		method: 'POST',
		headers: {'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')}
	})
	.then((response) => {
		app.catchResponse(response);
		return response.json();
	})
	.then((data) => {
		
		app.adminInCommunities = data;

	});

	loadMorePosts([]);
	
	window.addEventListener('scroll', scrollListener);
}