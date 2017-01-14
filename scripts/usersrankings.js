$(document).ready(function() {
	
	var table = $('#usersrankings-table').DataTable({
		"processing": true,
		"serverSide": true,
		"ajax": "/process/datatables/fetchUsersRankings.php",
		"columnDefs": [
			{ "data" : "rank", "targets": 0 },
			{ "data" : "userName", "targets": 1 },
			{ "data" : "logosSolved", "targets": 2 },
			{ "data" : "averageTimeTaken", "className" : "text-right", "targets": 3 },
			{ "data" : "averageTimeTakenPerLetter", "className" : "text-right", "targets": 4 },
			{ "data" : "averageSkips", "className" : "text-right", "targets": 5 },
			{ "data" : "averageTimeTakenBelowAverage", "className" : "text-right", "targets": 6 },
			{ "data" : "averageTimeTakenPerLetterBelowAverage", "className" : "text-right" , "targets": 7 },
			{ "data" : "averageSkipsBelowAverage", "className" : "text-right", "targets": 8 }
		]
	});
});