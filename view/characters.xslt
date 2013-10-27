<?xml version="1.0" ?>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform" xmlns:php="http://php.net/xsl">
    <xsl:output method="html" />
    <xsl:template match="/">
        <div id="round" class="characters">
            <h2><xsl:value-of select="//bracket/name" /></h2>
            <h3>Full Character Pool</h3>
            <ul>
                <xsl:for-each select="//characters_item">
                    <li class="entrant">
                        <img src="http://cdn.awwni.me/bracket/{image}" alt="{name}" />
                        <h4><xsl:value-of select="name" disable-output-escaping="yes" /></h4>
                        <h5>
                            <xsl:variable name="source"><xsl:value-of select="source" /></xsl:variable>
                            <xsl:value-of select="$source" disable-output-escaping="yes" />
                        </h5>
                    </li>
                </xsl:for-each>
            </ul>
        </div>
    </xsl:template>
</xsl:stylesheet>