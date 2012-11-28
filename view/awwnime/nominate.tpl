<!DOCTYPE html>
<html>
	<head>
		<title>The Great 2012 Awwnime Bracket</title>
		<link rel="stylesheet" type="text/css" href="/view/awwnime/styles/awwnime.css" />
		<script src="//ajax.googleapis.com/ajax/libs/jquery/1.8.2/jquery.min.js"></script>
	</head>
	<body>
		<h1>The Great 2012 Awwnime Bracket</h1>
		<div id="nominate">
			<img src="/view/awwnime/styles/images/awwnime_madoka.png" alt="Her Magical Girl wish is for you to pick her" />
			<h2>Nominate your favorite cute anime girls</h2>
			<div class="info">
				<p>We are currently taking nominations for entrants into the bracket. You may nominate as many characters as you wish between now and October 22<sup>nd</sup> @ 12am CDT.</p>
				<p>Before we get started, though, some ground rules must be laid out:</p>
				<ul>
					<li>Females only. No traps allowed!</li>
					<li>Must originate from Japanese media. This includes, but is not limited to:
						<ul>
							<li>Anime</li>
							<li>Manga</li>
							<li>Light Novels</li>
							<li>Visual Novels *</li>
							<li>Games</li>
							<li>Software</li>
						</ul>
					</li>
				</ul>
				<a href="/nominate/" class="nominate">This seems fair. Take me to the form</a>
				<p class="footnote">* Western VNs are allowed. Looking at you, Katawa Shoujo.</p>
			</div>
			<div class="form">
				<label for="txtName">Character Name (e.g. Nagisa Furukawa):</label>
				<input type="text" name="txtName" id="txtName" />
				<label for="txtSource">Source (e.g. Clannad):</label>
				<input type="text" name="txtSource" id="txtSource" />
				<button>Nominate</button>
				<p id="message">Success!</p>
			</div>
		</div>
		<footer>
			
		</footer>
		<script type="text/javascript">
			(function() {
				
				var
				
				$nominate = $('#nominate'),
				$txtName = $('#txtName'),
				$txtSource = $('#txtSource'),
				$message = $('#message'),
				
				isIE = (/MSIE/).test(window.navigator.userAgent),
				
				displayMessage = function(message) {
					$message
						.stop()
						.css({ top:0, opacity:0 })
						.html(message)
						.animate({ top:'-68px', opacity:1 }, 400, function() {
							setTimeout(function() { $message.animate({ opacity:0 }); }, 1000);
						});
				},
				
				nomineeCallback = function(data) {
					displayMessage(data.success ? 'Success!' : data.message);
					$txtName.focus().val(data.success ? '' : $txtName.val());
				},
				
				nomineeKeypress = function(e) {
					if ((e.keyCode == 13 || e.charCode == 13) && !isIE) {
						nomineeSubmit(null);
					}
				},
				
				nomineeSubmit = function(e) {
					
					var submit = $txtName.val().length && $txtSource.val().length;
					
					if (null != e) {
						e.preventDefault();
					}
					
					$nominate.find('.error').removeClass('error');
					
					if (!submit) {
						if (!$txtName.val().length) {
							$txtName.addClass('error');
						}
						if (!$txtSource.val().length) {
							$txtSource.addClass('error');
						}
					} else {
					
						$.ajax({
							url:'/process.php?action=nominate',
							dataType:'json',
							type:'POST',
							data:{ bracketId:3, nomineeName:$txtName.val(), nomineeSource:$txtSource.val() },
							success:nomineeCallback
						});
					
					}
				},
				
				formShow = function(e) {
					$nominate.find('.info').hide();
					$nominate.find('.form').show();
					$txtName.focus();
					e.preventDefault();
				},
				
				init = (function() {
					$nominate.find('.info a').on('click', formShow);
					$nominate.find('button').on('click', nomineeSubmit);
					$nominate.find('input').on('keypress', nomineeKeypress);
				}());
				
			}());
			var _gaq=_gaq||[];_gaq.push(["_setAccount","UA-280226-6"]);_gaq.push(["_trackPageview"]);(function(){var a=document.createElement("script");a.type="text/javascript";a.async=!0;a.src=("https:"==document.location.protocol?"https://ssl":"http://www")+".google-analytics.com/ga.js";var b=document.getElementsByTagName("script")[0];b.parentNode.insertBefore(a,b)})();
		</script>
	</body>
</html>