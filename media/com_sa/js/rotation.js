function sa_init(thisad, module_id, ad_rotationdelay, waitForTransition = '') {
	var preview_for = jQuery(thisad).attr('preview_for');
	var zone_id = jQuery(thisad).parent().attr('havezone');
	var ad_type = jQuery(thisad).find('.adtype').attr('adtype');

	if (ad_type != 'video') {
		if (waitForTransition) {
			var t = setTimeout(function () {
				jQuery(thisad).show(decideToRotate(preview_for, zone_id, module_id, ad_type, ad_rotationdelay));
			}, 1000);
		} else {
			jQuery(thisad).show(decideToRotate(preview_for, zone_id, module_id, ad_type, ad_rotationdelay));
		}
	}
	else {
		/*console.log(flowplayer('vid_player_'+ preview_for).getState());*/
		if (flowplayer('vid_player_' + preview_for).getState() != -1) {
			var t = setTimeout(function () {
				decideToRotate(preview_for, zone_id, module_id, ad_type, ad_rotationdelay);
			}, 5000);
		}
		else {
			flowplayer('vid_player_' + preview_for).onLoad(function () {
				var t = setTimeout(function () {
					decideToRotate(preview_for, zone_id, module_id, ad_type, ad_rotationdelay);
				}, 5000);
			});
		}
	}
}

function decideToRotate(preview_for, zone_id, module_id, ad_type, ad_rotationdelay) {
	var this_addiv = jQuery("div[preview_for=" + preview_for + "]");
	var ad_entry_number = jQuery(this_addiv).attr('ad_entry_number');

	if (ad_type == 'video') {
		var cli_state = flowplayer('vid_player_' + preview_for).getState();
		var cntset = ad_entry_number * ad_rotationdelay * 1000;
		if (cli_state == 4 || cli_state == -1) {
			/*var this_video_div	=	jQuery( "div[preview_for="+preview_for+"]" );*/
			var cntset = ad_entry_number * ad_rotationdelay * 1000;
		}
		else if (cli_state == 3) {
			var cntset = flowplayer('vid_player_' + preview_for).getClip().fullDuration;
		}
	}
	else {
		var cntset = ad_rotationdelay * 1000;
	}

	getAdForSwitch(this_addiv, zone_id, module_id, preview_for, cntset, ad_entry_number, ad_type, ad_rotationdelay);
}

function getAdForSwitch(this_addiv, zone_id, module_id, ad_id, cntset, ad_entry_number, ad_type, ad_rotationdelay) {
	var donotrotate = 0;
	countdown = setTimeout(async function () {
		try {
			var isHovered = jQuery(this_addiv).is(":hover");
			if (isHovered) {
				donotrotate = 1;
			}
		} catch (error) {
			/*Skip*/
		}

		if (ad_type == 'video') {
			var state = flowplayer('vid_player_' + ad_id).getState();
			if (state == 3) {
				donotrotate = 1;
			}
		}

		if (donotrotate == 1) {
			sa_init(this_addiv, module_id, ad_rotationdelay);
		}
		else {
			var switch_addata = await checkIfAdsAvailable(ad_id, zone_id, module_id);

			if (switch_addata) {
				if (switch_addata.ad_id) {
					var switch_ad_html = await getAdHtml(switch_addata.ad_id, module_id);
				} else {
					switch_ad_html = switch_addata.adHTML;
					switch_addata = switch_addata.check;
				}

				if (jQuery(this_addiv).parent().hasClass('ad_rotate_with_transition')) {
					// jQuery(this_addiv).parent().append(switch_ad_html);
					jQuery(switch_ad_html).insertBefore(this_addiv);
					var switched_ad = jQuery('div[preview_for="' + switch_addata.ad_id + '"]');
					jQuery(switched_ad).attr("ad_entry_number", ad_entry_number);
					var waitForTransition = 1;
					sa_init(switched_ad, module_id, ad_rotationdelay, waitForTransition);
					jQuery(switched_ad).addClass('rotateAds');
					jQuery(this_addiv).addClass('transparent');

					setTimeout(function () {
						jQuery(this_addiv).remove();
					}, 1000);

				} else {
					jQuery(this_addiv).fadeOut(2000, function () {
						jQuery(this_addiv).replaceWith(switch_ad_html);


						jQuery('div[preview_for="' + switch_addata.ad_id + '"]').fadeIn(2000, function () {

							/*jQuery('div[preview_for="' + switch_addata.ad_id + '"]').css({
								"transform": "translate3d(0, 0, 0)", "backface-visibility": "hidden", "-webkit-perspective": "1000"
							});*/

							var switched_ad = jQuery('div[preview_for="' + switch_addata.ad_id + '"]');
							jQuery(switched_ad).attr("ad_entry_number", ad_entry_number);
							sa_init(switched_ad, module_id, ad_rotationdelay);
							jQuery(switched_ad).addClass('rotateAds');
						});
					});
				}
			}
		}
	}, cntset);
}

/**
 * Check If Ads Available
 *
 * @param  ad_id    Ad Id
 * @param  zone_id  Zone Id
 *
 * @return
 */
 async function checkIfAdsAvailable(ad_id, zone_id, module_id) {
	var ad_data = '';
	jQuery.ajax({
		type: 'get',
		url: site_link + 'index.php?option=com_sa&task=render.checkIfAdsAvailable&ad_id=' + ad_id + '&zone_id=' + zone_id + '&module_id=' + module_id + '&checkAdAndGethtml=1',
		async: false,
		dataType: 'json',
		success: function (data) {
			if (data == null) {
				return;
			}
			ad_data = data;
		}
	});

	return ad_data;
}

/**
 * Get the Add HTML
 *
 * @param  ad_id      Ad Id
 * @param  module_id  Module Id
 *
 * @return  Ad HTML
 */
async function getAdHtml(ad_id, module_id) {
	var ad_html = '';

	if (window.XMLHttpRequest) {
		xhttp = new XMLHttpRequest();
	}
	else {
		xhttp = new ActiveXObject("Microsoft.XMLHTTP");
	}

	xhttp.open("GET", site_link + "index.php?option=com_sa&task=render.getAdHtml&ad_id=" + ad_id + "&module_id=" + module_id, false);
	xhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
	xhttp.send("ad_id=" + ad_id + "&module_id=" + module_id);
	ad_html = xhttp.responseText;

	return ad_html;
}
