function Game() {

	this.correctAnswer = false;
	this.title;
	this.answer;
	this.description;
	this.descriptionWikiTitle;
	this.descriptionText;
	this.blanks;
	this.blanksContainer;
	this.blankHTML = '<input type="text" class="form-control text-center game-blank" maxlength="1">';
	this.nakedCharHTMLOpen = '<span class="game-nakedchar">';
	this.nakedCharHTMLClose = '</span>';
	this.imageContainer;
	this.image;
	this.imageLocation;
	this.timeTaken;
	this.timer
	this.timerStart;
	this.timerInterval;
	this.skipContainer;
	this.skip;
	this.skipHTML = '<button id="game-skip" class="btn btn-md btn-warning">Skip</button>';
	this.next;
	this.nextHTML = '<button id="game-next" class="btn btn-md btn-success">Next</button>';
	this.wonMessageHTML = '<div class="alert alert-success" role="alert">You\'ve solved all the Logos!<br><br>Check back tomorrow for new Logos.</div>';

	//PHP files for AJAX requests
	this.ajaxPHP = 	{ 	'fetchNewLogo' : 'process/fetchNewLogo.php',
						'submitAnswer' : 'process/submitAnswer.php',
						'clearActiveLogo' : 'process/clearActiveLogo.php'
					};

	this.initiate = function() {
		var game = this;
		game.clearActiveLogo(function() {
			game.restart(true);
		});
	}

	this.restart = function(initialization) {
		var game = this;
		game.title = "";
		game.answer = "";
		game.correctAnswer = false;

		//Selectors
		game.container = $("#game-container");
		game.description = $("#game-description");
		game.blanksContainer = $("#game-blanks-container");
		game.imageContainer = $("#game-image-container");
		game.image = $("#game-image");
		game.timer = $("#game-timer");
		game.skipContainer = $("#game-skip-container");

		game.stopTimer();
		game.clearDescription();
		game.clearLogo();

		//Get new logo
		game.fetchNewLogo(function() {
			game.displayLogo();
			game.displaySkip();
			game.displayBlanks();
			if (initialization) { //For game initialization, wait for animations to stop
			}
			else {
				game.startTimer();
			}
		});
	}

	this.clearActiveLogo = function(callbackfx) {
		var game = this;
		$.ajax({
			type: "POST",
			url: this.ajaxPHP['clearActiveLogo'],
			data: { 
			},
			dataType: "JSON"
		})
		.done(function(jsonResponse) {
			if (jsonResponse.result=="success") {
				callbackfx();
			}
			else {
				notify("Error starting game. Please refresh the page and try again.", "error");
				defaultAJAXresultcheck(jsonResponse);
			}
		});
	}
	this.fetchNewLogo = function(callbackfx) {
		var game = this;
		addLoadingSpinner(game.imageContainer);
		$.ajax({
			type: "POST",
			url: this.ajaxPHP['fetchNewLogo'],
			data: { 
			},
			dataType: "JSON"
		})
		.done(function(jsonResponse) {
			removeLoadingSpinner(game.imageContainer);
			if (jsonResponse.result=="success") {
				game.title = jsonResponse.title;
				game.imageLocation = jsonResponse.location;
				game.timeTaken = jsonResponse.timeTaken;
				callbackfx();
			}
			else if (jsonResponse.result=="nomorelogos") {
				game.won();
			}
			else {
				defaultAJAXresultcheck(jsonResponse);
			}
		});
	}
	this.displayLogo = function() {
		this.image.attr('src', this.imageLocation);
	}
	this.displayLogoSuccessBoundary = function() {
		this.image.addClass('border-success');
	}
	this.clearLogo = function() {
		this.image.removeClass('border-success');
		this.image.attr('src', '');
	}

	this.getDescription = function(callbackfx) {
		var game = this;
		addLoadingSpinner(game.description);
		$.ajax({
			type: "GET",
			url: "https://en.wikipedia.org/w/api.php?format=json&action=query&prop=extracts&indexpageids=&exintro=&explaintext=&callback=?&titles="+game.descriptionWikiTitle,
			dataType: "JSON"
		})
		.done(function(jsonResponse) {
			removeLoadingSpinner(game.description);
			var extractInfo = jsonResponse['query']['pages'][jsonResponse['query']['pageids'][0]]['extract'];
			game.descriptionText = preventxss(extractInfo+" (Source: Wikipedia)");
			callbackfx();
		});
	}
	this.displayDescription = function() {
		this.description.html('<p>'+this.descriptionText.replace(/\n/g, '</p><p>')+'</p>');
	}
	this.clearDescription = function() {
		this.descriptionText = "";
		this.displayDescription();
	}

	this.startTimer = function() {
		var game = this;
		var desisec = 0;
		game.timerStart = (new Date).getTime()-game.timeTaken;
		function pad ( val ) { return val > 9 ? val : "0" + val; }
		game.timerInterval = setInterval(function() {
			decisec = Math.floor(((new Date).getTime()-game.timerStart)/10);
			game.timer.html( pad(parseInt(decisec/6000)) + ":" + pad(parseInt(decisec/100)%60) + "<small>:"+(decisec%100)+"</small>");
		}, 10);
	}
	this.stopTimer = function() {
		clearInterval(this.timerInterval);
	}

	this.displayBlanks = function() {
		var game = this;
		var html = "";
		for (var i = 0; i<game.title.length; i++) {
			if (game.title.charAt(i)=="*") { //If asterisk (hidden letter), add blank
				html += game.blankHTML;
			}
			else if (game.title.charAt(i)==" ") { //Space
				html += '<p></p>';
			}
			else { //Otherwise print out character
				html += game.nakedCharHTMLOpen+game.title.charAt(i)+game.nakedCharHTMLClose;
			}
		}
		game.blanksContainer.html(html);

		//Select blanks after they are created
		game.blanks = $(".game-blank");
		game.moveBlank(0, 0, false);  //Move cursor to first blank
		game.blanks.keyup(function(e) {
			game.processKeyPress(this, e);
		}).keydown(function(e) {
			game.processKeyPress(this, e);
		});
	}
	this.moveBlank = function(currentBlankIndex, nextBlankIndex, cycle) {
		//Move focus
		if (nextBlankIndex>=0&&nextBlankIndex<this.blanks.length) { //Next blank exists
		}
		else { 
			if (cycle) { //Loop around
				if (nextBlankIndex>currentBlankIndex) { //We fell off the right side of the blanks
					nextBlankIndex = 0;
				}
				else { //We fell off the left side
					nextBlankIndex = this.blanks.length-1;
				}
			}
			else { //Remain put
				nextBlankIndex = currentBlankIndex;
			}
		}
		var next =  this.blanks.eq(nextBlankIndex);
		next.focus();
		//Move cursor to end of text input
		var tmpStr = next.val();
		next.val('');
		next.val(tmpStr);
	}
	this.processKeyPress = function(element, e) {
		var keyCode = e.keyCode;
		var key = e.key
		var val = $(element).val(); //Value BEFORE character is changed
		var currentBlankIndex = this.blanks.index(element);

		//Process keydown and keyup
		if (e.type=="keydown") {
			if (keyCode==8) { //Backspace
				if (val=="") { //Only go back if blank is empty
					this.moveBlank(currentBlankIndex, currentBlankIndex-1, false);
				}
			}
		}
		else if (e.type=="keyup") {
			if (keyCode>=65&&keyCode<=90) { //A-Z
				$(element).val(key.toUpperCase());
				this.moveBlank(currentBlankIndex, currentBlankIndex+1, false);
			}
			else if (keyCode==38||keyCode==39) { //Right or Up Arrow
				this.moveBlank(currentBlankIndex, currentBlankIndex+1, true);
			}
			else if (keyCode==37||keyCode==40) { //Left or Down Arrow
				this.moveBlank(currentBlankIndex, currentBlankIndex-1, true);
			}
			else if (keyCode==8) { //Backspace - Only handle on .keydown event
			}
			else { //Only allow letters - delete all other characters
				$(element).val(val);
			}
			//Submit response if all blanks are filled
			var allBlanksFilled = this.blanks.filter(function() { return this.value === ""; }).length==0;
			if (allBlanksFilled) {
				this.submit();
			}
		}

	}
	this.checkBlanks = function(answer) {
		for (var i = 0; i<answer.length; i++) {
			var borderClass = 'border-success';
			if (answer.charAt(i)=='*') { //Wrong letter
				borderClass = 'border-danger';
			}
			game.blanks.eq(i).removeClass('border-success').removeClass('border-danger').addClass(borderClass);
		}
		game.blanks.filter(".border-danger:first").focus();
		if (game.correctAnswer) {
			game.blanks.prop('disabled', true); //Disable blanks
		}
	}

	this.skipLogo = function() {
		this.restart();
	}
	this.displaySkip = function() {
		var game = this;
		game.skipContainer.html(this.skipHTML);
		game.skip = $("#game-skip");
		game.skip.prop('disabled', false);
		game.skip.focus();
		game.skip.click(function() {
			game.skip.prop('disabled', true); //Temporarily disable (to prevent multiple skips from double clicks)
			game.skipLogo();
		});
	}
	this.displayNextGameButton = function() {
		var game = this;
		game.skipContainer.html(this.nextHTML);
		game.next = $("#game-next");
		game.next.prop('disabled', false);
		game.next.focus();
		game.next.click(function() {
			game.next.prop('disabled, true'); //Temporarily disable
			game.restart();
		})
	}

	this.submit = function() {
		var game = this;
		game.answer = "";
		game.blanks.each(function() { 
			game.answer += $(this).val();
		});
		this.submitAnswer(game.answer, function() {
			if (game.correctAnswer) {
				game.success();
			}
		});
	}
	this.submitAnswer = function(answer, callbackfx) {
		var game = this;
		$.ajax({
			type: "POST",
			url: this.ajaxPHP['submitAnswer'],
			data: { 
				answer: game.answer
			},
			dataType: "JSON"
		})
		.done(function(jsonResponse) {
			if (!game.correctAnswer) { //User hasn't already solved it with a different AJAX request
				if (jsonResponse.result=="success") {
					if (jsonResponse.response=="correct") { //Correct response
						game.correctAnswer = true;
						game.descriptionWikiTitle = jsonResponse.descriptionWikiTitle;
						game.checkBlanks(game.answer);
					}
					else { //Incorrect
						game.checkBlanks(jsonResponse.updatedAnswer);
					}
					callbackfx();
				}
				else {
					defaultAJAXresultcheck(jsonResponse);
				}
			}
		});
	}

	this.success = function() {
		var game = this;
		game.stopTimer();
		game.displaySuccess();
	}
	this.displaySuccess = function() {
		var game = this;
		game.displayLogoSuccessBoundary();
		game.displayNextGameButton();
		game.getDescription(function() {
			game.displayDescription();
		});
	}

	this.won = function() {
		var game = this;
		game.displayWonMessage();
	}
	this.displayWonMessage = function() {
		var game = this;
		game.container.html(game.wonMessageHTML);
	}
}

var game = new Game();

//User clicks Welcome page's "Start Game!" button
$("#welcome-startgame").click( function() {
	game.initiate();
	//Animate start of game
	$("#welcome-jumbotron").hide("blind", { direction: "horizontal" }, 1000);
	$("#game-container").delay(1000).show("blind", { direction: "horizontal" }, 1000, function() {
		game.moveBlank(0, 0, false);  //Move cursor to first blank
		game.startTimer();
	});
});