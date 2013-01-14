<!DOCTYPE html>
<html>
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
		<title>{TITLE}</title>
		<link rel="stylesheet" type="text/css" href="/view/awwnime/styles/awwnime.css?20121117T1639" />
		<link rel="stylesheet" type="text/css" href="/view/awwnime/styles/bracket_{BRACKET_ID}.css?20120113" />
		<script src="//ajax.googleapis.com/ajax/libs/jquery/1.8.2/jquery.min.js"></script>
	</head>
	<body>
		<h1>The Great 2012 Awwnime Bracket</h1>
		<div id="round">
			{CONTENT}
		</div>
		<script type="text/javascript">
			(function() {
			
				var
				
				$votes = [],
				
				voteCallback = function(data) {
					
					var message = '';
					
					if (data.success) {
						for (var i = 0, count = $votes.length; i < count; i++) {
							$votes[i].remove();
						}
						$('.wildcard .round').remove();
						$(window).scrollTop(0);
						if ($('.round').length > 0) {
							message = 'You can still vote on the remaining entrants or wait until tomorrow to vote again.';
						} else {
							message = 'You will have the opportunity to vote again tomorrow.';
							$('#round button,#round .disclaimer').hide();
						}
						$('#round .message').html('Your vote has been saved! ' + message).fadeIn();
						
					}
					
				},
				
				entrantClick = function(e) {
					var
						$this = $(e.currentTarget),
						$parent = $this.parent();
					
					if (e.target.tagName !== 'A' && !$parent.hasClass('voted')) {				
						$('.wildcard .selected').removeClass('selected');
						
						if (!$this.hasClass('selected')) {
							$parent.find('.selected').removeClass('selected');
							$this.addClass('selected');
						} else {
							$this.removeClass('selected');
						}
					}
				},
				
				submitClick = function(e) {
					var voteData = '';
					
					$('.round').each(function() {
						var
							$this = $(this),
							$selected = $this.find('.entrant.selected');
						
						if ($selected.length === 1) {
							voteData += ',' + $this.attr('data-id') + ',' + $selected.attr('data-id');
							$votes.push($this);
						}
					});
					
					if (voteData.length > 0) {
						voteData = voteData.substr(1);
						$.ajax({
							url:'/process.php?action=vote',
							type:'POST',
							dataType:'json',
							data:{ bracketId:4, votes:voteData},
							success:voteCallback
						});
					}
					
				},
				
				init = (function() {
					$('.entrant').on('click', entrantClick);
					$('button').on('click', submitClick);
				}());
			
			}());
		</script>
	</body>
</html>