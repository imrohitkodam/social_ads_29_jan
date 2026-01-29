var tjFile = {
	validateFile: function(thisFile) {
		var mediaformData = new FormData();
		var fileField = jQuery(thisFile);
		var uploadType = fileField.attr('type');
		var media_id = jQuery(thisFile).parent().find("input[name$='[media_id]']").val();

		/*Get all the fields data and add it in media form object.*/
		if (uploadType == 'file') {
			mediaformData.append('file[]', jQuery(thisFile)[0].files[0]);
			mediaformData.append('media_id', media_id);
			mediaformData.append('upload_file', uploadType);
			mediaformData.append('title', fileField.data("title"));
			mediaformData.append('uploadPath', fileField.data("uploadpath"));
			mediaformData.append('oldData', fileField.data("old-data"));
			mediaformData.append('saveData', fileField.data("save-data"));
			mediaformData.append('size', fileField.attr('size'));
			mediaformData.append('storage', fileField.data("storage"));
			mediaformData.append('state', fileField.data("state"));
			mediaformData.append('access', fileField.data("access"));
			mediaformData.append('params', fileField.data("params"));
			mediaformData.append('allowedType', fileField.data("allowedtype"));
			mediaformData.append('allowedext', fileField.data("allowedext"));
			mediaformData.append('extension', fileField.data("extension"));
			mediaformData.append('acl', fileField.data("acl"));

			var validateTjFileHtml5Check = tjFile.html5FileCheck(thisFile, fileField.attr('size'), fileField.data("allowedtype"));

			if (validateTjFileHtml5Check !== true) {
				jQuery(thisFile).val('');
				tjFile.showErrorMesssage(thisFile, validateTjFileHtml5Check);

				return false;
			}

			if (tjFile.uploadFile(mediaformData, thisFile))
			{
				return true;
			}

			return false;
		}

		jQuery(thisFile).val('');
		return false;
	},

	html5FileCheck: function(ele, allowedMediaSize, allowedMimeTypes) {
		/*Check for browser support for all File API*/
		if(window.File && window.FileReader && window.FileList && window.Blob) {
			/*Get file size and file type*/
			var fsize = jQuery(ele)[0].files[0].size;
			var ftype = jQuery(ele)[0].files[0].type;

			/*Check mime type*/
			var allowedMimeTypesArray = allowedMimeTypes.split(",");

			if (allowedMimeTypesArray !== undefined && (allowedMimeTypesArray.length > 1)) {
				allowedMimeTypesArray = allowedMimeTypesArray.map(function callback(currentValue) {
					return currentValue.trim();
				});

				if(jQuery.inArray(ftype, allowedMimeTypesArray) == -1) {
					alert(Joomla.JText._('LIB_TECHJOOMLA_ERR_MSG_FILE_ALLOW'))

					return Joomla.JText._('LIB_TECHJOOMLA_ERR_MSG_FILE_ALLOW');
				}
			}

			/*Check file size*/
			var allowedMediaSizeInKb = allowedMediaSize * 1024 *1024;
			if(fsize > allowedMediaSizeInKb) {
				var tjFileSizeErrMsg = Joomla.JText._('LIB_TECHJOOMLA_ALLOWED_FILE_SIZE')
				tjFileSizeErrMsg = tjFileSizeErrMsg.replace("%s", allowedMediaSize);
				alert(tjFileSizeErrMsg);

				return tjFileSizeErrMsg;
			}

			return true;
		}

		return false;
	},

	uploadFile: function(mediaformData, thisFile) {
		var fileField = jQuery(thisFile);
		let url = Joomla.getOptions('system.paths').base + "/index.php?option=" + fileField.data('extension') + "&task=" + fileField.data('task');

		/* Upload the file data in in media data. */
		this.ajaxObj = jQuery.ajax({
			type: "POST",
			url: url,
			dataType: 'JSON',
			contentType: false,
			processData: false,
			data: mediaformData,
			success: function(data) {
				/*On success add the media id and image links inside nearest hidden field.*/
				if (data.success) {
					try {
						jQuery(thisFile).parent().find("input[name$='[media_id]']").val(data.data[0].id);

						let imgLink = jQuery(thisFile).parent().find("a[name$='[imgLink]']");
						imgLink.attr('href', data.data[0].media);
						imgLink.html(data.data[0].original_filename);

						if (imgLink.hasClass("hide")) {
							imgLink.removeClass('hide');
						}

						tjFile.showSuccessMesssage(thisFile, data.message);

						var tjFileNewMimeType = jQuery(thisFile)[0].files[0].type;
						if (tjFileNewMimeType == 'image/jpg' || tjFileNewMimeType == 'image/jpeg' || tjFileNewMimeType == 'image/png') {
							jQuery(thisFile).parent().find('.tjfile-uploaded-media > a').attr('href', data.data[0].media);
							jQuery(thisFile).parent().find('.tjfile-uploaded-media > a > img').attr('src', data.data[0].media);

							return true;
						}
						else
						{
							return false;
						}
					}
					catch (err) {
						console.error(err.message);
						return false;
					}
				}
				else {
					tjFile.showErrorMesssage(thisFile, data.message);

					return false;
				}
			},
			error: function(xhr, status, error) {
				tjFile.showErrorMesssage(thisFile, error);

				return false;
			}
		});
	},

	showErrorMesssage: function (ele, error) {
		/*Add invalid classes to field, field lable*/
		jQuery(ele).addClass('invalid');
		jQuery(ele).parent().parent().find('label').addClass('invalid');

		/*Show error message*/
		jQuery(ele).parent().find('.tjfile-success-message').css('display', 'none');
		jQuery(ele).parent().find('.tjfile-error-message').css('display', 'block');
		jQuery(ele).parent().find('.tjfile-error-message > p').text(error);

		/*Joomla.renderMessages({
			'error': [error]
		});*/
	},

	showSuccessMesssage: function (ele, msg) {
		/*Remove invalid classes to field, field lable*/
		jQuery(ele).removeClass('invalid');
		jQuery(ele).parent().parent().find('label').removeClass('invalid');

		/*Show success message*/
		jQuery(ele).parent().find('.tjfile-error-message').css('display', 'none');
		jQuery(ele).parent().find('.tjfile-success-message').css('display', 'block');
		jQuery(ele).parent().find('.tjfile-success-message > p').text(msg);
	},

	eventImgPopup: function(className) {
		jQuery("." + className).magnificPopup({
			type: 'image'
		});
	}
};
