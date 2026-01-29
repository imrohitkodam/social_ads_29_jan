if (typeof(techjoomla) == 'undefined')
{
	var techjoomla = {};
}

if (typeof techjoomla.jQuery == "undefined")
{
	techjoomla.jQuery = jQuery;
}

let tjHouseKeepingScriptsCount = 0;
let tjEachScriptProgress = 0;
let initResponse = "";

jQuery(document).ready(function(){
	let client = TjHouseKeeping.getUrlParam('option');
	let controller = TjHouseKeeping.getUrlParam('view');

	if (controller === null)
	{
		controller = tjHouseKeepingView;
	}

	jQuery("#tjHouseKeepingFixDatabasebutton").click(function(){
		TjHouseKeeping.fixDatabase(client, controller);
	});

	jQuery.ajax({
		url: Joomla.getOptions('system.paths').base+'/index.php?option='+client+'&task='+controller+'.init'+'&tmpl=component&'+Joomla.getOptions('csrf.token')+'=1',
		type: 'POST',
		dataType:'json',
		success: function(response)
		{
			initResponse = response;
			tjEachScriptProgress = parseFloat(100/response.count,10);

			if (response.count > 0)
			{
				/* Show the fix database button if there are scripts found*/
				jQuery('#tjHouseKeepingFixDatabasebutton').removeClass('hidden');
			}
		},
		error: function(jqXHR, textStatus, errorThrown)
		{
			Joomla.renderMessages({'error':["Something went wrong"]});
		}
	});
});

var TjHouseKeeping = {

	fixDatabase: function (client, controller){

		/* Add required HTML elements in the body*/
		jQuery('<div class="fix-database-info"><div class="progress-container" id="tjHouseKeepingClose"></div></div>').insertBefore(".tjBs3");

		/* Disable the fix database button*/
		jQuery('#tjHouseKeepingFixDatabasebutton').attr('disabled', true);

		tjHouseKeepingScriptsCount = 0;
		jQuery('.fix-database-info').html('<div class="progress-container"></div>');
		jQuery('.fix-database-info').show();

		if (initResponse.scripts.length > 0)
		{
			/* Initialise progress bar */
			let obj = jQuery('.fix-database-info .progress-container');
			let progressBarObj = new TjHouseKeeping.createProgressbar(obj);
			let tjHouseKeepingCounter = 0;

			initResponse.scripts.forEach(function(script)
			{
				statusdiv = "<div class='alert alert-plain tjHouseKeepingScriptDiv"+tjHouseKeepingCounter+"' id='tjHouseKeepingScriptDiv"+tjHouseKeepingCounter+"'>" +
								"<button class='close pull-right' onclick=\"document.getElementById('tjHouseKeepingScriptDiv"+tjHouseKeepingCounter+"').style.display='none'\" >x</button>"+
								"<div class='before'>Fixing database:&nbsp;"+script[3]+"</div>"+
								"<div class='after'>"+script[4]+"</div>" +
							"</div>";
				jQuery('.fix-database-info').append(statusdiv);

				tjHouseKeepingCounter++;
			});

			TjHouseKeeping.executeHouseKeeping(initResponse.scripts, client, controller, progressBarObj);
		}
		else
		{
			statusdiv = "<div class='alert alert-info'>" +
							"<div>Database upto date.</div>"+
						"</div>";
			jQuery('.fix-database-info').append(statusdiv);
		}

		return false;
	},

	executeHouseKeeping:function (scripts, client, controller, progressBarObj){
		let script = scripts[tjHouseKeepingScriptsCount];

		if(typeof script !== "undefined")
		{
			jQuery.ajax({
				url: Joomla.getOptions('system.paths').base+'/index.php?option='+client+'&task='+controller+'.executeHouseKeeping&client='+script[0]+'&version='+script[1]+'&script='+script[2]+'&tmpl=component&'+Joomla.getOptions('csrf.token')+'=1',
				type: 'POST',
				dataType:'json',
				success: function(response)
				{
					let progressPercent = parseInt(tjEachScriptProgress * (tjHouseKeepingScriptsCount+1));

					/* If response status is true show progressbar with 100% */
					if (response.status === true)
					{
						progressBarObj.setProgress(progressPercent);

						jQuery('.fix-database-info .tjHouseKeepingScriptDiv'+tjHouseKeepingScriptsCount).removeClass('alert-plain').addClass('alert-success');
						jQuery('.fix-database-info .tjHouseKeepingScriptDiv'+tjHouseKeepingScriptsCount+' .after').append('...Done!');

						/* If response status is true then only allow to execute new file*/
						tjHouseKeepingScriptsCount++;
					}
					else if (response.status === false)
					{
						jQuery('.fix-database-info .tjHouseKeepingScriptDiv'+tjHouseKeepingScriptsCount).removeClass('alert-plain').addClass('alert-error');
						jQuery('.fix-database-info .tjHouseKeepingScriptDiv'+tjHouseKeepingScriptsCount+' .after').append('...Something went wrong!');
					}

					/* If response status is true  and all migration files are execute then hide fix database button*/
					if (tjHouseKeepingScriptsCount == initResponse.count && response.status === true)
					{
						jQuery('#tjHouseKeepingFixDatabasebutton').addClass('hidden');
					}

					TjHouseKeeping.executeHouseKeeping(scripts, client, controller, progressBarObj);
				},
				error: function(jqXHR, textStatus, errorThrown)
				{
					jQuery('.fix-database-info').removeClass('alert-plain').addClass('alert-error');
					jQuery('.fix-database-info .after').html(jqXHR.responseText);
					jQuery('.fix-database-info .after').show();
				}
			});
		}
	},

	createProgressbar: function(obj, bartitle){
		bartitle = bartitle ? bartitle : 'Fixing database:&nbsp;';
		this.statusbar = jQuery("<div></div>");
		this.progressBar = jQuery('<div class="progress progress-striped active progress-bar"><span class="bar progress-bar">' + bartitle + ' <b class="progress_per"></b></div>').appendTo(this.statusbar);

		obj.append(this.statusbar);

		this.setProgress = function(progress)
		{
			if (progress == 100)
			{
				this.statusbar.hide();

				return;
			}

			this.statusbar.show();
			this.progressBar.show();
			let progressBarWidth =progress*this.progressBar.width()/ 100;
			this.progressBar.find('.progress-bar').animate({ width: progressBarWidth }, 10);
			this.progressBar.find('.progress_per').html(progress + "% ");
		}
	},
	getUrlParam: function(name){
		var results = new RegExp('[\?&]' + name + '=([^&#]*)').exec(window.location.href);
		if (results==null){
			return null;
		}
		else{
			return results[1] || 0;
		}
	},
}
