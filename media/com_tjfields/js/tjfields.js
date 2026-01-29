jQuery(document).ready(function(){
	/* This function deletes tjucm file via ajax */
	deleteFile = function(fileName, fieldId, valueId, subformFileFieldId, isSubformField)
	{
		if (!fileName)
		{
			return;
		}

		if(!confirm(Joomla.JText._('COM_TJFIELDS_FILE_DELETE_CONFIRM')))
		{
			return;
		}

		jQuery.ajax({
			url: Joomla.getOptions('system.paths').base + "/index.php?option=com_tjfields&task=fields.deleteFile&format=json&" + Joomla.getOptions('csrf.token') + '=1',
			type: 'POST',
			data:{
                fileName: fileName,
                valueId: valueId,
                subformFileFieldId:subformFileFieldId,
                isSubformField:isSubformField
			},
			cache: false,
			dataType: "json",
			success: function (result) {
				alert(result.message);
				if (result.data) {
					var element = jQuery("input[fileFieldId='" + fieldId + "']");
					element.val('');
					element.parent().remove('div.control-group');
				}
			}
		});
	}

    /*Required fields valiadtion*/
    document.formvalidator.setHandler('min100', function(value) {
        value = value.trim();
        if (value.trim().length < 100) {
            return false;
        }
        return true;
    });

    document.formvalidator.setHandler('min200', function(value) {
        value = value.trim();
        if (value.trim().length < 200) {
            return false;
        }
        return true;
    });

    document.formvalidator.setHandler('min250', function(value) {
        value = value.trim();
        if (value.trim().length < 250) {
            return false;
        }
        return true;
    });

    document.formvalidator.setHandler('min300', function(value) {
        value = value.trim();
        if (value.trim().length < 300) {
            return false;
        }
        return true;
    });

    document.formvalidator.setHandler('blank-space', function(value) {
        if (value.trim() == '') {
            return false;
        }
        return true;
    });
    document.formvalidator.setHandler('numeric', function(value) {
        if (Number(value) <= 0) {
            return false;
        }
        return true;
    });

    document.formvalidator.setHandler('filetype', function(value, element) {

        var tjfields_file_accept = element[0].accept;
        var tjfields_accept_array = tjfields_file_accept.split(",");
        var tjfields_uploadedfile = element[0].files[0];

        /* Get uploaded file name */
        var tjfields_filename = tjfields_uploadedfile.name;

        /* extension of file*/
        var tjfields_ext = '.' + tjfields_filename.split('.').pop().toLowerCase();

        // Converting to bytes
        var tjfields_uploadSize = element[0].size * 1048576;
        var tjfields_filesize = element[0].files[0].size;

        if(tjfields_uploadSize < tjfields_filesize)
        {
        	alert(Joomla.JText._('COM_TJFIELDS_FILE_ERROR_MAX_SIZE'));

        	return false;
        }

        if(tjfields_accept_array.indexOf(tjfields_ext) === -1)
        {
           return false;
        }

        return true;
    });
    document.formvalidator.setHandler('url', function(value) {
        var tjfields_url_regex = /\b(?:(?:https?|ftp):\/\/|www\.)[-a-z0-9+&#\/%?=~_|!:,.;]*[-a-z0-9+&#\/%=~_|]/i;
        return tjfields_url_regex.test(value);
    });

    /* It restrict the user for manual input in datepicker field */
    jQuery(document).delegate('.calendar-textfield-class', 'focusin', function(event) {
       event.preventDefault();
       jQuery(this).parent().siblings(':eq(0)').show();
    });

    jQuery(document).delegate('.calendar-textfield-class', 'keydown contextmenu', function() {
			return false;
    });

    jQuery(document).delegate('.tjfields-input-image', 'change', function() {
		jQuery(this).closest('div').find('.control-group').hide();
    });

    /* Code for number field validation */
    document.formvalidator.setHandler('check_number_field', function(value, element) {
        var tjfields_enteredValue = parseFloat(value);
        var tjfields_maxValue = parseFloat(element[0].max);
        var tjfields_minValue = parseFloat(element[0].min);

        if (!isNaN(tjfields_maxValue) || !isNaN(tjfields_minValue)) {
            if (tjfields_maxValue < tjfields_enteredValue || tjfields_minValue > tjfields_enteredValue) {
                alert(Joomla.JText._('COM_TJUCM_FIELDS_VALIDATION_ERROR_NUMBER'));
                return false;
            }
            return true;
        }
        return false;
    });
});
