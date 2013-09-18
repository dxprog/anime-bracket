<?xml version="1.0"?>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform" xmlns:php="http://php.net/xsl">
    <xsl:output method="html" />
    <xsl:template match="/">
        <form action="/admin/?action=eliminations" method="get">
            <label for="days">How many days of eliminations?</label>
            <p>There are currently <xsl:value-of select="//count" /> characters in the pool</p>
            <input type="text" id="days" name="days" />
            <input type="hidden" name="bracket" value="{//id}" />
            <input type="hidden" name="action" value="eliminations" />
            <button type="submit">Submit</button>
        </form>
    </xsl:template>
</xsl:stylesheet>