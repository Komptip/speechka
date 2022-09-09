const editor = new EditorJS({ 
  tools: {
  	header: {
      class: Header,
      config: {
        placeholder: 'Введите подзаголовок',
        levels: [2],
        defaultLevel: 2
      }
    },
    paragraph: {
      config: {
        placeholder: 'Нажмите Tab для выбора инструмента'
      }
    },
    integration, 
    list: {
      class: List,
      shortcut: 'CMD+SHIFT+L'
    },
    image: {
      class: ImageTool,
      config: {
      	additionalRequestHeaders: {
            "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        endpoints: {
          byFile: '/files/upload-image', // Your backend file uploader endpoint
          byUrl: 'http://localhost:8008/fetchUrl', // Your endpoint that provides uploading by Url
        }
      }
    }
  },
  defaultBlock: 'paragraph',
  holder: 'editorjs',
  onReady: () => {
    if(app.postForEdit){
      insertDataToEditor();
    }
   }
})