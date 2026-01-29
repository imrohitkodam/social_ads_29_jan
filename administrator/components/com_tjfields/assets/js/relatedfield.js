/*
 * @package    Tjfields
 * @subpackage Com_Tjfields
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (c) 2009-2019 TechJoomla. All rights reserved
 * @license    GNU General Public License version 2, or later
 */
var relatedField = {
	populateFields: function(clientObj) {
		var client = jQuery(clientObj).val();
		var clientElementId = jQuery(clientObj).attr('id');
		var fieldsElementId = clientElementId.replace("client", "fieldIds");

		jQuery.ajax({
			url: Joomla.getOptions('system.paths').base + "/index.php?option=com_tjfields&task=fields.getFields&format=json&" + Joomla.getOptions('csrf.token') + "=1",
			type: 'POST',
			data:{
				client: client
			},
			dataType: "json",
			success: function (result) {

				jQuery("#"+fieldsElementId).html("");

				if (result.data)
				{
					var allowedFieldTypes = ["text", "textarea", "textareacounter", "email", "number"];

					jQuery.each(result.data, function( index, field) {

						if (allowedFieldTypes.includes(field.type))
						{
							jQuery("#"+fieldsElementId).append("<option value="+field.id+">"+field.label+"</option>");
						}
					});

					jQuery("#"+fieldsElementId).trigger("liszt:updated");
					jQuery("#"+fieldsElementId).trigger("chosen:updated");
				}
			}
		});
	}
};
