
var app = Vue.createApp(
	{
		data() {
			return {
				sidebarHidded: false,
				sidebarHiddenMobile: true,
				accountDropdownHidden: true,
				user: false,
				auth: false,
				authAwaiting: false,
				error: false,
				posts: false,
				postViewID: false,
				postsIsLoading: false,
				commentsIsLoading: false,
				users: {},
				message: false,
				sComments: false,
				comments: false,
				commentsOrder: false,
				postSended: false,
				profileViewType: false,
				settingsIsLoading: false,
				postIsDeleting: false,
				sideCommentsHidden: false,
				sideComments: false,
				sideCommentsPostsTitles: false,
				postForEdit: false,
				feedType: false,
				commentsreplies: {
					'start': {
						'attachment': false,
						'sended': false
					},
					'reply': {
						'reply_to': false,
						'attachment': false,
						'sended': false
					},
					'end': {
						'attachment': false,
						'sended': false
					}
					
				},
				settings: {
					opened: false,
					data: {
						photoPreview: false
					}
				}
			}
		},
		watch: {
			auth: {
				handler: function(newValue){
					if(newValue !== false){
						if(grecaptcha !== undefined){
							if(grecaptcha.render !== undefined){
								setTimeout(function(){
									app.refreshCaptcha();
								}, 0);
							}
						}
					}
				},
				immediate: true
			}
		},
		beforeMount(){
			let feedTypeElement = document.querySelector('meta[name="feed-type"]');

			if(feedTypeElement !== null){
				this.feedType = feedTypeElement.getAttribute('content');
			}
			this.getUserData();

			let beforeMountFunctions = ['beforeMountProfile', 'beforeMountFeed', 'beforeMountViewpost', 'beforeMountSidecomments'];

			beforeMountFunctions.forEach(function(beforeMountFunction){
				if(typeof window[beforeMountFunction] === "function"){
					window[beforeMountFunction]();
				}
			});
		},
		methods: {
			refreshCaptcha: function(){
				grecaptcha.render("recaptcha", {
		            sitekey: '6LfMf8YhAAAAAPZWfXU3NQubfDcnphJbHFFUQefB',
		            callback: function () {
		                console.log('recaptcha callback');
		            }
		        });
			},
			catchResponse: function(response){
				if(!response.ok){
					app.throwMessage(`Произошла ошибка ${response.status}, повторите попытку позже`, 'error');
				}
			},
			viewPost: function(post){
				window.location.href = '/p/' + post.id;
			},
			parseURL: function(url){
				setTimeout(function(){
					twttr.widgets.load();
				}, 100);
				if(!url.startsWith('http://') && !url.startsWith('https://')){
					url = 'https://' + url;
				}
				let link = new URL(url);

				if(link.hostname === 'www.youtube.com' || link.hostname === 'youtube.com' || link.hostname === 'youtu.be' || link.hostname === 'www.youtu.be'){
					let regExp = /^.*(youtu.be\/|v\/|u\/\w\/|embed\/|watch\?v=|\&v=)([^#\&\?]*).*/;
				    let match = url.match(regExp);

				    if (match && match[2].length == 11) {
				        return {'type': 'youtube', 'key': match[2]};
				    } else {
				        return false;
				    }
				}

				else if (link.hostname === 't.me' || link.hostname === 'www.t.me'){
					let linkParts = url.split('/');
					return {'type': 'telegram', 'url': linkParts.at(-2) + '/' + linkParts.at(-1).split('?')[0]};
				}

				else if(link.hostname === 'www.twitter.com' || link.hostname === 'twitter.com'){
					return {'type': 'twitter'};
				}

				else {
					return {'type': 'none'};
				}
			},
			sharePost: function(post){
				const el = document.createElement('textarea');
				el.value = window.location.hostname + '/p/' + post.id;
				el.setAttribute('readonly', '');
				el.style.position = 'absolute';
				el.style.left = '-9999px';
				document.body.appendChild(el);
				el.select();
				document.execCommand('copy');
				document.body.removeChild(el);
				app.throwMessage('Ссылка на пост скопирована', 'success');
			},
			hideMobileSidebar: function(event){
				if(event.target.classList.contains('sidebar')){
					app.sidebarHiddenMobile = true;
				}
			},
			handleResponse: function(data){
				if(data.action == 'error'){
					app.throwMessage(data.data, 'error');
				}
				if(data.action == 'success'){
					app.throwMessage(data.data, 'success');
				}
				if(data.action == 'refresh'){
					location.reload();
				}
				if(data.action == 'reload-user-data'){
					app.getUserData();
					app.auth = false;
				}
				if(data.action == 'redirect'){
					window.location.href = data.data;
				}
			},
			viewUserProfile: function(id){
				window.location.href = '/u/' + id;
			},
			viewCommentOrigin: function(id){
				window.location.href = '/p/' + id;
			},
			deletePost: function(post){
				post.showMore = false;
				if(app.postIsDeleting){
					return;
				}
				app.postIsDeleting = true;
				let formData = new FormData();
				formData.append('post_id', post.id);
				fetch('/data/post/delete', {
					method: 'POST',
					body: formData,
					headers: {'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')}
				})
				.then((response) => {
					app.postIsDeleting = false;
					app.catchResponse(response);
					return response.json();
				})
				.then((data) => {
					post.active = 0;
					app.handleResponse(data);
				});
			},
			editPost: function(post){
				post.showMore = false;
				window.location.href = '/post/edit/' + post.id;
			},
			republishPost: function(post){
				post.showMore = false;
				if(app.postIsDeleting){
					return;
				}
				app.postIsDeleting = true;
				let formData = new FormData();
				formData.append('post_id', post.id);
				fetch('/data/post/recreate', {
					method: 'POST',
					body: formData,
					headers: {'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')}
				})
				.then((response) => {
					app.postIsDeleting = false;
					app.catchResponse(response);
					return response.json();
				})
				.then((data) => {
					post.active = 1;
					app.handleResponse(data);
				});
			},
			getUserData: function(){
				fetch('/data/auth/get', {
					method: 'POST',
					headers: {'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')}
				})
				.then((response) => {
					return response.json();
				})
				.then((data) => {
					app.user = data.user;
				});
			},
			viewProfileType: function(type){
				let userID = document.querySelector('meta[name="user_id"]').getAttribute('content');
				window.location.href = '/u/' + userID + '/' + type;
			},
			throwMessage: function(text, type){
				app.message = {
					'type': type,
					'text': text
				}

				setTimeout(function(){
					app.message = false;
				}, 10000);

			},
			signUp: function(event){
				event.preventDefault();
				let recaptcha = grecaptcha.getResponse();

				if(recaptcha.length < 1){
					return app.throwMessage('Пройдите капчу', 'error');
				}

				app.authAwaiting = true;
				let form = event.target.closest('form');
				let formData = new FormData(form);
				fetch('/data/auth/signup', {
					method: 'POST',
					body: formData,
					headers: {'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')}
				})
				.then((response) => {
					app.catchResponse(response);
					grecaptcha.reset();
					app.authAwaiting = false;
					return response.json();
				})
				.then((data) => {
					app.handleResponse(data);
					if(data.action == 'success'){
						form.querySelector('input[name="name"]').value = null;
						form.querySelector('input[name="email"]').value = null;
						form.querySelector('input[name="password"]').value = null;
					}
				});
			},
			logIn: function(event){
				event.preventDefault();
				let recaptcha = grecaptcha.getResponse();

				if(recaptcha.length < 1){
					return app.throwMessage('Пройдите капчу', 'error');
				}

				app.authAwaiting = true;
				let form = event.target.closest('form');
				let formData = new FormData(form);
				fetch('/data/auth/login', {
					method: 'POST',
					body: formData,
					headers: {'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')}
				})
				.then((response) => {
					app.catchResponse(response);
					grecaptcha.reset();
					app.authAwaiting = false;
					return response.json();
				})
				.then((data) => {
					app.handleResponse(data);
				});
			},
			passwordReset: function(event){
				event.preventDefault();
				let recaptcha = grecaptcha.getResponse();

				if(recaptcha.length < 1){
					return app.throwMessage('Пройдите капчу', 'error');
				}

				app.authAwaiting = true;
				let form = event.target.closest('form');
				let formData = new FormData(form);
				fetch('/data/auth/password-reset', {
					method: 'POST',
					body: formData,
					headers: {'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')}
				})
				.then((response) => {
					app.catchResponse(response);
					grecaptcha.reset();
					app.authAwaiting = false;
					return response.json();
				})
				.then((data) => {
					app.handleResponse(data);
					if(data.action == 'success'){
						form.querySelector('input[name="email"]').value = null;
					}
				});
			},
			newPassword: function(event){
				event.preventDefault();

				app.authAwaiting = true;
				let form = event.target.closest('form');

				let token = document.querySelector('meta[name="token"]').getAttribute('content');

				let formData = new FormData(form);
				formData.append('token', token);
				fetch('/data/auth/new-password-set', {
					method: 'POST',
					body: formData,
					headers: {'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')}
				})
				.then((response) => {
					app.catchResponse(response);
					app.authAwaiting = false;
					return response.json();
				})
				.then((data) => {
					app.handleResponse(data);
				});
			},
			logOut: function(event){
				fetch('/data/auth/logout', {
					method: 'POST',
					headers: {'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')}
				})
				.then((response) => {
					app.catchResponse(response);
					return response.json();
				})
				.then((data) => {
					app.handleResponse(data);
				});
			},
			onUploadPhoto: function(event){
				app.settings.data.photoPreview = URL.createObjectURL(event.target.files[0]);
			},
			uploadPhoto: function(event){
				event.target.closest('.box').querySelector('.file-uploader').click();
			},
			saveSettings: function(event){
				event.preventDefault();

				app.settingsIsLoading = true;

				let form = event.target.closest('.box');
				let photo = form.querySelector('.file-uploader').files[0];

				let formData = new FormData();

				if(photo !== undefined){
					formData.append('photo', photo);
				}

				if(form.querySelector('input[name="new_password"]').value.length > 0){

					formData.append('new_password', form.querySelector('input[name="new_password"]').value);
					formData.append('old_password', form.querySelector('input[name="old_password"]').value);
				}

				fetch('/data/settings/user/set', {
					method: 'POST',
					body: formData,
					headers: {'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')}
				})
				.then((response) => {
					app.settingsIsLoading = false;
					app.catchResponse(response);
					return response.json();
				})
				.then((data) => {
					app.getUserData();
					app.handleResponse(data);

					if(data.action == 'success'){
						form.querySelector('input[name="new_password"]').value = null;
						form.querySelector('input[name="old_password"]').value = null;
					}
				});
			},
			reply: function(id){
				app.commentsreplies['reply']['attachment'] = false;
				app.commentsreplies['reply']['reply_to'] = id;
			},
			hideReply: function(){
				app.commentsreplies['reply']['attachment'] = false;
				app.commentsreplies['reply']['reply_to'] = false;
			},
			myProfile: function(event){
				window.location.replace('/u/' + this.user.id);
			},
			newPost: function(event){
				window.location.replace('/post/new');	
			},
			publishPost: function(event){
				app.postSended = true;
				editor.save().then((outputData) => {
					if(event.target.closest('.large').querySelector('h1').innerText.length < 1){
						if(outputData.blocks.length < 1){
							app.postSended = false;
							return app.throwMessage('Пост не может быть пустым', 'error');
						} else {
							if(outputData.blocks.length == 1){
								if(outputData.blocks[0].type == 'paragraph'){
									let convertTextarea = document.createElement("textarea");
								    convertTextarea.innerHTML = outputData.blocks[0].data.text;
								    let convertedText =  convertTextarea.value;
								    convertTextarea.remove();
									if(convertedText.replaceAll(/\s/g,'').length < 1){
										app.postSended = false;
										return app.throwMessage('Пост не может быть пустым', 'error');		
									}
								}
							}
						}
					}
					if(event.target.closest('.large').querySelector('h1').innerText.length > 120){
						return app.throwMessage('Заголвок не может привышать 120 символов', 'error');
					}
					let formdata = new FormData();
					fetch('/data/post/create', {
						method: 'POST',
						body: JSON.stringify({'data': outputData.blocks, 'title': event.target.closest('.large').querySelector('h1').innerText}),
						headers: {'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')}
					})
					.then((response) => {
						app.postSended = false;
						app.catchResponse(response);
						return response.json();
					})
					.then((data) => {
						app.handleResponse(data);
					});
				});
			},
			savePost: function(event){
				app.postSended = true;
				editor.save().then((outputData) => {
					if(event.target.closest('.large').querySelector('h1').innerText.length < 1){
						if(outputData.blocks.length < 1){
							app.postSended = false;
							return app.throwMessage('Пост не может быть пустым', 'error');
						} else {
							if(outputData.blocks.length == 1){
								if(outputData.blocks[0].type == 'paragraph'){
									let convertTextarea = document.createElement("textarea");
								    convertTextarea.innerHTML = outputData.blocks[0].data.text;
								    let convertedText =  convertTextarea.value;
								    convertTextarea.remove();
									if(convertedText.replaceAll(/\s/g,'').length < 1){
										app.postSended = false;
										return app.throwMessage('Пост не может быть пустым', 'error');		
									}
								}
							}
						}
					}
					if(event.target.closest('.large').querySelector('h1').innerText.length > 120){
						return app.throwMessage('Заголвок не может привышать 120 символов', 'error');
					}
					fetch('/data/post/edit', {
						method: 'POST',
						body: JSON.stringify({'data': outputData.blocks, 'title': event.target.closest('.large').querySelector('h1').innerText, 'id': document.querySelector('meta[name="post-id"]').getAttribute('content')}),
						headers: {'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')}
					})
					.then((response) => {
						app.postSended = false;
						app.catchResponse(response);
						return response.json();
					})
					.then((data) => {
						app.handleResponse(data);
					});
				});
			},
			formatTime: function(timestamp){
				let timeDifference = Math.floor(Date.now() / 1000) - timestamp;

				let previousLevelValue = timeDifference;

				let steps = [
					{
						'name': {
							'single': 'секунду',
							'multi': 'секунд'
						},
						'max': 60
					},
					{
						'name': {
							'single': 'минуту',
							'multi': 'минут'
						},
						'max': 60
					},
					{
						'name': {
							'single': 'час',
							'multi': 'часов'
						},
						'max': 24
					},
					{
						'name': {
							'single': 'день',
							'multi': 'дней'
						},
						'max': 30,
					},
					{
						'name': {
							'single': 'месяц',
							'multi': 'месяцев'
						},
						'max': 12
					},
					{
						'name': {
							'single': 'год',
							'multi': 'лет'
						},
						'max': Infinity
					}
				];

				let formattedText;

				steps.forEach(function(step){

					if(formattedText !== undefined){
						return;
					}
					
					if(step['max'] <= previousLevelValue){

						previousLevelValue = Math.floor(previousLevelValue / step['max']);

						return;
					}

					name = false;

					if(previousLevelValue < 2){
						name = step['name']['single'];
					}
					else {
						name = step['name']['multi'];
					}

					formattedText = `${previousLevelValue} ${name}`;

				});

				return formattedText;
			},
			rate: function(post, type){
				if(app.user == false){
					app.throwMessage('Для этого действия нужно авторизоваться', 'error');
					return app.auth = true;
				}
				if(app.user.id == post.author_id){
					return app.throwMessage('Нельзя лайкать самого себя', 'error')
				}
				if(post.grade == type){
					if(type == true){
						post.ratingValue--;
					} else {
						post.ratingValue++;
					}
					post.grade = null;
				} else {
					if(type == true){
						if(post.grade === false){
							post.ratingValue += 2;
						} else {
							post.ratingValue++;
						}
					} else {
						if(post.grade === true){
							post.ratingValue -= 2;
						} else {
							post.ratingValue--;
						}
					}
					post.grade = type;
				}

				let formData = new FormData();
				formData.append('entity_id', post.id);
				formData.append('type', post.grade);
				fetch('/data/post/rating/set', {
					method: 'POST',
					body: formData,
					headers: {'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')}
				})
				.then((response) => {
					app.catchResponse(response);
					return response.json();
				})
				.then((data) => {
					app.handleResponse(data);
					app.refreshRating(post);
				});
			},
			rateComment: function(comment, type){
				if(app.user == false){
					app.throwMessage('Для этого действия нужно авторизоваться', 'error');
					return app.auth = true;
				}
				if(app.user.id == comment.author_id){
					return app.throwMessage('Нельзя лайкать самого себя', 'error')
				}
				if(comment['grade'] == type){
					if(type == true){
						comment['rating_value']--;
					} else {
						comment['rating_value']++;
					}
					comment['grade'] = null;
				} else {
					if(type == true){
						if(comment['grade'] === false){
							comment['rating_value'] += 2;
						} else {
							comment['rating_value']++;
						}
					} else {
						if(comment['grade'] === true){
							comment['rating_value'] -= 2;
						} else {
							comment['rating_Value']--;
						}
					}
					comment['grade'] = type;
				}

				let formData = new FormData();
				formData.append('entity_id', comment['id']);
				formData.append('type', comment['grade']);
				fetch('/data/comment/rating/set', {
					method: 'POST',
					body: formData,
					headers: {'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')}
				})
				.then((response) => {
					app.catchResponse(response);
					return response.json();
				})
				.then((data) => {
					app.handleResponse(data);
					app.refreshCommentRating(comment);
				});
			},
			countRating: function(rating){
				let ratingval = 0;

				Object.keys(rating).forEach(function(user){
					if(rating[user] == 1){
						ratingval++;
					} else {
						ratingval--;
					}
				});

				return ratingval;
			},
			hidePost: function(post){
				post.showMore = false;
				post.hidden = true;
				app.throwMessage(`Пост #${post.id} успешно скрыт из ленты`);
			},
			loadPosts: function(data){
				if(app.posts === false){
					app.posts = [];
				}

				if(document.querySelector('meta[name="post-id"]') !== null){
					app.postViewID = document.querySelector('meta[name="post-id"]').getAttribute('content');
				}

				let usersIds = [];

				data.forEach(function(post){
					usersIds = usersIds.concat(Object.keys(post['rating']));
					usersIds.push(post.author_id);
				});

				usersIds = [...new Set(usersIds)];

				let request = app.getUsersByID(usersIds);

				Promise.all([request]).then((results) => {
				 	let users = results[0];
				 	Object.keys(users).forEach(function(userID){
						app.users[userID] = users[userID];
					});

					data.forEach(function(post){
						post.ratingValue = app.countRating(post['rating']);
						post.showMore = false;
						post.hidden = false;
						app.posts.push(post);
					});

					app.postsIsLoading = false;
				});
			},
			loadComments: function(data, sideComments=false){
				if(app.comments === false){
					app.comments = [];
				}

				let usersIds = [];

				data.forEach(function(comment){
					usersIds = usersIds.concat(Object.keys(comment['rating']));
					usersIds.push(comment.author_id);
				});

				usersIds = [...new Set(usersIds)];

				let request = app.getUsersByID(usersIds);

				Promise.all([request]).then((results) => {
				 	let users = results[0];
				 	Object.keys(users).forEach(function(userID){
						app.users[userID] = users[userID];
					});

				 	data.forEach(function(comment){
				 		if(!sideComments){
					 		if(comment['sub_comments']){
					 			comment['sub_comments']['opened'] = true;
					 		}
					 		comment['rating_value'] = app.countRating(comment['rating']);

							app.comments[comment['id']] = comment;
				 		} else {
				 			if(!app.sComments){
				 				app.sComments = [];
				 			}

				 			app.sComments[comment['id']] = comment;	
				 		}
					});

					app.commentsIsLoading = false;
				 });
			},
			refreshRating: function(post){
				let formData = new FormData();
				formData.append('entity_id', post.id);
				fetch('/data/post/rating/get', {
					method: 'POST',
					body: formData,
					headers: {'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')}
				})
				.then((response) => {
					return response.json();
				})
				.then((data) => {
					let usersRequest = app.getUsersByID(Object.keys(data));

					Promise.all([usersRequest]).then(results => {
						users = results[0];
						Object.keys(users).forEach(function(userID){
							app.users[userID] = users[userID];
						});
						post.ratingValue = app.countRating(data);
						
						post.rating = data;
					});
				});
			},
			countSubComments: function(comment){
				let countOfSubcomments = 0;
				if(comment['sub_comments']['ids'] !== undefined){
					countOfSubcomments += comment['sub_comments']['ids'].length;

					comment['sub_comments']['ids'].forEach(function(subComment){
						countOfSubcomments += app.countSubComments(app.comments[subComment]);
					});	

				}

				return countOfSubcomments;
			},
			refreshCommentRating: function(comment){
				let formData = new FormData();
				formData.append('entity_id', comment['id']);
				fetch('/data/comment/rating/get', {
					method: 'POST',
					body: formData,
					headers: {'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')}
				})
				.then((response) => {
					return response.json();
				})
				.then((data) => {
					let usersRequest = app.getUsersByID(Object.keys(data));

					Promise.all([usersRequest]).then(results => {
						users = results[0];
						Object.keys(users).forEach(function(userID){
							app.users[userID] = users[userID];
						});
						
						comment['rating_value'] = app.countRating(data);
						comment['rating'] = data;
					});
				});
			},
			removeComment: function(id){
				let formData = new FormData();
				formData.append('entity_id', id);
				fetch('/data/comment/remove', {
					method: 'POST',
					body: formData,
					headers: {'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')}
				})
				.then((response) => {
					app.catchResponse(response);
					return response.json();
				})
				.then((data) => {
					app.handleResponse(data);
					loadComments();
				});
			},
			unremoveComment: function(id){
				let formData = new FormData();
				formData.append('entity_id', id);
				fetch('/data/comment/unremove', {
					method: 'POST',
					body: formData,
					headers: {'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')}
				})
				.then((response) => {
					app.catchResponse(response);
					return response.json();
				})
				.then((data) => {
					app.handleResponse(data);
					loadComments();
				});
			},
			removePosts: function(id){
				let formData = new FormData();
				formData.append('entity_id', id);
				fetch('/data/post/remove', {
					method: 'POST',
					body: formData,
					headers: {'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')}
				})
				.then((response) => {
					app.catchResponse(response);
					return response.json();
				})
				.then((data) => {
					app.handleResponse(data);
					loadComments();
				});
			},
			unremovePosts: function(id){
				let formData = new FormData();
				formData.append('entity_id', id);
				fetch('/data/post/unremove', {
					method: 'POST',
					body: formData,
					headers: {'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')}
				})
				.then((response) => {
					app.catchResponse(response);
					return response.json();
				})
				.then((data) => {
					app.handleResponse(data);
					loadComments();
				});
			},
			banUser: function(id){
				let formData = new FormData();
				formData.append('entity_id', id);
				fetch('/data/user/ban', {
					method: 'POST',
					body: formData,
					headers: {'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')}
				})
				.then((response) => {
					app.catchResponse(response);
					return response.json();
				})
				.then((data) => {
					app.handleResponse(data);
					loadComments();
				});
			},
			unbanUser: function(id){
				let formData = new FormData();
				formData.append('entity_id', id);
				fetch('/data/user/unban', {
					method: 'POST',
					body: formData,
					headers: {'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')}
				})
				.then((response) => {
					app.catchResponse(response);
					return response.json();
				})
				.then((data) => {
					app.handleResponse(data);
					loadComments();
				});
			},
			uplaodAttachment: function(event){
				event.target.previousSibling.click();
			},

			onUploadAttachment: function(event, id){
				app.commentsreplies[id]['attachment'] = URL.createObjectURL(event.target.files[0]);
			},

			removeAttachment: function(event, id){
				event.target.closest('.attachment').nextSibling.querySelector('.file-uploader').value = null;
				app.commentsreplies[id]['attachment'] = false;
			},

			publishComment: function(event, id, reply=undefined){
				if(app.user == false){
					app.throwMessage('Для этого действия нужно авторизоваться', 'error');
					return app.auth = true;
				}

				let comment = event.target.closest('.comment-editor');
				let commentText = comment.querySelector('.text').innerText;
				let file = comment.querySelector('.file-uploader').files[0];

				if(commentText.replace(/^(&nbsp;|\s)*/, '').length < 1 && file === undefined){
					return app.throwMessage('Комментарий не может быть пустым', 'error');
				}

				if(commentText.length > 1000){
					return app.throwMessage('Комментарий не может привышать 1000 символов', 'error');
				}


				app.commentsreplies[id]['sended'] = true;

				let formData = new FormData();
				formData.append('text', commentText);
				formData.append('attachment', file);
				formData.append('post-id', app.postViewID);
				if(reply !== undefined){
					formData.append('reply-id', reply);
				}


				fetch('/data/comment/create', {
					method: 'POST',
					body: formData,
					headers: {'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')}
				})
				.then((response) => {
					app.commentsreplies[id]['sended'] = false;
					app.catchResponse(response);
					return response.json();
				})
				.then((data) => {
					app.handleResponse(data);
					event.target.closest('.comment-editor').querySelector('.file-uploader').value = null;
					event.target.closest('.comment-editor').querySelector('.text').innerHTML = null;
					app.commentsreplies[id]['attachment'] = false;
					if(id == 'reply'){
						app.commentsreplies[id]['reply_to'] = false;
					}
					loadComments();
				});
			},
			escapeLinksFromText: function(text){

				let Rexp = /((http|https|ftp):\/\/[\w?=&.\/-;#~%-]+(?![\w\s?&.\/;#~%"=-]*>))/g;
             
           		 return text.replace(Rexp, "<a href='$1' target='_blank'>$1</a>");
			},
			getUsersByID: async function(usersIds){
				let formData = new FormData();
				usersIds.forEach(function(id){
					if(app.users[id] === undefined){
						formData.append('ids[]', id);
					}
				});

				return await fetch('/data/users/get', {
					method: 'POST',
					headers: {'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')},
					body: formData
				})
				.then((response) => {
					return response.json();
				})
				.then((data) => {
					return data;
				});
			}
		}
	}
).component('rating-up', {
	props: ['grade'],
	template: `
		<?xml version="1.0" standalone="no"?>
		<!DOCTYPE svg PUBLIC "-//W3C//DTD SVG 20010904//EN"
		 "http://www.w3.org/TR/2001/REC-SVG-20010904/DTD/svg10.dtd">
		<svg version="1.0" xmlns="http://www.w3.org/2000/svg"
		 width="24.000000pt" height="24.000000pt" viewBox="0 0 512.000000 512.000000"
		 preserveAspectRatio="xMidYMid meet" style="display: block;transform:rotate(180deg);">

			<g transform="translate(0.000000,512.000000) scale(0.100000,-0.100000)"
			:fill="[grade == true ? '#07A23B' : '#000000']" stroke="none">
			<path d="M277 4009 c-103 -24 -197 -103 -244 -204 -23 -51 -28 -73 -27 -145 0
			-160 -96 -52 1192 -1342 777 -778 1160 -1155 1191 -1172 73 -39 158 -53 234
			-37 34 7 83 24 108 37 31 17 414 394 1191 1172 1288 1290 1192 1182 1192 1342
			0 72 -4 94 -28 147 -84 184 -308 262 -491 171 -26 -13 -388 -368 -1037 -1016
			l-998 -997 -998 997 c-652 651 -1011 1003 -1037 1016 -76 37 -170 49 -248 31z"/>
			</g>
		</svg>

	`
}).component('rating-down', {
	props: ['grade'],
	template: `
		<?xml version="1.0" standalone="no"?>
			<!DOCTYPE svg PUBLIC "-//W3C//DTD SVG 20010904//EN"
			 "http://www.w3.org/TR/2001/REC-SVG-20010904/DTD/svg10.dtd">
			<svg version="1.0" xmlns="http://www.w3.org/2000/svg"
			 width="24.000000pt" height="24.000000pt" viewBox="0 0 512.000000 512.000000"
			 preserveAspectRatio="xMidYMid meet">

			<g transform="translate(0.000000,512.000000) scale(0.100000,-0.100000)"
			:fill="[grade == false ? '#e52e3a' : '#000000']" stroke="none">
			<path d="M277 4009 c-103 -24 -197 -103 -244 -204 -23 -51 -28 -73 -27 -145 0
			-160 -96 -52 1192 -1342 777 -778 1160 -1155 1191 -1172 73 -39 158 -53 234
			-37 34 7 83 24 108 37 31 17 414 394 1191 1172 1288 1290 1192 1182 1192 1342
			0 72 -4 94 -28 147 -84 184 -308 262 -491 171 -26 -13 -388 -368 -1037 -1016
			l-998 -997 -998 997 c-652 651 -1011 1003 -1037 1016 -76 37 -170 49 -248 31z"/>
			</g>
		</svg>
	`
}).component('sub-comments', {
	props: ['app', 'id'],
	template: `
		<div class="sub-comments" v-if="app.comments[id]['sub_comments'] || app.commentsreplies['reply']['reply_to'] == id">
			<template v-if="app.comments[id]['sub_comments']['opened'] || app.commentsreplies['reply']['reply_to'] == id">
				<div class="branch" v-on:click="app.comments[id]['sub_comments']['opened'] = false;">
					
				</div>
				<div class="sub-comments-list">
					<div class="comment-editor" v-if="app.commentsreplies['reply']['reply_to'] == id">
						<p class="text" contenteditable placeholder="Написать комментарий..."></p>
						<div class="attachment" v-if="app.commentsreplies['reply']['attachment']" :style="'background-image: url(' + app.commentsreplies['reply']['attachment']  + ')'">
							<div class="remove" v-on:click="app.removeAttachment($event, 'reply')"></div>
						</div>
						<div class="bottom">
							<input type="file" accept=".jpeg,.png,.bmp,.jpg,.gif,.webp,.avif,.svg" class="file-uploader" v-on:change="app.onUploadAttachment($event, 'reply')" required>
							<img src="/img/image.svg" :class="['image', app.commentsreplies['reply']['attachment'] ? 'noactive' : '']" v-on:click="app.uplaodAttachment">
							<p class="cancel" v-on:click="app.hideReply">Отменить</p>
							<button :class="[app.commentsreplies['reply']['sended'] ? 'noactive' : '']" v-on:click="app.publishComment($event, 'reply', id)">Отправить</button>
						</div>
					</div>
					<template v-if="app.comments[id]['sub_comments']">
						<template v-if="app.comments[id]['sub_comments']['opened']">
							<comment v-for="commentID in app.comments[id]['sub_comments']['ids']" :app="app" :id="commentID"></comment>
						</template>
					</template>
				</div>
			</template>
		</div>
		<p class="open" v-if="app.comments[id]['sub_comments']['opened'] == false" v-on:click="app.comments[id]['sub_comments']['opened'] = true;">{{ app.countSubComments(app.comments[id]) }} комментариев</p>

	`
}).component('comment', {
	props: ['app', 'id'],
	template: `
		<div class="comment">
			<div class="meta">
				<div v-if="app.comments[id]['active']" class="icon" :style="'background-image: url(' + app.users[app.comments[id]['author_id']]['picture'] + ')'"  v-on:click="app.viewUserProfile(app.comments[id]['author_id'])">
					
				</div>
				<div v-else style="background-image: url(/img/removed.webp)" class="icon">
					
				</div>
				<div class="name-and-date" v-if="app.comments[id]['active']">
					<p class="name" v-on:click="app.viewUserProfile(app.comments[id]['author_id'])">{{ app.users[app.comments[id]['author_id']]['name'] }}</p>
					<p class="date">{{ app.formatTime(app.comments[id]['created_at']) }} <span v-if="app.posts[0]['author_id'] == app.comments[id]['author_id']" class="author">Автор</span></p>
				</div>
				<template v-if="app.user.moderator">
					<img v-if="app.comments[id]['active']" src="/img/ban.svg" class="ban" v-on:click="app.removeComment(id)"/>
					<img v-if="!app.comments[id]['active']" src="/img/unban.svg" class="ban" v-on:click="app.unremoveComment(id)"/>
				</template>
				<div class="rating">
					<rating-down :grade="app.comments[id]['grade']" v-on:click="app.rateComment(app.comments[id], false)"></rating-down>
					<p :class="app.comments[id]['rating_value'] > 0 ? 'positive' : app.comments[id]['rating_value'] < 0 ? 'negative' : ''">{{ app.comments[id]['rating_value'] }}</p>
					<div class="rating-list" v-if="Object.keys(app.comments[id]['rating']).length > 0">
						<a class="user" v-for="(rate, id) in app.comments[id]['rating']" :href="'/u/' + id">
							<div class="pic" :style="'background-image: url(' + app.users[id]['picture'] + ');'">
								
							</div>
							<p :class="rate == 1 ? 'positive' : 'negative'">{{ app.users[id]['name'] }}</p>
						</a>
					</div>
					<rating-up :grade="app.comments[id]['grade']" v-on:click="app.rateComment(app.comments[id], true)"></rating-up>
				</div>
			</div>
			<div class="comment-text">
				<p>
					{{ app.comments[id]['content'] }}
				</p>
			</div>
			<div class="attachment" v-if="app.comments[id]['attachment']" >
				<img :src="app.comments[id]['attachment']" />
			</div>
			<div class="bottom">
				<p class="reply" v-on:click="app.reply(id)">Ответить</p>
			</div>
		</div>
		<sub-comments :app="app" :id="id"></sub-comments>
	`
}).component('comments', {
	props: ['app'],
	template: `
		<template v-for="commentID in app.commentsOrder">
			<template v-if="app.comments[commentID] !== undefined">
				<template v-if="app.comments[commentID]['reply_to'] == null">
					<comment :app="app" :id="commentID"></comment>			
				</template>
			</template>
		</template>
	`
}).mount('#app');