<?xml version="1.0" ?>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform" xmlns:php="http://php.net/xsl">
	<xsl:output method="html" />
	<xsl:template match="/">
		<xsl:variable name="guest"><xsl:if test="//userId = '0'">yes</xsl:if></xsl:variable>
		<xsl:variable name="tier"><xsl:value-of select="//round_item[@index='0']/tier" /></xsl:variable>
		<xsl:variable name="group">
			<xsl:choose>
				<xsl:when test="//round_item[@index='0']/group = //round_item[@index='1']/group">
					<xsl:value-of select="//round_item[@index='0']/group" />
				</xsl:when>
				<xsl:otherwise>all</xsl:otherwise>
			</xsl:choose>
		</xsl:variable>
		<h2>
			<xsl:choose>
				<xsl:when test="$tier = '0'">Elimination Round - </xsl:when>
				<xsl:when test="$tier &gt; 0">
					<xsl:choose>
						<xsl:when test="count(//round_item[character2Id != '1']) = 0">Redemption Round - </xsl:when>
						<xsl:otherwise>Round <xsl:value-of select="$tier" /> - </xsl:otherwise>
					</xsl:choose>
				</xsl:when>
				<xsl:otherwise>Thanks for voting!</xsl:otherwise>
			</xsl:choose>
			<xsl:choose>
				<xsl:when test="$group = '0'">Group A</xsl:when>
				<xsl:when test="$group = '1'">Group B</xsl:when>
				<xsl:when test="$group = '2'">Group C</xsl:when>
				<xsl:when test="$group = '3'">Group D</xsl:when>
				<xsl:when test="$group = '4'">Group E</xsl:when>
				<xsl:when test="$group = '5'">Group F</xsl:when>
				<xsl:when test="$group = '6'">Group G</xsl:when>
				<xsl:when test="$group = 'all' and count(//round_item) &gt; 0">All Groups</xsl:when>
			</xsl:choose>
		</h2>
		
		<xsl:if test="$guest != 'yes'">
			<xsl:choose>
				<xsl:when test="count(//round_item[character2Id != '1']) = 0 and $tier &gt; 0">
					<h3>You may resurrect one girl to go on to the finals</h3>
				</xsl:when>
				<xsl:when test="$tier &gt; -1">
					<h3>Place your votes below</h3>
				</xsl:when>
				<xsl:otherwise>
					<h3>You have made the catgirls purr</h3>
				</xsl:otherwise>
			</xsl:choose>
		</xsl:if>
		<p class="message"></p>
		<div class="vote-success">
			<p>Thanks for voting! Now go and grab yourself a nice cup of tea and some cake and relax a bit. Alternatively, you can <a href="http://www.reddit.com/r/awwnime/about/sticky/" target="_blank">checkout the discussion</a></p>
			<a href="/{//bracket/perma}/vote/" class="button">Vote on remaining rounds</a>
			<img src="/images/{//bracket/perma}-post-voting.png" alt="Thanks for voting!" />
		</div>
		<!--
		<div id="survey">
			<h4>If you have a moment, please be a peach and fill out this survey =). If you've already filled it out, proceed to ignore.</h4>
			<iframe src="https://docs.google.com/spreadsheet/embeddedform?formkey=dFk3Yko4VTVCbTFKWm03TnRoZXE4MHc6MQ" width="760" height="1179" frameborder="0" marginheight="0" marginwidth="0">Loading...</iframe>
		</div>
		-->
		<xsl:choose>
			<xsl:when test="count(//round_item) &gt; 0">
				<div class="rounds">
					<xsl:choose>
						<xsl:when test="$tier = 0">
							<xsl:attribute name="class">rounds elimination</xsl:attribute>
							<xsl:call-template name="wildcard_round" />
						</xsl:when>
						<xsl:when test="count(//round_item[character2Id != '1']) = 0">
							<xsl:attribute name="class">rounds wildcard</xsl:attribute>
							<xsl:call-template name="wildcard_round" />
						</xsl:when>
						<xsl:otherwise>
							<xsl:apply-templates select="//round_item" />
						</xsl:otherwise>
					</xsl:choose>
					<xsl:if test="$guest != 'yes'">
						<label for="prizes" class="prizes">
							<input id="prizes" type="checkbox" />
							<xsl:if test="//user/prizes = '1'">
								<xsl:attribute name="checked">checked</xsl:attribute>
							</xsl:if>
							I'd like to be entered to win fabulous prizes. I understand that I'll have to tell the mods where I live so they can send me said fabulous prizes.
						</label>
						<p class="disclaimer">Remember, once your vote for an entrant is cast, you can't take it back. Be sure your selections are where you want them.</p>
						<button>Submit Vote</button>
					</xsl:if>
				</div>
			</xsl:when>
			<xsl:otherwise>
				<p class="disclaimer">You have voted on all the entrants today. Come back tomorrow to cast your votes again.</p>
			</xsl:otherwise>
		</xsl:choose>

		<xsl:if test="$guest != 'yes'">
			<script type="text/javascript">
				window.bracketId = <xsl:value-of select="//round_item[@index = '0']/bracketId" />;
			</script>
			<script type="text/javascript" src="/view/anime-bracket.min.js?20131102"></script>
		</xsl:if>

	</xsl:template>

	<xsl:template match="round_item">
		<div class="round" data-id="{id}">
			<xsl:if test="voted = 'true'">
				<xsl:attribute name="class">round voted</xsl:attribute>
			</xsl:if>
			<div class="entrant left" data-id="{character1Id}">
				<xsl:if test="votedCharacterId = character1Id">
					<xsl:attribute name="class">entrant left selected</xsl:attribute>
				</xsl:if>
				<img src="http://cdn.awwni.me/bracket/{character1/image}" alt="{character1/name}" />
				<h4><xsl:value-of select="character1/name" disable-output-escaping="yes" /></h4>
				<h5><xsl:value-of select="character1/source" disable-output-escaping="yes" /></h5>
			</div>
			<div class="entrant right" data-id="{character2Id}">
				<xsl:if test="votedCharacterId = character2Id">
					<xsl:attribute name="class">entrant right selected</xsl:attribute>
				</xsl:if>
				<img src="http://cdn.awwni.me/bracket/{character2/image}" alt="{character2/name}" />
				<h4><xsl:value-of select="character2/name" disable-output-escaping="yes" /></h4>
				<h5><xsl:value-of select="character2/source" disable-output-escaping="yes" /></h5>
			</div>
		</div>
	</xsl:template>
	
	<xsl:template name="wildcard_round">
		<xsl:for-each select="//round_item">
			<div class="round" data-id="{id}">
				<xsl:if test="voted = 'true'">
					<xsl:attribute name="class">round voted</xsl:attribute>
				</xsl:if>
				<div class="entrant" data-id="{character1Id}">
					<xsl:if test="voted ='true'">
						<xsl:attribute name="class">entrant selected</xsl:attribute>
					</xsl:if>
					<img src="http://cdn.awwni.me/bracket/{character1/image}" alt="{character1/name}" />
					<h4><xsl:value-of select="character1/name" disable-output-escaping="yes" /></h4>
					<h5>
						<xsl:variable name="source"><xsl:value-of select="character1/source" /></xsl:variable>
						<xsl:choose>
							<xsl:when test="php:function('strpos', $source, 'tp://') = 2">
								<a href="{$source}" target="_blank">See More Info <xsl:text disable-output-escaping="yes">&amp;raquo;</xsl:text></a>
							</xsl:when>
							<xsl:otherwise>
								<xsl:value-of select="$source" disable-output-escaping="yes" />
							</xsl:otherwise>
						</xsl:choose>
					</h5>
				</div>
			</div>
		</xsl:for-each>
	</xsl:template>
	
</xsl:stylesheet>