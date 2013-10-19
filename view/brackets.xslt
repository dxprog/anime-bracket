<?xml version="1.0" ?>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform" xmlns:php="http://php.net/xsl">
    <xsl:output method="html" />
    <xsl:template match="/">
        <div class="brackets">
            <div class="wrapper">
                <xsl:for-each select="//brackets_item[state != 6]">
                    <xsl:sort select="id" data-type="number" order="descending" />
                    <div class="bracket">
                        <h3><xsl:value-of select="name" /></h3>
                        <img src="{pic}" alt="{name}" />
                        <xsl:choose>
                            <xsl:when test="state = 5">
                                <h4><xsl:value-of select="winner/name" /></h4>
                                <a href="/{perma}/view/" class="button">View Bracket Results</a>
                            </xsl:when>
                            <xsl:when test="state = 1">
                                <h4>Accepting Nominations</h4>
                                <a href="/{perma}/nominate/" class="button">Nominate Characters</a>
                            </xsl:when>
                            <xsl:when test="state = 2">
                                <h4>Eliminations Round</h4>
                                <a href="/{perma}/vote/" class="button">Vote In Eliminations</a>
                            </xsl:when>
                            <xsl:when test="state = 3">
                                <h4>Voting In Progress</h4>
                                <a href="/{perma}/vote/" class="button">Cast Your Vote</a>
                                <a href="/{perma}/view/" class="button">See Results</a>
                            </xsl:when>
                            <xsl:when test="state = 0">
                                <xsl:variable name="date"><xsl:value-of select="start" /></xsl:variable>
                                <h4>Begins <xsl:value-of select="php:function('date', 'M. jS', $date)" /></h4>
                            </xsl:when>
                        </xsl:choose>
                    </div>
                </xsl:for-each>
            </div>
        </div>
    </xsl:template>
</xsl:stylesheet>