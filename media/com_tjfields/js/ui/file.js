var tjFieldsFileField = {
	previewMedia: function (tjUcmMediaUrl, width = window.innerWidth-50, height = window.innerHeight-50){
		if (width > window.innerWidth-50)
		{
			width = window.innerWidth-50;
		}

		if (height > window.innerHeight-50)
		{
			height = window.innerHeight-50;
		}

		SqueezeBox.open(tjUcmMediaUrl ,{handler: "iframe", size: {x: width, y: height}});
	}
}
