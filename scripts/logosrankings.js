$(document).ready(function() {
	
	var table = $('#logosrankings-table').DataTable({
		"processing": true,
		"serverSide": true,
		"ajax": "/process/datatables/fetchLogosRankings.php",
		"columnDefs": [
			{ "data" : "title", "targets": 0 },
			{ "data" : "difficulty", "targets": 1 },
			{ "data" : "percentUsersSolved", "className" : "text-right", "targets": 2 },
			{ "data" : "averageTimeTaken", "className" : "text-right", "targets": 3 },
			{ "data" : "averageTimeTakenPerLetter", "className" : "text-right", "targets": 4 },
			{ "data" : "averageSkips", "className" : "text-right", "targets": 5 },
			{ "data" : "averageTimeTakenBelowAverageLogo", "className" : "text-right", "targets": 6 },
			{ "data" : "averageTimeTakenPerLetterBelowAverageLogo", "className" : "text-right" , "targets": 7 },
			{ "data" : "averageSkipsBelowAverageLogo", "className" : "text-right", "targets": 8 }
		]
	});


});