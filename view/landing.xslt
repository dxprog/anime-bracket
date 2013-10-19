<?xml version="1.0" ?>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform" xmlns:php="http://php.net/xsl">
    <xsl:output method="html" />
    <xsl:template match="/">
        <div class="intro landing-section">
            <h2>Which Girl is the Most Moe?</h2>
            <p>The time has come once again to crown the Queen of Moe.</p>
            <p>Join us starting October 26th in a battle of cuteness as the most moe girls of the last two years are pitted against each other for the title of Queen.</p>
            <xsl:for-each select="//landing[id = 6]">
                <xsl:choose>
                    <xsl:when test="state = 1">
                        <a href="/{perma}/nominate/" class="button">Nominate Characters</a>
                    </xsl:when>
                    <xsl:when test="state = 2">
                        <a href="/{perma}/vote/" class="button">Vote In Eliminations</a>
                    </xsl:when>
                    <xsl:when test="state = 3">
                        <a href="/{perma}/vote/" class="button">Cast Your Vote</a>
                        <a href="/{perma}/view/" class="arrow-button">See Results</a>
                    </xsl:when>
                </xsl:choose>
            </xsl:for-each>
            <a href="/brackets/" class="arrow-button old-brackets-button">Previous Brackets</a>
        </div>
    </xsl:template>
</xsl:stylesheet>