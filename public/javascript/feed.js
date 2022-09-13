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

	blackList.forEach(function(id){
		formData.append('black-list[]', id);
	});

	if(document.querySelector('meta[name="post-id"]') !== null){
		formData.append('black-list[]', document.querySelector('meta[name="post-id"]').getAttribute('content'))
	}

	let requestURL = '/data/post/popular';

	let feedType = document.querySelector('meta[name="feed-type"]').getAttribute('content');

	if(feedType == 'newest'){
		requestURL = '/data/post/newest';
	}

	fetch(requestURL, {
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

function beforeMountFeed(){
	loadMorePosts([]);
	 window.addEventListener('scroll', scrollListener);
}