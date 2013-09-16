<?xml version="1.0" ?>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
	<xsl:template match="/">
		<p>
			<xsl:value-of select="//message" />
		</p>
		<xsl:if test="string-length(//code) &gt; 0 and //code != 404">
			<p>
				The system's dying words were: <xsl:value-of select="//code" />
			</p>
		</xsl:if>
	</xsl:template>
</xsl:stylesheet>