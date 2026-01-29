var userAgentIsMobile = false;
var userAgentIsAndroid = false;
var subFormFieldCount = 0;
var cameraFacingMode = 'user';

jQuery(document).ready(function() {
	var userAgent = navigator.userAgent || navigator.vendor || window.opera;

	/* For mobile devices need to use a button to initialise camera as its not initialised on page load*/
	if (/Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(userAgent)) {
		userAgentIsMobile = true;
	}

	/* Check if useragent is android as we need to show camera switch button for android devices*/
	if (/android/i.test(userAgent)) {
		userAgentIsAndroid = true;
	}

	/* Initialise camera on page load by clicking a hidden button - the button is used in case of mobile devices as we cant activate camera on page load on mobile devices*/
	jQuery.each(jQuery(document).find(jQuery("input[onClick^='set_camera(']")), function(key, value) {
		if (jQuery('#' + jQuery(value).attr('id').replace('take_another', '_hasvalue')).val() == '0') {
			jQuery(value).click();
		}
	});

	/* Hide button to capture image on mobile device as this is done via mobile interface*/
	jQuery.each(jQuery(document).find(jQuery("input[onClick^='take_snapshot(']")), function(key, value) {
		if (userAgentIsMobile) {
			document.getElementById(jQuery(value).attr('id')).style = 'display:none;';
		}
	});

	/* Hide switch camera button is useragent is not android */
	jQuery.each(jQuery(document).find(jQuery("input[onClick^='switch_camera(']")), function(key, value) {
		if (userAgentIsAndroid === false) {
			document.getElementById(jQuery(value).attr('id')).style = 'display:none;';
		}
	});

	/* Update the options of related field for new record of subform */
	jQuery(document).on('subform-row-add', function(event, row) {
		subFormFieldCount = jQuery(row).attr('data-group').replace(jQuery(row).attr('data-base-name'), "");
		var likeName = 'jform_' + jQuery(row).attr('data-base-name') + '__' + jQuery(row).attr('data-group').substring(0, jQuery(row).attr('data-group').length - 7);

		/* Update the id of elements used in field when a new group of field is added for subform */
		jQuery.each(jQuery(row).find(jQuery("input[id^='" + likeName + "']")), function(key, value) {
			jQuery(value).attr('id', jQuery(value).attr('id').replace("X__", subFormFieldCount + '__'));
		});

		/* Update the id of elements used in field when a new group of field is added for subform */
		jQuery.each(jQuery(row).find(jQuery("div[id^='" + likeName + "']")), function(key, value) {
			jQuery(value).attr('id', jQuery(value).attr('id').replace("X__", subFormFieldCount + '__'));
		});

		/* Initialise camera on page load by clicking a hidden button - the button is used in case of mobile devices as we cant activate camera on page load on mobile devices*/
		jQuery.each(jQuery(row).find(jQuery("input[onClick^='set_camera(']")), function(key, value) {
			if (jQuery('#' + jQuery(value).attr('id').replace('take_another', '_hasvalue')).val() == '0') {
				jQuery(value).click();
			}
		});

		/* Update the id of elements used in field when a new group of field is added for subform */
		jQuery.each(jQuery(row).find(jQuery("input[onClick^='set_camera(']")), function(key, value) {
			jQuery(value).attr('onClick', jQuery(value).attr('onClick').replace('X__', subFormFieldCount + '__'));
		});

		/* Update the id of elements used in field when a new group of field is added for subform */
		jQuery.each(jQuery(row).find(jQuery("input[onClick^='take_snapshot(']")), function(key, value) {
			jQuery(value).attr('onClick', jQuery(value).attr('onClick').replace('X__', subFormFieldCount + '__'));
		});

		/* Update the id of elements used in field when a new group of field is added for subform */
		jQuery.each(jQuery(row).find(jQuery("input[onClick^='switch_camera(']")), function(key, value) {
			jQuery(value).attr('onClick', jQuery(value).attr('onClick').replace('X__', subFormFieldCount + '__'));

			if (userAgentIsAndroid == false)
			{
				document.getElementById(jQuery(value).attr('id')).style = 'display:none;';
			}
		});

		/* Update the id of elements used in field when a new group of field is added for subform */
		jQuery.each(jQuery(row).find(jQuery("input[onClick^='switch_camera(']")), function(key, value) {
			jQuery(value).attr('onClick', jQuery(value).attr('onClick').replace('X__', subFormFieldCount + '__'));
		});

		/* Update the id of elements used in field when a new group of field is added for subform */
		jQuery.each(jQuery(row).find(jQuery("input[onClick^='use_snapshot(']")), function(key, value) {
			jQuery(value).attr('onClick', jQuery(value).attr('onClick').replace('X__', subFormFieldCount + '__'));
		});

		/* Hide button to capture image on mobile device as this is done via mobile interface*/
		jQuery.each(jQuery(row).find(jQuery("input[onClick^='take_snapshot(']")), function(key, value) {
			if (userAgentIsMobile) {
				document.getElementById(jQuery(value).attr('id')).style = 'display:none;';
			}
		});
	});
});

/* Method to take snapshot - used for non mobile devices*/
function take_snapshot(thisId) {
	Webcam.snap(function(data_uri) {
		document.getElementById(thisId + '_capture_img').style = 'display:none';
		jQuery("#"+thisId + '_captured_img').attr('style', 'width:'+document.getElementById(thisId + '__camera_width').value+'px;height:'+document.getElementById(thisId + '__camera_height').value+'px;');
		jQuery('#' + thisId + '_captured_img').find("img").attr("src", data_uri);
		jQuery('#' + thisId).val(jQuery('#' + thisId + '_captured_img').find("img").attr("src")).trigger('change');
		document.getElementById(thisId + '_take_snapshot').style = 'display:none;';
		document.getElementById(thisId + '_switch_camera').style = 'display:none;';
		document.getElementById(thisId + '_take_another').style = '';
	});
}

/* Method to take snapshot - used for mobile devices*/
function use_snapshot(thisId) {
	Webcam.snap(function(data_uri) {
		document.getElementById(thisId + '_capture_img').style = 'display:none';
		jQuery("#"+thisId + '_captured_img').attr('style', 'width:'+document.getElementById(thisId + '__camera_width').value+'px;height:'+document.getElementById(thisId + '__camera_height').value+'px;');
		jQuery('#' + thisId + '_captured_img').find("img").attr("src", data_uri);
		Webcam.reset();

		if (userAgentIsMobile) {
			document.getElementById(thisId + '_use').style = 'display:none;';
		}

		jQuery('#' + thisId).val(jQuery('#' + thisId + '_captured_img').find("img").attr("src")).trigger('change');

		document.getElementById(thisId + '_take_snapshot').style = 'display:none;';
		document.getElementById(thisId + '_switch_camera').style = 'display:none;';
		document.getElementById(thisId + '_take_another').style = '';
	});
}

/* Method to initialise and re-initialise camera - used for non mobile devices*/
function set_camera(thisId) {
	thisId = thisId.replace('X__', subFormFieldCount + '__');
	document.getElementById(thisId + '_capture_img').style = '';
	document.getElementById(thisId + '_captured_img').src = '';
	jQuery('#' + thisId + '_captured_img').find("img").attr("src", '');
	document.getElementById(thisId + '_captured_img').style = 'display:none';
	document.getElementById(thisId + '_take_another').style = 'display:none;';
	document.getElementById(thisId + '_take_snapshot').style = '';
	document.getElementById(thisId + '_use').style = 'display:none;';

	if (userAgentIsMobile) {
		document.getElementById(thisId + '_take_snapshot').style = 'display:none;';
		document.getElementById(thisId + '_use').style = '';
	}

	if (userAgentIsAndroid)
	{
		document.getElementById(thisId + '_switch_camera').style = '';
	}

	/* WebcamJs code to initialise camera - used for mobile devices*/
	Webcam.set({
		width: document.getElementById(thisId + '__camera_width').value,
        height: document.getElementById(thisId + '__camera_height').value,
        dest_width: document.getElementById(thisId + '__camera_width').value*2,
		dest_height: document.getElementById(thisId + '__camera_height').value*2,
		flip_horiz: true,
		image_format: 'jpeg',
		jpeg_quality: 100
	});

	/* WebcamJs code to attach camera outupt to a div on the page*/
	Webcam.attach('#' + thisId + '_capture_img');
}

/* Method to switch camera mode - used for android mobile devices only*/
function switch_camera(thisId) {
	if (userAgentIsAndroid)
	{
		cameraFacingMode = (cameraFacingMode == 'user') ? 'environment' : 'user';

		Webcam.reset();

		Webcam.set({
			width: document.getElementById(thisId + '__camera_width').value,
			height: document.getElementById(thisId + '__camera_height').value,
			dest_width: document.getElementById(thisId + '__camera_width').value*2,
			dest_height: document.getElementById(thisId + '__camera_height').value*2,
			flip_horiz: true,
			image_format: 'jpeg',
			jpeg_quality: 100,
			constraints: {
				facingMode: cameraFacingMode
			}
		});

		/* WebcamJs code to attach camera outupt to a div on the page*/
		Webcam.attach('#' + thisId + '_capture_img');
	}
}
