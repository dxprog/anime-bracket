<?xml version="1.0" ?>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform" xmlns:php="http://php.net/xsl">
	<xsl:output method="html" />
	<xsl:template match="/">
		<xsl:variable name="tier"><xsl:value-of select="//round_item[@index='0']/roundTier" /></xsl:variable>
		<xsl:variable name="group">
			<xsl:choose>
				<xsl:when test="//round_item[@index='0']/roundGroup = //round_item[@index='1']/roundGroup">
					<xsl:value-of select="//round_item[@index='0']/roundGroup" />
				</xsl:when>
				<xsl:otherwise>all</xsl:otherwise>
			</xsl:choose>
		</xsl:variable>
		<h2>
			<xsl:choose>
				<xsl:when test="$tier = '0'">Elimination Round - </xsl:when>
				<xsl:when test="$tier &gt; 0">
					<xsl:choose>
						<xsl:when test="count(//round_item[roundCharacter2Id != '1']) = 0">Redemption Round - </xsl:when>
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
				<xsl:when test="$group = 'all' and count(//round_item) &gt; 0">All Groups</xsl:when>
			</xsl:choose>
		</h2>
		
		<xsl:choose>
			<xsl:when test="count(//round_item[roundCharacter2Id != '1']) = 0 and $tier &gt; -1">
				<h3>You may resurrect one girl to go on to the finals</h3>
			</xsl:when>
			<xsl:when test="$tier &gt; -1">
				<h3>Place your votes below</h3>
			</xsl:when>
			<xsl:otherwise>
				<h3>You have made the catgirls purr</h3>
			</xsl:otherwise>
		</xsl:choose>
		<p class="message"></p>
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
						<xsl:when test="count(//round_item[roundCharacter2Id != '1']) = 0">
							<xsl:attribute name="class">rounds wildcard</xsl:attribute>
							<xsl:call-template name="wildcard_round" />
						</xsl:when>
						<xsl:otherwise>
							<xsl:apply-templates select="//round_item" />
						</xsl:otherwise>
					</xsl:choose>
				</div>
				<p class="disclaimer">Remember, once your vote for an entrant is cast, you can't take it back. Be sure your selections are where you want them.</p>
				<button>Submit Vote</button>
			</xsl:when>
			<xsl:otherwise>
				<p class="disclaimer">You have voted on all the entrants today. Come back tomorrow to cast your votes again.</p>
			</xsl:otherwise>
		</xsl:choose>
	</xsl:template>

	<xsl:template match="round_item">
		<div class="round" data-id="{roundId}">
			<div class="entrant left" data-id="{roundCharacter1Id}">
				<img src="http://cdn.awwni.me/bracket/{roundCharacter1/characterImage}" alt="{roundCharacter1/characterName}" />
				<h4><xsl:value-of select="roundCharacter1/characterName" disable-output-escaping="yes" /></h4>
				<h5><xsl:value-of select="roundCharacter1/characterSource" disable-output-escaping="yes" /></h5>
			</div>
			<div class="entrant right" data-id="{roundCharacter2Id}">
				<img src="http://cdn.awwni.me/bracket/{roundCharacter2/characterImage}" alt="{roundCharacter2/characterName}" />
				<h4><xsl:value-of select="roundCharacter2/characterName" disable-output-escaping="yes" /></h4>
				<h5><xsl:value-of select="roundCharacter2/characterSource" disable-output-escaping="yes" /></h5>
			</div>
		</div>
	</xsl:template>
	
	<xsl:template name="wildcard_round">
		<xsl:for-each select="//round_item">
			<div class="round" data-id="{roundId}">
				<div class="entrant" data-id="{roundCharacter1Id}">
					<img src="http://cdn.awwni.me/bracket/{roundCharacter1/characterImage}" alt="{roundCharacter1/characterName}" />
					<h4><xsl:value-of select="roundCharacter1/characterName" disable-output-escaping="yes" /></h4>
					<h5><xsl:value-of select="roundCharacter1/characterSource" disable-output-escaping="yes" /></h5>
				</div>
			</div>
		</xsl:for-each>
	</xsl:template>
	
</xsl:stylesheet>