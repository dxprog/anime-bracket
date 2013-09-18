<!DOCTYPE html>
<html>
	<head>
		<title>{TITLE}</title>
		<link rel="stylesheet" type="text/css" href="/view/css/styles.css" />
		<script src="//ajax.googleapis.com/ajax/libs/jquery/1.8.2/jquery.min.js"></script>
	</head>
	<body>
		<a href="/">
			<h1>The Great Awwnime Bracket</h1>
		</a>
		<div id="nominate">
			<img src="/images/{PERMA}-nominations.png" alt="Her Magical Girl wish is for you to pick her" />
			<div class="info">
				{RULES}
				<a href="/nominate/" class="nominate">This seems fair. Take me to the form</a>
			</div>
			<div class="form">
				<label for="txtName">Character Name:</label>
				<p class="footnote">In Japanese order, please. <em>Example: Takanashi Rikka</em></p>
				<input type="text" name="txtName" id="txtName" />
				<label for="txtSource">Source:</label>
				<p class="footnote">Shortened where possible. <em>Example: Chuunibyou</em></p>
				<input type="text" name="txtSource" id="txtSource" />
				<label for="txtPic">Link to Character Picture:</label>
				<p class="footnote">Hot linking is fine. We'll take care of rehosting.</p>
				<input type="text" name="txtPic" id="txtPic" />
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
				$txtPic = $('#txtPic'),
				$message = $('#message'),
				bracketId = {BRACKET_ID},
				
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
					$txtPic.val(data.success ? '' : $txtPic.val());
				},
				
				nomineeKeypress = function(e) {
					if ((e.keyCode == 13 || e.charCode == 13) && !isIE) {
						nomineeSubmit(null);
					}
				},
				
				nomineeSubmit = function(e) {
					
					var submit = $txtName.val().length && $txtSource.val().length && $txtPic.val().length;
					
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
						if (!$txtPic.val().length) {
							$txtPic.addClass('error');
						}
					} else {
					
					$.ajax({
						url:'/process.php?action=nominate',
						dataType:'json',
						type:'POST',
						data:{ bracketId:bracketId, nomineeName:$txtName.val(), nomineeSource:$txtSource.val(), image:$txtPic.val() },
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