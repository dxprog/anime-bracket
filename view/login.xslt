<?xml version="1.0" ?>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform" xmlns:php="http://php.net/xsl">
    <xsl:output method="html" />
    <xsl:template match="/">
        <div class="login-form">
            <a href="{//loginUrl}">Login</a>
            <p>To help maintain integrity of voting, we require that you authenticate with your reddit account. We just want to make sure you're a unique person  and don't have any access to your password or browsing information. We do require that your account be at least on month old.</p>
        </div>
    </xsl:template>
</xsl:stylesheet>