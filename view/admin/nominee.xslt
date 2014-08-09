<?xml version="1.0"?>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform" xmlns:php="http://php.net/xsl">
    <xsl:output method="html" />
    <xsl:template match="/">
        <xsl:choose>
            <xsl:when test="string-length(//nominee/id) &gt; 0">
                <form action="/admin/?action=nominations&amp;bracket={//bracket/id}" method="post" enctype="multipart/form-data">
                    <div class="nominee">
                        <h2>Nominee</h2>
                        <dl>
                            <dt>Image</dt>
                            <dd class="image"><img src="{//nominee/image}" /></dd>
                            <dt>Name</dt>
                            <dd class="name"><input type="text" name="name" value="{//nominee/name}" /></dd>
                            <dt>Source</dt>
                            <dd class="source"><input type="text" name="source" value="{//nominee/source}" /></dd>
                            <dt>Headshot</dt>
                            <dd><input type="file" name="headshot" /></dd>
                        </dl>
                        <button type="submit" class="button" name="form_action" value="create">Enter Character in Bracket</button>
                        <xsl:if test="count(//similar/similar_item) &gt; 0">
                            <h2>Similar Nominees</h2>
                            <table>
                                <xsl:for-each select="//similar/similar_item">
                                    <tr>
                                        <td rowspan="2">
                                            <input type="checkbox" name="chkProcess[]" value="{id}" />
                                        </td>
                                        <td rowspan="2">
                                            <a class="thumb" style="background-image:url({image})" href="{image}" target="_blank">Thumbnail</a>
                                        </td>
                                        <td>
                                            <xsl:value-of select="name" disable-output-escaping="yes" />
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>
                                            <xsl:value-of select="source" disable-output-escaping="yes" />
                                        </td>
                                    </tr>
                                </xsl:for-each>
                            </table>
                        </xsl:if>
                        <xsl:variable name="bracketId" select="//bracket/id" />
                        <xsl:if test="count(//character/character_item[bracketId = $bracketId]) &gt; 0">
                            <h2>Similar Characters in this Bracket</h2>
                            <table>
                                <xsl:for-each select="//character/character_item[bracketId = $bracketId]">
                                    <tr>
                                        <td rowspan="2">
                                            <a class="thumb" style="background-image:url(//cdn.awwni.me/bracket/{image})" href="{image}" target="_blank">Thumbnail</a>
                                        </td>
                                        <td>
                                            <xsl:value-of select="name" disable-output-escaping="yes" />
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>
                                            <xsl:value-of select="source" disable-output-escaping="yes" />
                                        </td>
                                    </tr>
                                </xsl:for-each>
                            </table>
                        </xsl:if>

                        <xsl:if test="count(//character/character_item[bracketId != $bracketId]) &gt; 0">
                            <h2>Similar Characters From Other Brackets</h2>
                            <table>
                                <xsl:for-each select="//character/character_item[bracketId != $bracketId]">
                                    <tr>
                                        <td rowspan="2">
                                            <a class="thumb" style="background-image:url(//cdn.awwni.me/bracket/{image})" href="{image}" target="_blank">Thumbnail</a>
                                        </td>
                                        <td>
                                            <xsl:value-of select="name" disable-output-escaping="yes" />
                                        </td>
                                        <td rowspan="2">
                                            <button type="submit" class="button" name="form_action" value="clone|{id}">Use Character</button>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>
                                            <xsl:value-of select="source" disable-output-escaping="yes" />
                                        </td>
                                    </tr>
                                </xsl:for-each>
                            </table>
                        </xsl:if>

                        <input type="hidden" name="id" value="{//nominee/id}" />
                        <button type="submit" class="button" name="form_action" value="ignore">Ignore for Now</button>
                        <button type="submit" class="button" name="form_action" value="skip">Delete Checked Nominees</button>
                    </div>
                </form>
            </xsl:when>
            <xsl:otherwise>
                <h2>There are no unprocessed nominees</h2>
            </xsl:otherwise>
        </xsl:choose>
    </xsl:template>
</xsl:stylesheet>