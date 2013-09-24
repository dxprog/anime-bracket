<?xml version="1.0"?>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform" xmlns:php="http://php.net/xsl">
    <xsl:output method="html" />
    <xsl:template match="/">
        <form action="#" method="post">
            <label for="entrants">Entrant Counts</label>
            <select name="entrants" id="entrants">
                <xsl:for-each select="//create_bracket_item">
                    <option value="{.}"><xsl:value-of select="." /></option>
                </xsl:for-each>
            </select>
            <label for="groups">Group Count</label>
            <input type="text" name="groups" id="groups" />
            <button type="submit">Create</button>
        </form>
    </xsl:template>
</xsl:stylesheet>