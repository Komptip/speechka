function insertDataToEditor(){
	if(!app.postForEdit.loaded){
		app.postForEdit.loaded = true;

		editor.blocks.render({
			blocks: app.postForEdit.data
		});
	}
}

let formData = new FormData();

formData.append('id', document.querySelector('meta[name="post-id"]').getAttribute('content'));

fetch('/data/post/get-for-edit', {
	method: 'POST',
	headers: {'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')},
	body: formData
})
.then((response) => {
	return response.json();
})
.then((data) => {

	app.postForEdit = data;
	app.postForEdit.loaded = false;
	app.postForEdit.id = document.querySelector('meta[name="post-id"]').getAttribute('content');

	if(editor.blocks !== undefined){
		insertDataToEditor();
	}

});