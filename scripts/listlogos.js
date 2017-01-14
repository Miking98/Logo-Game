$(document).ready(function() {
	
	var table = $('#listlogos-table').DataTable({
		"processing": true,
		"serverSide": true,
		"ajax": "/process/datatables/fetchPastLogos.php",
		"columnDefs": [
			{ "data" : "id", "targets": 0 },
			{ "data" : "title", "targets": 1 },
			{ "data" : "timeTaken", "className" : "text-right", "targets": 2 },
			{ "data" : "timeTakenPerLetter", "className" : "text-right", "targets": 3 },
			{ "data" : "skips", "className" : "text-right", "targets": 4 },
			{ "data" : "timeTakenBelowAverage", "className" : "text-right", "targets": 5 },
			{ "data" : "timeTakenPerLetterBelowAverage", "className" : "text-right" , "targets": 6 },
			{ "data" : "skipsBelowAverage", "className" : "text-right", "targets": 7 }
		]
	});

	$("#listlogos-table tbody").on('click', 'tr:not(.table-moreInfo)', function() {
		var $tr = $(this);
		var logoID = $tr.attr('logoID');
 		var moreInfoPrefix = 'listlogos-table-moreInfo-'+logoID;
 		var moreInfoPanel = $("#"+moreInfoPrefix);

 		if (moreInfoPanel.length>0) { //Panel already exists
 			moreInfoPanel.toggle();
 			$tr.toggleClass('active');
 		}
 		else { //Generate panel
			var descriptionWikiTitle = $tr.attr('descriptionWikiTitle');
	 		var imageLocation = "/images/logos/"+$tr.attr('imagelocation');

	 		var numberofColumns = table.columns().header().length;
	 		var imageContainer = moreInfoPrefix+'-image';
	 		var descriptionContainer = moreInfoPrefix+'-description';
	 		var descriptionTextToggle = descriptionContainer+'-texttoggle';
	 		var descriptionSecondTextContainer = descriptionContainer+'-secondText';

	 		moreInfoHTML = '<tr id="'+moreInfoPrefix+'" class="active table-moreInfo table-hover-tr-notClickable">'+
	 							'<td colspan="'+numberofColumns+'">'+
				 					'<div class="col-xs-12">'+
				 						'<div id="'+imageContainer+'" class="col-xs-4">'+
				 							'<img src="'+imageLocation+'" class="img-responsive img-thumbnail">'+
				 						'</div>'+
				 						'<div id="'+descriptionContainer+'" class="col-xs-8">'+
				 						'</div>'+
				 					'</div>'+
				 				'</td>'+
				 			'</tr>';

			$tr.after(moreInfoHTML);
			$tr.addClass('active');
			//Get Description
			addLoadingSpinner($("#"+descriptionContainer));
			$.ajax({
				type: "GET",
				url: "https://en.wikipedia.org/w/api.php?format=json&action=query&prop=extracts&indexpageids=&exintro=&explaintext=&callback=?&titles="+descriptionWikiTitle,
				dataType: "JSON"
			})
			.done(function(jsonResponse) {
				removeLoadingSpinner($("#"+descriptionContainer));
				var extractInfo = jsonResponse['query']['pages'][jsonResponse['query']['pageids'][0]]['extract'];
				var descriptionFullText = '<p>'+(preventxss(extractInfo.trim()+" (Source: Wikipedia)")).replace(/\n/g, '</p><p>')+'</p>';
				var descriptionEndFirstText = descriptionFullText.indexOf('</p>')+4;
				var descriptionFirstText = descriptionFullText.substring(0,descriptionEndFirstText);
				var descriptionSecondText = descriptionFullText.substring(descriptionEndFirstText);
				var descriptionTextToggleHTML = '<a id="'+descriptionTextToggle+'" class="text-toggle" data-toggle="collapse" href="#'+descriptionSecondTextContainer+'" data-alt-text="<< Show less">Show more >></a>';
				var descriptionSecondTextContainerHTML = '<span id="'+descriptionSecondTextContainer+'" class="display-none">'+descriptionSecondText+'</span>';

				$("#"+descriptionContainer).html(descriptionFirstText+descriptionSecondTextContainerHTML+(descriptionEndFirstText<descriptionFullText.length ? descriptionTextToggleHTML : ''));
			});
		}
	});

});