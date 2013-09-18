<?xml version="1.0"?>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform" xmlns:php="http://php.net/xsl">
    <xsl:output method="html" />
    <xsl:template match="/">
        <ul>
            <xsl:for-each select="//brackets_overview_item[state != '6']">
                <li>
                    <xsl:value-of select="name" />
                    <ul>
                        <li><a href="/admin/?action=editForm&amp;bracket={id}">Edit Bracket Information</a></li>
                        <xsl:if test="state != '5'">
                            <xsl:if test="state = '0'">
                                <li><a href="/admin/?action=setState&amp;state=1&amp;bracket={id}">Start Nominations</a></li>
                            </xsl:if>
                            <xsl:if test="state = '1'">
                                <li><a href="/admin/?action=nominations&amp;bracket={id}">View Nominations</a></li>
                                <li><a href="/admin/?action=eliminations&amp;bracket={id}">Start Eliminations</a></li>
                            </xsl:if>
                            <xsl:if test="state = '2'">
                                <li><a href="/admin/?action=setState&amp;state=3&amp;bracket={id}">Start Voting</a></li>
                            </xsl:if>
                            <xsl:if test="state = '3'">
                                <li><a href="/admin/?action=setState&amp;state=0&amp;bracket={id}">Pause Bracket</a></li>
                                <li><a href="/admin/?action=setState&amp;state=4&amp;bracket={id}">Start Wildcards</a></li>
                                <li><a href="/admin/?action=setState&amp;state=5&amp;bracket={id}">End Bracket</a></li>
                            </xsl:if>
                            <xsl:if test="state = '3' or state = '4'">
                                <li><a href="/admin/?action=advance&amp;bracket={id}">Finalize Tier</a></li>
                                <li><a href="/admin/?action=results&amp;bracket={id}">Results for Current Tier</a></li>
                            </xsl:if>
                        </xsl:if>
                    </ul>
                </li>
            </xsl:for-each>
        </ul>
    </xsl:template>
</xsl:stylesheet>