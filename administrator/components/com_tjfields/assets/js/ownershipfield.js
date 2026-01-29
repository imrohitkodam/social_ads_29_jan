/*
 * @package    Com_Tjfields
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (c) 2009-2019 TechJoomla. All rights reserved
 * @license    GNU General Public License version 2, or later
 */

var ownership = {

	clusterUrl: Joomla.getOptions('system.paths').base + "/index.php?option=com_cluster&task=clusterusers.getUsersByClientId&format=json",
	userUrl: Joomla.getOptions('system.paths').base + "/index.php?option=com_tjfields&task=fields.getAllUsers&format=json",

	/* This function to get all users in tjucm via ajax */
	getUsers: function (clusterUserData, ajaxUrl, ownershipFieldId) {
		jQuery('#'+ownershipFieldId+', .chzn-results').empty();
		jQuery.ajax({
			url: ajaxUrl,
			type: 'POST',
			data: clusterUserData,
			dataType:"json",
			success: function (response) {
				var selectOption = '';
				var op = '';
				var data = response.data;

				for(var index = 0; index < data.length; ++index)
				{
					selectOption = '';
					if (clusterUserData.user_id == data[index].value)
					{
						selectOption = ' selected="selected" ';
					}
					op="<option value='"+data[index].value+"' "+selectOption+" > " + data[index]['text'] + "</option>" ;
					jQuery('#'+ownershipFieldId).append(op);
				}

				/* IMP : to update to chz-done selects*/
				jQuery("#"+ownershipFieldId).trigger("liszt:updated");
				jQuery("#"+ownershipFieldId).trigger("chosen:updated");
			}
		});
	},
	/* This function to populate all users in ownership field of tjucm form */
	setUsers: function (clusterUserData, clusterInputId) {
		var clusterId = '';
		var ajaxUrl = this.userUrl;
		var ownershipFieldId = clusterInputId.replace("clusterclusterid", "ownershipcreatedby");

		clusterUserData.user_id = jQuery("#"+ownershipFieldId+"value").val();

		// Check class exists or not
		if (jQuery("#"+clusterInputId).length > 0)
		{
			clusterId = jQuery("#"+clusterInputId).val();

			clusterUserData.cluster_id = clusterId;
			ajaxUrl = this.clusterUrl;
		}

		if ((jQuery.trim(clusterId) != '' && clusterId != 'undefined') || (jQuery("#"+clusterInputId).length == 0))
		{
			this.getUsers(clusterUserData, ajaxUrl, ownershipFieldId);
		}
	},
	/* This function to get users based on cluster value in tjucm via ajax */
	updateOwnershipField: function (e){
		var clusterFieldId = jQuery(e).attr('id');
		var ownershipFieldId = clusterFieldId.replace("clusterclusterid", "ownershipcreatedby");

		// If there is no ownership field in the form then we do not need to update ownership field after cluster onchange event
		if (jQuery("#"+ownershipFieldId).length == 0)
		{
			return e.preventDefault();
		}

		var dataFields = {cluster_id: jQuery("#"+clusterFieldId).val() , user_id: jQuery("#"+ownershipFieldId+"value").val()};
		var ajaxUrl = ownership.clusterUrl;
		//Get All associated users
		ownership.getUsers(dataFields, ajaxUrl, ownershipFieldId);
	}
}
