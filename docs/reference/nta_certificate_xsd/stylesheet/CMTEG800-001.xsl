<?xml version="1.0" encoding="utf-8"?>
<xsl:stylesheet xmlns:xsl="http://www.w3.org/1999/XSL/Transform" xmlns:kyo="http://xml.e-tax.nta.go.jp/XSD/kyotsu" xmlns:gen="http://xml.e-tax.nta.go.jp/XSD/general" id="TEG800" version="1.0" exclude-result-prefixes="kyo gen">
	<!-- NAME="生命保険料控除証明書" ID="TEG800" VR="1.1" -->
	<xsl:template match="/">
		<HTML>
			<HEAD>
				<TITLE>生命保険料控除証明書データシート</TITLE>
			</HEAD>
			<style>
			BODY{font: 8pt ＭＳ Ｐ明朝;}
			TBODY{font: 8pt ＭＳ Ｐ明朝;}
			.newPage { page-break-before: always; }
			p { line-height: 100%; }
			td.line0 {border-top:solid 1px;border-left:solid 1px;border-right:none;border-bottom:solid 1px;}
			td.line1 {border-top:solid 1px;border-left:none;border-right:solid 1px;border-bottom:solid 1px;}
			td.line2 {border-top:solid 1px;border-left:solid 1px;border-right:solid 1px;border-bottom:none;}
			td.line3 {border-top:none;border-left:solid 1px;border-right:solid 1px;border-bottom:solid 1px;}
			td.line4 {border-top:solid 1px;border-left:solid 1px;border-right:solid 1px;border-bottom:solid 2px;}
			td.line5 {border-top:none;border-left:solid 1px;border-right:solid 1px;border-bottom:solid 2px;}
			td.line6 {border-top:solid 1px;border-left:none;border-right:none;border-bottom:solid 1px;}
			td.line7 {border-top:solid 3px;border-left:solid 3px;border-right:solid 3px;border-bottom:none;}
			td.line8 {border-top:none;border-left:solid 3px;border-right:solid 3px;border-bottom:solid 1px;}
			td.line9 {border-top:solid 1px;border-left:solid 3px;border-right:solid 3px;border-bottom:none;}
			td.line10 {border-top:none;border-left:solid 3px;border-right:solid 3px;border-bottom:solid 2px;}
			td.line11 {border-top:solid 1px;border-left:solid 3px;border-right:solid 3px;border-bottom:none;}
			td.line12 {border-top:none;border-left:solid 3px;border-right:solid 3px;border-bottom:solid 3px;}
			@media screen { table { width:900px;height:2px;}}
			@media screen { BODY{font: 8pt ＭＳ Ｐ明朝;}}
			@media screen { TBODY{font: 8pt ＭＳ Ｐ明朝;}}
			@media screen { .fs12 { font-size: 12pt; }}
			@media screen { .fs7 { font-size: 7pt; }}
			@media screen { .newLine1{height:107px;overflow:hidden;white-space:pre-wrap;}}
			@media print { BODY{font: 6pt ＭＳ Ｐ明朝;}}
			@media print { TBODY{font: 6pt ＭＳ Ｐ明朝;}}
			@media print { .fs12 { font-size: 9pt; }}
			@media print { .fs7 { font-size: 5pt; }}
			@media print { .newLine1{height:80px;overflow:hidden;white-space:pre-wrap;}}
			</style>
			<script type="text/javascript">
				<![CDATA[
					var S_stylesheet = document.styleSheets.item(0);
					if (navigator.userAgent.indexOf("MSIE") != -1 || navigator.userAgent.indexOf("Trident") != -1 || navigator.userAgent.indexOf("Edge") != -1){
						S_stylesheet.insertRule("@media print{ table { width:650px;height:1px;}}", S_stylesheet.cssRules.length)
					}else{
						S_stylesheet.insertRule("@media print{ table { width:750px;height:1px;}}", S_stylesheet.cssRules.length)
						S_stylesheet.insertRule("@media print{ body { transform: scale(0.866);}}", S_stylesheet.cssRules.length)
						S_stylesheet.insertRule("@media print{ body { transform-origin: top left;}}", S_stylesheet.cssRules.length)
					}
				]]>
			</script>
			<BODY oncontextmenu="return false">
				<xsl:apply-templates select="//kyo:TEG800"/>
			</BODY>
		</HTML>
	</xsl:template>
	<xsl:template match="kyo:TEG800[@VR='1.0'] | kyo:TEG800[@VR='1.1']">
		<TBODY>
			<xsl:choose>
				<xsl:when test="count(./kyo:WCE00000)=0">
					<table border="0" cellspacing="0" style="table-layout: fixed;">
						<tr height="19px">
							<td colspan="11" align="right"><span class="fs12"><b>　年分</b></span></td>
							<td colspan="78" align="center"><span class="fs12"><b>　データシート</b></span></td>
							<td colspan="11"/>
						</tr>
					</table>
					<br/>
					<table border="2" cellspacing="0" style="table-layout: fixed;">
						<tr height="15px">
							<td colspan="20" align="left">適用制度</td>
							<td colspan="80" align="left">　</td>
						</tr>
						<tr height="15px">
							<td colspan="20" align="left">証券番号</td>
							<td colspan="80" align="left">　</td>
						</tr>
						<tr height="15px">
							<td colspan="20" align="left">保険種類</td>
							<td colspan="80" align="left">　</td>
						</tr>
						<tr height="15px">
							<td colspan="20" align="left">契約日</td>
							<td colspan="5" align="right" class="line0">　年</td>
							<td colspan="4" align="right" class="line6">　月</td>
							<td colspan="4" align="right" class="line6">　日</td>
							<td colspan="67" class="line1"/>
						</tr>
						<tr height="26px">
							<td colspan="20" align="left">契約者</td>
							<td colspan="77" align="left" class="line0">
								<xsl:choose>
									<xsl:when test="count(./kyo:WCD00000)=0">　</xsl:when>
									<xsl:when test="./kyo:WCD00000=''">　</xsl:when>
									<xsl:otherwise><xsl:apply-templates select="./kyo:WCD00000"/></xsl:otherwise>
								</xsl:choose>
							</td>
							<td colspan="3" align="center" class="line1">様</td>
						</tr>
						<tr height="15px">
							<td colspan="20" align="left">事業所（団体）コード</td>
							<td colspan="80" align="left">　</td>
						</tr>
						<tr height="26px">
							<td colspan="20" align="left">被保険者</td>
							<td colspan="77" align="left" class="line0">　</td>
							<td colspan="3" align="center" class="line1">様</td>
						</tr>
						<tr height="15px">
							<td colspan="20" align="left">被保険者番号</td>
							<td colspan="80" align="left">　</td>
						</tr>
						<tr height="15px">
							<td colspan="20" align="left">払込方法</td>
							<td colspan="80" align="left">　</td>
						</tr>
						<tr height="26px">
							<td colspan="20" align="left">受取人</td>
							<td colspan="77" align="left" class="line0">　</td>
							<td colspan="3" align="center" class="line1">様</td>
						</tr>
					</table>
					<br/>
					<table border="0">
						<tr height="14">
							<td colspan="100" align="left">一般生命保険、または介護医療保険</td>
						</tr>
					</table>
					<table border="2" cellspacing="0" style="table-layout: fixed;">
						<tr height="15px">
							<td colspan="20" align="left">保険期間</td>
							<td colspan="3" align="right" class="line0">　</td>
							<td colspan="77" align="left" class="line1">　年間</td>
						</tr>
					</table>
					<br/>
					<table border="0">
						<tr height="14">
							<td colspan="100" align="left">個人年金保険</td>
						</tr>
					</table>
					<table border="2" cellspacing="0" style="table-layout: fixed;">
						<tr height="15px">
							<td colspan="20" align="left">受取人生年月日</td>
							<td colspan="5" align="right" class="line0">　年</td>
							<td colspan="4" align="right" class="line6">　月</td>
							<td colspan="4" align="right" class="line6">　日</td>
							<td colspan="17" class="line1"/>
							<td colspan="20" align="left">保険料払込期間</td>
							<td colspan="3" align="right" class="line0">　</td>
							<td colspan="27" align="left" class="line1">　年間</td>
						</tr>
						<tr height="15px">
							<td colspan="20" align="left">年金種類</td>
							<td colspan="80" align="left">　</td>
						</tr>
						<tr height="15px">
							<td colspan="20" align="left">年金支払期間</td>
							<td colspan="3" align="right" class="line0">　</td>
							<td colspan="27" align="left" class="line1">　年間</td>
							<td colspan="20" align="left">年金支払開始日</td>
							<td colspan="5" align="right" class="line0">　年</td>
							<td colspan="4" align="right" class="line6">　月</td>
							<td colspan="4" align="right" class="line6">　日</td>
							<td colspan="17" class="line1"/>
						</tr>
					</table>
					<br/>
					<table border="0">
						<tr height="15">
							<td colspan="100" align="left">証明日時点の保険料払込額を以下のとおり証明します。</td>
						</tr>
					</table>
					<table border="2" cellspacing="0" style="table-layout: fixed;">
						<tr height="15px">
							<td rowspan="4" colspan="5" align="center" class="line4">旧<br/>制<br/>度</td>
							<td rowspan="2" colspan="11" align="center">一般</td>
							<td colspan="28" align="left" class="line2">一般生命保険料(Ａ)</td>
							<td colspan="28" align="left" class="line2">配当金(相当額)(Ｂ)</td>
							<td colspan="28" align="left" class="line2">一般証明額(Ａ－Ｂ)</td>
						</tr>
						<tr height="15px">
							<td colspan="28" align="right" class="line3">　円</td>
							<td colspan="28" align="right" class="line3">　円</td>
							<td colspan="28" align="right" class="line3">　円</td>
						</tr>
						<tr height="15px">
							<td rowspan="2" colspan="11" align="center" class="line4">年金</td>
							<td colspan="28" align="left" class="line2">個人年金保険料(Ｃ)</td>
							<td colspan="28" align="left" class="line2">配当金(相当額)(Ｄ)</td>
							<td colspan="28" align="left" class="line2">個人年金証明額(Ｃ－Ｄ)</td>
						</tr>
						<tr height="15px">
							<td colspan="28" align="right" class="line5">　円</td>
							<td colspan="28" align="right" class="line5">　円</td>
							<td colspan="28" align="right" class="line5">　円</td>
						</tr>
						<tr height="15px">
							<td rowspan="6" colspan="5" align="center">新<br/>制<br/>度</td>
							<td rowspan="2" colspan="11" align="center">一般</td>
							<td colspan="28" align="left" class="line2">一般生命保険料(Ｅ)</td>
							<td colspan="28" align="left" class="line2">配当金(相当額)(Ｆ)</td>
							<td colspan="28" align="left" class="line2">一般証明額(Ｅ－Ｆ)</td>
						</tr>
						<tr height="15px">
							<td colspan="28" align="right" class="line3">　円</td>
							<td colspan="28" align="right" class="line3">　円</td>
							<td colspan="28" align="right" class="line3">　円</td>
						</tr>
						<tr height="15px">
							<td rowspan="2" colspan="11" align="center">介護医療</td>
							<td colspan="28" align="left" class="line2">介護医療保険料(Ｇ)</td>
							<td colspan="28" align="left" class="line2">配当金(相当額)(Ｈ)</td>
							<td colspan="28" align="left" class="line2">介護医療証明額(Ｇ－Ｈ)</td>
						</tr>
						<tr height="15px">
							<td colspan="28" align="right" class="line3">　円</td>
							<td colspan="28" align="right" class="line3">　円</td>
							<td colspan="28" align="right" class="line3">　円</td>
						</tr>
						<tr height="15px">
							<td rowspan="2" colspan="11" align="center">年金</td>
							<td colspan="28" align="left" class="line2">個人年金保険料(Ｉ)</td>
							<td colspan="28" align="left" class="line2">配当金(相当額)(Ｊ)</td>
							<td colspan="28" align="left" class="line2">個人年金証明額(Ｉ－Ｊ)</td>
						</tr>
						<tr height="15px">
							<td colspan="28" align="right" class="line3">　円</td>
							<td colspan="28" align="right" class="line3">　円</td>
							<td colspan="28" align="right" class="line3">　円</td>
						</tr>
					</table>
					<br/>
					<table border="0">
						<tr height="14">
							<td colspan="100" align="left">《ご参考》　 年 12 月末まで保険料をお払い込みの場合は下記金額をご申告ください。</td>
						</tr>
					</table>
					<table border="2" cellspacing="0" style="table-layout: fixed;">
						<tr height="15px">
							<td rowspan="4" colspan="5" align="center" class="line4">旧<br/>制<br/>度</td>
							<td rowspan="2" colspan="11" align="center">一般</td>
							<td colspan="28" align="left" class="line2">一般生命保険料(ａ)</td>
							<td colspan="28" align="left" class="line2">配当金(相当額)(ｂ)</td>
							<td colspan="28" align="left" class="line7">一般申告額(ａ－ｂ)</td>
						</tr>
						<tr height="15px">
							<td colspan="28" align="right" class="line3">　円</td>
							<td colspan="28" align="right" class="line3">　円</td>
							<td colspan="28" align="right" class="line8">　円</td>
						</tr>
						<tr height="15px">
							<td rowspan="2" colspan="11" align="center" class="line4">年金</td>
							<td colspan="28" align="left" class="line2">個人年金保険料(ｃ)</td>
							<td colspan="28" align="left" class="line2">配当金(相当額)(ｄ)</td>
							<td colspan="28" align="left" class="line9">個人年金申告額(ｃ－ｄ)</td>
						</tr>
						<tr height="15px">
							<td colspan="28" align="right" class="line5">　円</td>
							<td colspan="28" align="right" class="line5">　円</td>
							<td colspan="28" align="right" class="line10">　円</td>
						</tr>
						<tr height="15px">
							<td rowspan="6" colspan="5" align="center">新<br/>制<br/>度</td>
							<td rowspan="2" colspan="11" align="center">一般</td>
							<td colspan="28" align="left" class="line2">一般生命保険料(ｅ)</td>
							<td colspan="28" align="left" class="line2">配当金(相当額)(ｆ)</td>
							<td colspan="28" align="left" class="line11">一般申告額(ｅ－ｆ)</td>
						</tr>
						<tr height="15px">
							<td colspan="28" align="right" class="line3">　円</td>
							<td colspan="28" align="right" class="line3">　円</td>
							<td colspan="28" align="right" class="line8">　円</td>
						</tr>
						<tr height="15px">
							<td rowspan="2" colspan="11" align="center">介護医療</td>
							<td colspan="28" align="left" class="line2">介護医療保険料(ｇ)</td>
							<td colspan="28" align="left" class="line2">配当金(相当額)(ｈ)</td>
							<td colspan="28" align="left" class="line9">介護医療申告額(ｇ－ｈ)</td>
						</tr>
						<tr height="15px">
							<td colspan="28" align="right" class="line3">　円</td>
							<td colspan="28" align="right" class="line3">　円</td>
							<td colspan="28" align="right" class="line8">　円</td>
						</tr>
						<tr height="15px">
							<td rowspan="2" colspan="11" align="center">年金</td>
							<td colspan="28" align="left" class="line2">個人年金保険料(ｉ)</td>
							<td colspan="28" align="left" class="line2">配当金(相当額)(ｊ)</td>
							<td colspan="28" align="left" class="line9">個人年金申告額(ｉ－ｊ)</td>
						</tr>
						<tr height="15px">
							<td colspan="28" align="right" class="line3">　円</td>
							<td colspan="28" align="right" class="line3">　円</td>
							<td colspan="28" align="right" class="line12">　円</td>
						</tr>
					</table>
					<br/>
					<table border="0">
						<tr height="14">
							<td colspan="100" align="left">転換等一時払保険料</td>
						</tr>
					</table>
					<table border="2" cellspacing="0" style="table-layout: fixed;">
						<tr height="15px">
							<td colspan="33" align="left" class="line2">一般生命保険料</td>
							<td colspan="33" align="left" class="line2">介護医療保険料</td>
							<td colspan="34" align="left" class="line2">個人年金保険料</td>
						</tr>
						<tr height="15px">
							<td colspan="33" align="right" class="line3">　円</td>
							<td colspan="33" align="right" class="line3">　円</td>
							<td colspan="34" align="right" class="line3">　円</td>
						</tr>
					</table>
					<br/>
					<table border="0">
						<tr height="14">
							<td colspan="100" align="left">その他特記事項</td>
						</tr>
					</table>
					<table border="2" cellspacing="0" style="table-layout: fixed;">
						<tr>
							<td colspan="100" align="left">
								<p class="newLine1">　</p>
							</td>
						</tr>
					</table>
					<br/>
					<table border="0">
						<tr height="14">
							<td colspan="100" align="left">控除対象となる保険料は上記であることを証明いたします。</td>
						</tr>
					</table>
					<table border="0" cellspacing="0" style="table-layout: fixed;">
						<tr height="14">
							<td colspan="8" align="center">証明日</td>
							<xsl:choose>
								<xsl:when test="count(./kyo:WCC00000)=0">
									<td colspan="6" align="right">　年</td>
									<td colspan="4" align="right">　月</td>
									<td colspan="4" align="right">　日</td>
								</xsl:when>
								<xsl:otherwise>
									<td colspan="6" align="right">
										<xsl:choose>
											<xsl:when test="string-length(./kyo:WCC00000/gen:yyyy)&gt;4">
												<xsl:choose>
													<xsl:when test="substring(./kyo:WCC00000/gen:yyyy,1,1)='-'">
														<xsl:value-of select="format-number(substring(./kyo:WCC00000/gen:yyyy,(string-length(./kyo:WCC00000/gen:yyyy)-3),4),'-0000')"/>年
													</xsl:when>
													<xsl:otherwise>
														<xsl:value-of select="format-number(substring(./kyo:WCC00000/gen:yyyy,(string-length(./kyo:WCC00000/gen:yyyy)-3),4),'0000')"/>年
													</xsl:otherwise>
												</xsl:choose>
											</xsl:when>
											<xsl:when test="./kyo:WCC00000/gen:yyyy=''">　年</xsl:when>
											<xsl:otherwise>
												<xsl:value-of select="format-number(./kyo:WCC00000/gen:yyyy,'###0')"/>年
											</xsl:otherwise>
										</xsl:choose>
									</td>
									<td colspan="4" align="right">
										<xsl:choose>
											<xsl:when test="string-length(./kyo:WCC00000/gen:mm)&gt;2">
												<xsl:choose>
													<xsl:when test="substring(./kyo:WCC00000/gen:mm,1,1)='-'">
														<xsl:value-of select="format-number(substring(./kyo:WCC00000/gen:mm,(string-length(./kyo:WCC00000/gen:mm)-1),2),'-00')"/>月
													</xsl:when>
													<xsl:otherwise>
														<xsl:value-of select="format-number(substring(./kyo:WCC00000/gen:mm,(string-length(./kyo:WCC00000/gen:mm)-1),2),'00')"/>月
													</xsl:otherwise>
												</xsl:choose>
											</xsl:when>
											<xsl:when test="./kyo:WCC00000/gen:mm=''">　月</xsl:when>
											<xsl:otherwise>
												<xsl:value-of select="format-number(./kyo:WCC00000/gen:mm,'#0')"/>月
											</xsl:otherwise>
										</xsl:choose>
									</td>
									<td colspan="4" align="right">
										<xsl:choose>
											<xsl:when test="string-length(./kyo:WCC00000/gen:dd)&gt;2">
												<xsl:choose>
													<xsl:when test="substring(./kyo:WCC00000/gen:dd,1,1)='-'">
														<xsl:value-of select="format-number(substring(./kyo:WCC00000/gen:dd,(string-length(./kyo:WCC00000/gen:dd)-1),2),'-00')"/>日
													</xsl:when>
													<xsl:otherwise>
														<xsl:value-of select="format-number(substring(./kyo:WCC00000/gen:dd,(string-length(./kyo:WCC00000/gen:dd)-1),2),'00')"/>日
													</xsl:otherwise>
												</xsl:choose>
											</xsl:when>
											<xsl:when test="./kyo:WCC00000/gen:dd=''">　日</xsl:when>
											<xsl:otherwise>
												<xsl:value-of select="format-number(./kyo:WCC00000/gen:dd,'#0')"/>日
											</xsl:otherwise>
										</xsl:choose>
									</td>
								</xsl:otherwise>
							</xsl:choose>
							<td colspan="78"/>
						</tr>
					</table>
					<table border="0">
						<tr height="14">
							<td colspan="100" align="right">
								<xsl:choose>
									<xsl:when test="count(./kyo:WCA00000)=0">　</xsl:when>
									<xsl:when test="./kyo:WCA00000=''">　</xsl:when>
									<xsl:otherwise><xsl:apply-templates select="./kyo:WCA00000"/></xsl:otherwise>
								</xsl:choose>
							</td>
						</tr>
					</table>
					<table border="0">
						<tr><td colspan="100" align="left"><b><div align="right">(<xsl:value-of select="//kyo:TEG800/@softNM"/>)</div></b>
								<b>・ e-Taxを利用して確定申告される場合は、電子生命保険料控除証明書のデータファイルを添付して送信してください。</b><br/><br/>
								<b>・ 書面により確定申告される場合は、この「生命保険料控除証明書データシート」を印刷したものは使用できませんので、</b><br/>
								<b>書面により交付を受けた「生命保険料控除証明書」又は「QRコード付生命保険料控除証明書」を添付する必要があります。</b>
							</td></tr>
					</table>
					<br/>
				</xsl:when>
				<xsl:otherwise>
					<xsl:for-each select="..//kyo:WCE00000">
						<xsl:if test="position()&lt;101">
							<xsl:if test="position()!=1">
								<p class="newPage"/>
							</xsl:if>
							<table border="0" cellspacing="0" style="table-layout: fixed;">
								<tr height="19px">
									<xsl:choose>
										<xsl:when test="count(./kyo:WCE00010)=0">
											<td colspan="11" align="right"><span class="fs12"><b>　年分</b></span></td>
										</xsl:when>
										<xsl:otherwise>
											<td colspan="11" align="right"><span class="fs12"><b>
														<xsl:choose>
															<xsl:when test="string-length(./kyo:WCE00010/gen:yyyy)&gt;4">
																<xsl:choose>
																	<xsl:when test="substring(./kyo:WCE00010/gen:yyyy,1,1)='-'">
																		<xsl:value-of select="format-number(substring(./kyo:WCE00010/gen:yyyy,(string-length(./kyo:WCE00010/gen:yyyy)-3),4),'-0000')"/>　年分
																	</xsl:when>
																	<xsl:otherwise>
																		<xsl:value-of select="format-number(substring(./kyo:WCE00010/gen:yyyy,(string-length(./kyo:WCE00010/gen:yyyy)-3),4),'0000')"/>　年分
																	</xsl:otherwise>
																</xsl:choose>
															</xsl:when>
															<xsl:when test="./kyo:WCE00010/gen:yyyy=''">　年分</xsl:when>
															<xsl:otherwise>
																<xsl:value-of select="format-number(./kyo:WCE00010/gen:yyyy,'###0')"/>　年分
															</xsl:otherwise>
														</xsl:choose>
													</b></span></td>
										</xsl:otherwise>
									</xsl:choose>
									<td colspan="78" align="center"><span class="fs12"><b>
												<xsl:choose>
													<xsl:when test="count(./kyo:WCE00020)=0">　データシート</xsl:when>
													<xsl:when test="./kyo:WCE00020=''">　データシート</xsl:when>
													<xsl:otherwise><xsl:apply-templates select="./kyo:WCE00020"/>データシート</xsl:otherwise>
												</xsl:choose>
											</b></span></td>
									<td colspan="11"/>
								</tr>
							</table>
							<br/>
							<table border="2" cellspacing="0" style="table-layout: fixed;">
								<tr height="15px">
									<td colspan="20" align="left">適用制度</td>
									<td colspan="80" align="left">
										<xsl:choose>
											<xsl:when test="count(./kyo:WCE00030/kyo:kubun_CD)=0">　</xsl:when>
											<xsl:otherwise>
												<xsl:choose>
													<xsl:when test="./kyo:WCE00030/kyo:kubun_CD='1'">新生命保険料控除制度</xsl:when>
													<xsl:when test="./kyo:WCE00030/kyo:kubun_CD='2'">旧生命保険料控除制度</xsl:when>
													<xsl:when test="./kyo:WCE00030/kyo:kubun_CD='3'">新生命保険料控除制度及び旧生命保険料控除制度</xsl:when>
													<xsl:otherwise>　</xsl:otherwise>
												</xsl:choose>
											</xsl:otherwise>
										</xsl:choose>
									</td>
								</tr>
								<tr height="15px">
									<td colspan="20" align="left">証券番号</td>
									<td colspan="80" align="left">
										<xsl:choose>
											<xsl:when test="count(./kyo:WCE00040)=0">　</xsl:when>
											<xsl:when test="./kyo:WCE00040=''">　</xsl:when>
											<xsl:otherwise><xsl:apply-templates select="./kyo:WCE00040"/></xsl:otherwise>
										</xsl:choose>
									</td>
								</tr>
								<tr height="15px">
									<td colspan="20" align="left">保険種類</td>
									<td colspan="80" align="left">
										<xsl:choose>
											<xsl:when test="count(./kyo:WCE00050)=0">　</xsl:when>
											<xsl:when test="./kyo:WCE00050=''">　</xsl:when>
											<xsl:otherwise><xsl:apply-templates select="./kyo:WCE00050"/></xsl:otherwise>
										</xsl:choose>
									</td>
								</tr>
								<tr height="15px">
									<td colspan="20" align="left">契約日</td>
									<xsl:choose>
										<xsl:when test="count(./kyo:WCE00060)=0">
											<td colspan="5" align="right" class="line0">　年</td>
											<td colspan="4" align="right" class="line6">　月</td>
											<td colspan="4" align="right" class="line6">　日</td>
										</xsl:when>
										<xsl:otherwise>
											<td colspan="5" align="right" class="line0">
												<xsl:choose>
													<xsl:when test="string-length(./kyo:WCE00060/gen:yyyy)&gt;4">
														<xsl:choose>
															<xsl:when test="substring(./kyo:WCE00060/gen:yyyy,1,1)='-'">
																<xsl:value-of select="format-number(substring(./kyo:WCE00060/gen:yyyy,(string-length(./kyo:WCE00060/gen:yyyy)-3),4),'-0000')"/>年
															</xsl:when>
															<xsl:otherwise>
																<xsl:value-of select="format-number(substring(./kyo:WCE00060/gen:yyyy,(string-length(./kyo:WCE00060/gen:yyyy)-3),4),'0000')"/>年
															</xsl:otherwise>
														</xsl:choose>
													</xsl:when>
													<xsl:when test="./kyo:WCE00060/gen:yyyy=''">　年</xsl:when>
													<xsl:otherwise>
														<xsl:value-of select="format-number(./kyo:WCE00060/gen:yyyy,'###0')"/>年
													</xsl:otherwise>
												</xsl:choose>
											</td>
											<td colspan="4" align="right" class="line6">
												<xsl:choose>
													<xsl:when test="string-length(./kyo:WCE00060/gen:mm)&gt;2">
														<xsl:choose>
															<xsl:when test="substring(./kyo:WCE00060/gen:mm,1,1)='-'">
																<xsl:value-of select="format-number(substring(./kyo:WCE00060/gen:mm,(string-length(./kyo:WCE00060/gen:mm)-1),2),'-00')"/>月
															</xsl:when>
															<xsl:otherwise>
																<xsl:value-of select="format-number(substring(./kyo:WCE00060/gen:mm,(string-length(./kyo:WCE00060/gen:mm)-1),2),'00')"/>月
															</xsl:otherwise>
														</xsl:choose>
													</xsl:when>
													<xsl:when test="./kyo:WCE00060/gen:mm=''">　月</xsl:when>
													<xsl:otherwise>
														<xsl:value-of select="format-number(./kyo:WCE00060/gen:mm,'#0')"/>月
													</xsl:otherwise>
												</xsl:choose>
											</td>
											<td colspan="4" align="right" class="line6">
												<xsl:choose>
													<xsl:when test="string-length(./kyo:WCE00060/gen:dd)&gt;2">
														<xsl:choose>
															<xsl:when test="substring(./kyo:WCE00060/gen:dd,1,1)='-'">
																<xsl:value-of select="format-number(substring(./kyo:WCE00060/gen:dd,(string-length(./kyo:WCE00060/gen:dd)-1),2),'-00')"/>日
															</xsl:when>
															<xsl:otherwise>
																<xsl:value-of select="format-number(substring(./kyo:WCE00060/gen:dd,(string-length(./kyo:WCE00060/gen:dd)-1),2),'00')"/>日
															</xsl:otherwise>
														</xsl:choose>
													</xsl:when>
													<xsl:when test="./kyo:WCE00060/gen:dd=''">　日</xsl:when>
													<xsl:otherwise>
														<xsl:value-of select="format-number(./kyo:WCE00060/gen:dd,'#0')"/>日
													</xsl:otherwise>
												</xsl:choose>
											</td>
										</xsl:otherwise>
									</xsl:choose>
									<td colspan="67" class="line1"/>
								</tr>
								<tr height="26px">
									<td colspan="20" align="left">契約者</td>
									<td colspan="77" align="left" class="line0">
										<xsl:choose>
											<xsl:when test="count(../kyo:WCD00000)=0">　</xsl:when>
											<xsl:when test="../kyo:WCD00000=''">　</xsl:when>
											<xsl:otherwise><xsl:apply-templates select="../kyo:WCD00000"/></xsl:otherwise>
										</xsl:choose>
									</td>
									<td colspan="3" align="center" class="line1">様</td>
								</tr>
								<tr height="15px">
									<td colspan="20" align="left">事業所（団体）コード</td>
									<td colspan="80" align="left">
										<xsl:choose>
											<xsl:when test="count(./kyo:WCE00070)=0">　</xsl:when>
											<xsl:when test="./kyo:WCE00070=''">　</xsl:when>
											<xsl:otherwise><xsl:apply-templates select="./kyo:WCE00070"/></xsl:otherwise>
										</xsl:choose>
									</td>
								</tr>
								<tr height="26px">
									<td colspan="20" align="left">被保険者</td>
									<td colspan="77" align="left" class="line0">
										<xsl:choose>
											<xsl:when test="count(./kyo:WCE00080)=0">　</xsl:when>
											<xsl:when test="./kyo:WCE00080=''">　</xsl:when>
											<xsl:otherwise><xsl:apply-templates select="./kyo:WCE00080"/></xsl:otherwise>
										</xsl:choose>
									</td>
									<td colspan="3" align="center" class="line1">様</td>
								</tr>
								<tr height="15px">
									<td colspan="20" align="left">被保険者番号</td>
									<td colspan="80" align="left">
										<xsl:choose>
											<xsl:when test="count(./kyo:WCE00090)=0">　</xsl:when>
											<xsl:when test="./kyo:WCE00090=''">　</xsl:when>
											<xsl:otherwise><xsl:apply-templates select="./kyo:WCE00090"/></xsl:otherwise>
										</xsl:choose>
									</td>
								</tr>
								<tr height="15px">
									<td colspan="20" align="left">払込方法</td>
									<td colspan="80" align="left">
										<xsl:choose>
											<xsl:when test="count(./kyo:WCE00100)=0">　</xsl:when>
											<xsl:when test="./kyo:WCE00100=''">　</xsl:when>
											<xsl:otherwise><xsl:apply-templates select="./kyo:WCE00100"/></xsl:otherwise>
										</xsl:choose>
									</td>
								</tr>
								<tr height="26px">
									<td colspan="20" align="left">受取人</td>
									<td colspan="77" align="left" class="line0">
										<xsl:choose>
											<xsl:when test="count(./kyo:WCE00110)=0">　</xsl:when>
											<xsl:when test="./kyo:WCE00110=''">　</xsl:when>
											<xsl:otherwise><xsl:apply-templates select="./kyo:WCE00110"/></xsl:otherwise>
										</xsl:choose>
									</td>
									<td colspan="3" align="center" class="line1">様</td>
								</tr>
							</table>
							<br/>
							<table border="0">
								<tr height="14">
									<td colspan="100" align="left">一般生命保険、または介護医療保険</td>
								</tr>
							</table>
							<table border="2" cellspacing="0" style="table-layout: fixed;">
								<tr height="15px">
									<td colspan="20" align="left">保険期間</td>
									<xsl:choose>
										<xsl:when test="./kyo:WCE00120='999'"><td colspan="80" align="left">終身</td></xsl:when>
										<xsl:otherwise>
											<td colspan="3" align="right" class="line0">
												<xsl:choose>
													<xsl:when test="count(./kyo:WCE00120)=0">　</xsl:when>
													<xsl:when test="./kyo:WCE00120=''">　</xsl:when>
													<xsl:when test="string-length(./kyo:WCE00120)&gt;3">　</xsl:when>
													<xsl:when test="substring(./kyo:WCE00120,1,1)='-'">　</xsl:when>
													<xsl:otherwise><xsl:value-of select="format-number(./kyo:WCE00120,'##0')"/></xsl:otherwise>
												</xsl:choose></td><td colspan="77" align="left" class="line1">　年間</td>
										</xsl:otherwise>
									</xsl:choose>
								</tr>
							</table>
							<br/>
							<table border="0">
								<tr height="14">
									<td colspan="100" align="left">個人年金保険</td>
								</tr>
							</table>
							<table border="2" cellspacing="0" style="table-layout: fixed;">
								<tr height="15px">
									<td colspan="20" align="left">受取人生年月日</td>
									<xsl:choose>
										<xsl:when test="count(./kyo:WCE00130/kyo:WCE00140)=0">
											<td colspan="5" align="right" class="line0">　年</td>
											<td colspan="4" align="right" class="line6">　月</td>
											<td colspan="4" align="right" class="line6">　日</td>
										</xsl:when>
										<xsl:otherwise>
											<td colspan="5" align="right" class="line0">
												<xsl:choose>
													<xsl:when test="string-length(./kyo:WCE00130/kyo:WCE00140/gen:yyyy)&gt;4">
														<xsl:choose>
															<xsl:when test="substring(./kyo:WCE00130/kyo:WCE00140/gen:yyyy,1,1)='-'">
																<xsl:value-of select="format-number(substring(./kyo:WCE00130/kyo:WCE00140/gen:yyyy,(string-length(./kyo:WCE00130/kyo:WCE00140/gen:yyyy)-3),4),'-0000')"/>年
															</xsl:when>
															<xsl:otherwise>
																<xsl:value-of select="format-number(substring(./kyo:WCE00130/kyo:WCE00140/gen:yyyy,(string-length(./kyo:WCE00130/kyo:WCE00140/gen:yyyy)-3),4),'0000')"/>年
															</xsl:otherwise>
														</xsl:choose>
													</xsl:when>
													<xsl:when test="./kyo:WCE00130/kyo:WCE00140/gen:yyyy=''">　年</xsl:when>
													<xsl:otherwise>
														<xsl:value-of select="format-number(./kyo:WCE00130/kyo:WCE00140/gen:yyyy,'###0')"/>年
													</xsl:otherwise>
												</xsl:choose>
											</td>
											<td colspan="4" align="right" class="line6">
												<xsl:choose>
													<xsl:when test="string-length(./kyo:WCE00130/kyo:WCE00140/gen:mm)&gt;2">
														<xsl:choose>
															<xsl:when test="substring(./kyo:WCE00130/kyo:WCE00140/gen:mm,1,1)='-'">
																<xsl:value-of select="format-number(substring(./kyo:WCE00130/kyo:WCE00140/gen:mm,(string-length(./kyo:WCE00130/kyo:WCE00140/gen:mm)-1),2),'-00')"/>月
															</xsl:when>
															<xsl:otherwise>
																<xsl:value-of select="format-number(substring(./kyo:WCE00130/kyo:WCE00140/gen:mm,(string-length(./kyo:WCE00130/kyo:WCE00140/gen:mm)-1),2),'00')"/>月
															</xsl:otherwise>
														</xsl:choose>
													</xsl:when>
													<xsl:when test="./kyo:WCE00130/kyo:WCE00140/gen:mm=''">　月</xsl:when>
													<xsl:otherwise>
														<xsl:value-of select="format-number(./kyo:WCE00130/kyo:WCE00140/gen:mm,'#0')"/>月
													</xsl:otherwise>
												</xsl:choose>
											</td>
											<td colspan="4" align="right" class="line6">
												<xsl:choose>
													<xsl:when test="string-length(./kyo:WCE00130/kyo:WCE00140/gen:dd)&gt;2">
														<xsl:choose>
															<xsl:when test="substring(./kyo:WCE00130/kyo:WCE00140/gen:dd,1,1)='-'">
																<xsl:value-of select="format-number(substring(./kyo:WCE00130/kyo:WCE00140/gen:dd,(string-length(./kyo:WCE00130/kyo:WCE00140/gen:dd)-1),2),'-00')"/>日
															</xsl:when>
															<xsl:otherwise>
																<xsl:value-of select="format-number(substring(./kyo:WCE00130/kyo:WCE00140/gen:dd,(string-length(./kyo:WCE00130/kyo:WCE00140/gen:dd)-1),2),'00')"/>日
															</xsl:otherwise>
														</xsl:choose>
													</xsl:when>
													<xsl:when test="./kyo:WCE00130/kyo:WCE00140/gen:dd=''">　日</xsl:when>
													<xsl:otherwise>
														<xsl:value-of select="format-number(./kyo:WCE00130/kyo:WCE00140/gen:dd,'#0')"/>日
													</xsl:otherwise>
												</xsl:choose>
											</td>
										</xsl:otherwise>
									</xsl:choose>
									<td colspan="17" class="line1"/>
									<td colspan="20" align="left">保険料払込期間</td>
									<xsl:choose>
										<xsl:when test="./kyo:WCE00130/kyo:WCE00150='999'"><td colspan="30" align="left">終身</td></xsl:when>
										<xsl:otherwise>
											<td colspan="3" align="right" class="line0">
												<xsl:choose>
													<xsl:when test="count(./kyo:WCE00130/kyo:WCE00150)=0">　</xsl:when>
													<xsl:when test="./kyo:WCE00130/kyo:WCE00150=''">　</xsl:when>
													<xsl:when test="string-length(./kyo:WCE00130/kyo:WCE00150)&gt;3">　</xsl:when>
													<xsl:when test="substring(./kyo:WCE00130/kyo:WCE00150,1,1)='-'">　</xsl:when>
													<xsl:otherwise><xsl:value-of select="format-number(./kyo:WCE00130/kyo:WCE00150,'##0')"/></xsl:otherwise>
												</xsl:choose></td><td colspan="27" align="left" class="line1">　年間</td>
										</xsl:otherwise>
									</xsl:choose>
								</tr>
								<tr height="15px">
									<td colspan="20" align="left">年金種類</td>
									<td colspan="80" align="left">
										<xsl:choose>
											<xsl:when test="count(./kyo:WCE00130/kyo:WCE00160)=0">　</xsl:when>
											<xsl:when test="./kyo:WCE00130/kyo:WCE00160=''">　</xsl:when>
											<xsl:otherwise><xsl:apply-templates select="./kyo:WCE00130/kyo:WCE00160"/></xsl:otherwise>
										</xsl:choose>
									</td>
								</tr>
								<tr height="15px">
									<td colspan="20" align="left">年金支払期間</td>
									<xsl:choose>
										<xsl:when test="./kyo:WCE00130/kyo:WCE00170='999'"><td colspan="30" align="left">終身</td></xsl:when>
										<xsl:otherwise>
											<td colspan="3" align="right" class="line0">
												<xsl:choose>
													<xsl:when test="count(./kyo:WCE00130/kyo:WCE00170)=0">　</xsl:when>
													<xsl:when test="./kyo:WCE00130/kyo:WCE00170=''">　</xsl:when>
													<xsl:when test="string-length(./kyo:WCE00130/kyo:WCE00170)&gt;3">　</xsl:when>
													<xsl:when test="substring(./kyo:WCE00130/kyo:WCE00170,1,1)='-'">　</xsl:when>
													<xsl:otherwise><xsl:value-of select="format-number(./kyo:WCE00130/kyo:WCE00170,'##0')"/></xsl:otherwise>
												</xsl:choose></td><td colspan="27" align="left" class="line1">　年間</td>
										</xsl:otherwise>
									</xsl:choose>
									<td colspan="20" align="left">年金支払開始日</td>
									<xsl:choose>
										<xsl:when test="count(./kyo:WCE00130/kyo:WCE00180)=0">
											<td colspan="5" align="right" class="line0">　年</td>
											<td colspan="4" align="right" class="line6">　月</td>
											<td colspan="4" align="right" class="line6">　日</td>
										</xsl:when>
										<xsl:otherwise>
											<td colspan="5" align="right" class="line0">
												<xsl:choose>
													<xsl:when test="string-length(./kyo:WCE00130/kyo:WCE00180/gen:yyyy)&gt;4">
														<xsl:choose>
															<xsl:when test="substring(./kyo:WCE00130/kyo:WCE00180/gen:yyyy,1,1)='-'">
																<xsl:value-of select="format-number(substring(./kyo:WCE00130/kyo:WCE00180/gen:yyyy,(string-length(./kyo:WCE00130/kyo:WCE00180/gen:yyyy)-3),4),'-0000')"/>年
															</xsl:when>
															<xsl:otherwise>
																<xsl:value-of select="format-number(substring(./kyo:WCE00130/kyo:WCE00180/gen:yyyy,(string-length(./kyo:WCE00130/kyo:WCE00180/gen:yyyy)-3),4),'0000')"/>年
															</xsl:otherwise>
														</xsl:choose>
													</xsl:when>
													<xsl:when test="./kyo:WCE00130/kyo:WCE00180/gen:yyyy=''">　年</xsl:when>
													<xsl:otherwise>
														<xsl:value-of select="format-number(./kyo:WCE00130/kyo:WCE00180/gen:yyyy,'###0')"/>年
													</xsl:otherwise>
												</xsl:choose>
											</td>
											<td colspan="4" align="right" class="line6">
												<xsl:choose>
													<xsl:when test="string-length(./kyo:WCE00130/kyo:WCE00180/gen:mm)&gt;2">
														<xsl:choose>
															<xsl:when test="substring(./kyo:WCE00130/kyo:WCE00180/gen:mm,1,1)='-'">
																<xsl:value-of select="format-number(substring(./kyo:WCE00130/kyo:WCE00180/gen:mm,(string-length(./kyo:WCE00130/kyo:WCE00180/gen:mm)-1),2),'-00')"/>月
															</xsl:when>
															<xsl:otherwise>
																<xsl:value-of select="format-number(substring(./kyo:WCE00130/kyo:WCE00180/gen:mm,(string-length(./kyo:WCE00130/kyo:WCE00180/gen:mm)-1),2),'00')"/>月
															</xsl:otherwise>
														</xsl:choose>
													</xsl:when>
													<xsl:when test="./kyo:WCE00130/kyo:WCE00180/gen:mm=''">　月</xsl:when>
													<xsl:otherwise>
														<xsl:value-of select="format-number(./kyo:WCE00130/kyo:WCE00180/gen:mm,'#0')"/>月
													</xsl:otherwise>
												</xsl:choose>
											</td>
											<td colspan="4" align="right" class="line6">
												<xsl:choose>
													<xsl:when test="string-length(./kyo:WCE00130/kyo:WCE00180/gen:dd)&gt;2">
														<xsl:choose>
															<xsl:when test="substring(./kyo:WCE00130/kyo:WCE00180/gen:dd,1,1)='-'">
																<xsl:value-of select="format-number(substring(./kyo:WCE00130/kyo:WCE00180/gen:dd,(string-length(./kyo:WCE00130/kyo:WCE00180/gen:dd)-1),2),'-00')"/>日
															</xsl:when>
															<xsl:otherwise>
																<xsl:value-of select="format-number(substring(./kyo:WCE00130/kyo:WCE00180/gen:dd,(string-length(./kyo:WCE00130/kyo:WCE00180/gen:dd)-1),2),'00')"/>日
															</xsl:otherwise>
														</xsl:choose>
													</xsl:when>
													<xsl:when test="./kyo:WCE00130/kyo:WCE00180/gen:dd=''">　日</xsl:when>
													<xsl:otherwise>
														<xsl:value-of select="format-number(./kyo:WCE00130/kyo:WCE00180/gen:dd,'#0')"/>日
													</xsl:otherwise>
												</xsl:choose>
											</td>
										</xsl:otherwise>
									</xsl:choose>
									<td colspan="17" class="line1"/>
								</tr>
							</table>
							<br/>
							<table border="0">
								<tr height="15">
									<td colspan="100" align="left">
										<xsl:choose>
											<xsl:when test="(count(./kyo:WCE00190/kyo:WCE00200)=0 or (./kyo:WCE00190/kyo:WCE00200/gen:yyyy='' and ./kyo:WCE00190/kyo:WCE00200/gen:mm='')) and (count(./kyo:WCE00190/kyo:WCE00210)=0 or (./kyo:WCE00190/kyo:WCE00210/gen:yyyy='' and ./kyo:WCE00190/kyo:WCE00210/gen:mm=''))">証明日時点の保険料払込額を以下のとおり証明します。</xsl:when>
											<xsl:when test="count(./kyo:WCE00190/kyo:WCE00200)=0 or (./kyo:WCE00190/kyo:WCE00200/gen:yyyy='' and ./kyo:WCE00190/kyo:WCE00200/gen:mm='')">
												<xsl:choose>
													<xsl:when test="string-length(./kyo:WCE00190/kyo:WCE00210/gen:yyyy)&gt;4">
														<xsl:choose>
															<xsl:when test="substring(./kyo:WCE00190/kyo:WCE00210/gen:yyyy,1,1)='-'">
																<xsl:value-of select="format-number(substring(./kyo:WCE00190/kyo:WCE00210/gen:yyyy,(string-length(./kyo:WCE00190/kyo:WCE00210/gen:yyyy)-3),4),'-0000')"/>
															</xsl:when>
															<xsl:otherwise>
																<xsl:value-of select="format-number(substring(./kyo:WCE00190/kyo:WCE00210/gen:yyyy,(string-length(./kyo:WCE00190/kyo:WCE00210/gen:yyyy)-3),4),'0000')"/>
															</xsl:otherwise>
														</xsl:choose>
													</xsl:when>
													<xsl:when test="./kyo:WCE00190/kyo:WCE00210/gen:yyyy=''">　　</xsl:when>
													<xsl:otherwise>
														<xsl:value-of select="format-number(./kyo:WCE00190/kyo:WCE00210/gen:yyyy,'###0')"/>
													</xsl:otherwise>
												</xsl:choose> 年
												<xsl:choose>
													<xsl:when test="string-length(./kyo:WCE00190/kyo:WCE00210/gen:mm)&gt;2">
														<xsl:choose>
															<xsl:when test="substring(./kyo:WCE00190/kyo:WCE00210/gen:mm,1,1)='-'">
																<xsl:value-of select="format-number(substring(./kyo:WCE00190/kyo:WCE00210/gen:mm,(string-length(./kyo:WCE00190/kyo:WCE00210/gen:mm)-1),2),'-00')"/>
															</xsl:when>
															<xsl:otherwise>
																<xsl:value-of select="format-number(substring(./kyo:WCE00190/kyo:WCE00210/gen:mm,(string-length(./kyo:WCE00190/kyo:WCE00210/gen:mm)-1),2),'00')"/>
															</xsl:otherwise>
														</xsl:choose>
													</xsl:when>
													<xsl:when test="./kyo:WCE00190/kyo:WCE00210/gen:mm=''">　</xsl:when>
													<xsl:otherwise>
														<xsl:value-of select="format-number(./kyo:WCE00190/kyo:WCE00210/gen:mm,'#0')"/>
													</xsl:otherwise>
												</xsl:choose> 月までの保険料払込額を以下のとおり証明します。
											</xsl:when>
											<xsl:otherwise>
												<xsl:choose>
													<xsl:when test="string-length(./kyo:WCE00190/kyo:WCE00200/gen:yyyy)&gt;4">
														<xsl:choose>
															<xsl:when test="substring(./kyo:WCE00190/kyo:WCE00200/gen:yyyy,1,1)='-'">
																<xsl:value-of select="format-number(substring(./kyo:WCE00190/kyo:WCE00200/gen:yyyy,(string-length(./kyo:WCE00190/kyo:WCE00200/gen:yyyy)-3),4),'-0000')"/>
															</xsl:when>
															<xsl:otherwise>
																<xsl:value-of select="format-number(substring(./kyo:WCE00190/kyo:WCE00200/gen:yyyy,(string-length(./kyo:WCE00190/kyo:WCE00200/gen:yyyy)-3),4),'0000')"/>
															</xsl:otherwise>
														</xsl:choose>
													</xsl:when>
													<xsl:when test="./kyo:WCE00190/kyo:WCE00200/gen:yyyy=''">　　</xsl:when>
													<xsl:otherwise>
														<xsl:value-of select="format-number(./kyo:WCE00190/kyo:WCE00200/gen:yyyy,'###0')"/>
													</xsl:otherwise>
												</xsl:choose> 年
												<xsl:choose>
													<xsl:when test="string-length(./kyo:WCE00190/kyo:WCE00200/gen:mm)&gt;2">
														<xsl:choose>
															<xsl:when test="substring(./kyo:WCE00190/kyo:WCE00200/gen:mm,1,1)='-'">
																<xsl:value-of select="format-number(substring(./kyo:WCE00190/kyo:WCE00200/gen:mm,(string-length(./kyo:WCE00190/kyo:WCE00200/gen:mm)-1),2),'-00')"/>
															</xsl:when>
															<xsl:otherwise>
																<xsl:value-of select="format-number(substring(./kyo:WCE00190/kyo:WCE00200/gen:mm,(string-length(./kyo:WCE00190/kyo:WCE00200/gen:mm)-1),2),'00')"/>
															</xsl:otherwise>
														</xsl:choose>
													</xsl:when>
													<xsl:when test="./kyo:WCE00190/kyo:WCE00200/gen:mm=''">　</xsl:when>
													<xsl:otherwise>
														<xsl:value-of select="format-number(./kyo:WCE00190/kyo:WCE00200/gen:mm,'#0')"/>
													</xsl:otherwise>
												</xsl:choose> 月　～　
												<xsl:choose>
													<xsl:when test="count(./kyo:WCE00190/kyo:WCE00210)=0">　　年　　月までの保険料払込額を以下のとおり証明します。</xsl:when>
													<xsl:otherwise>
														<xsl:choose>
															<xsl:when test="string-length(./kyo:WCE00190/kyo:WCE00210/gen:yyyy)&gt;4">
																<xsl:choose>
																	<xsl:when test="substring(./kyo:WCE00190/kyo:WCE00210/gen:yyyy,1,1)='-'">
																		<xsl:value-of select="format-number(substring(./kyo:WCE00190/kyo:WCE00210/gen:yyyy,(string-length(./kyo:WCE00190/kyo:WCE00210/gen:yyyy)-3),4),'-0000')"/>
																	</xsl:when>
																	<xsl:otherwise>
																		<xsl:value-of select="format-number(substring(./kyo:WCE00190/kyo:WCE00210/gen:yyyy,(string-length(./kyo:WCE00190/kyo:WCE00210/gen:yyyy)-3),4),'0000')"/>
																	</xsl:otherwise>
																</xsl:choose>
															</xsl:when>
															<xsl:when test="./kyo:WCE00190/kyo:WCE00210/gen:yyyy=''">　　</xsl:when>
															<xsl:otherwise>
																<xsl:value-of select="format-number(./kyo:WCE00190/kyo:WCE00210/gen:yyyy,'###0')"/>
															</xsl:otherwise>
														</xsl:choose> 年
														<xsl:choose>
															<xsl:when test="string-length(./kyo:WCE00190/kyo:WCE00210/gen:mm)&gt;2">
																<xsl:choose>
																	<xsl:when test="substring(./kyo:WCE00190/kyo:WCE00210/gen:mm,1,1)='-'">
																		<xsl:value-of select="format-number(substring(./kyo:WCE00190/kyo:WCE00210/gen:mm,(string-length(./kyo:WCE00190/kyo:WCE00210/gen:mm)-1),2),'-00')"/>
																	</xsl:when>
																	<xsl:otherwise>
																		<xsl:value-of select="format-number(substring(./kyo:WCE00190/kyo:WCE00210/gen:mm,(string-length(./kyo:WCE00190/kyo:WCE00210/gen:mm)-1),2),'00')"/>
																	</xsl:otherwise>
																</xsl:choose>
															</xsl:when>
															<xsl:when test="./kyo:WCE00190/kyo:WCE00210/gen:mm=''">　</xsl:when>
															<xsl:otherwise>
																<xsl:value-of select="format-number(./kyo:WCE00190/kyo:WCE00210/gen:mm,'#0')"/>
															</xsl:otherwise>
														</xsl:choose> 月までの保険料払込額を以下のとおり証明します。
													</xsl:otherwise>
												</xsl:choose>
											</xsl:otherwise>
										</xsl:choose>
									</td>
								</tr>
							</table>
							<table border="2" cellspacing="0" style="table-layout: fixed;">
								<tr height="15px">
									<td rowspan="4" colspan="5" align="center" class="line4">旧<br/>制<br/>度</td>
									<td rowspan="2" colspan="11" align="center">一般</td>
									<td colspan="28" align="left" class="line2">一般生命保険料(Ａ)</td>
									<td colspan="28" align="left" class="line2">配当金(相当額)(Ｂ)</td>
									<td colspan="28" align="left" class="line2">一般証明額(Ａ－Ｂ)</td>
								</tr>
								<tr height="15px">
									<td colspan="28" align="right" class="line3">
										<xsl:choose>
											<xsl:when test="count(./kyo:WCE00190/kyo:WCE00220/kyo:WCE00230/kyo:WCE00240)=0">　</xsl:when>
											<xsl:when test="./kyo:WCE00190/kyo:WCE00220/kyo:WCE00230/kyo:WCE00240=''">　</xsl:when>
											<xsl:otherwise>
												<xsl:choose>
													<xsl:when test="string-length(./kyo:WCE00190/kyo:WCE00220/kyo:WCE00230/kyo:WCE00240)&gt;15">
														<xsl:choose>
															<xsl:when test="substring(./kyo:WCE00190/kyo:WCE00220/kyo:WCE00230/kyo:WCE00240,1,1)='-'">
																<xsl:value-of select="format-number(substring(./kyo:WCE00190/kyo:WCE00220/kyo:WCE00230/kyo:WCE00240,(string-length(./kyo:WCE00190/kyo:WCE00220/kyo:WCE00230/kyo:WCE00240)-14),15),'-000,000,000,000,000')"/>
															</xsl:when>
															<xsl:otherwise>
																<xsl:value-of select="format-number(substring(./kyo:WCE00190/kyo:WCE00220/kyo:WCE00230/kyo:WCE00240,(string-length(./kyo:WCE00190/kyo:WCE00220/kyo:WCE00230/kyo:WCE00240)-14),15),'000,000,000,000,000')"/>
															</xsl:otherwise>
														</xsl:choose>
													</xsl:when>
													<xsl:otherwise>
														<xsl:value-of select="format-number(./kyo:WCE00190/kyo:WCE00220/kyo:WCE00230/kyo:WCE00240,'###,###,###,###,##0')"/>
													</xsl:otherwise>
												</xsl:choose>
											</xsl:otherwise>
										</xsl:choose>円</td>
									<td colspan="28" align="right" class="line3">
										<xsl:choose>
											<xsl:when test="count(./kyo:WCE00190/kyo:WCE00220/kyo:WCE00230/kyo:WCE00250)=0">　</xsl:when>
											<xsl:when test="./kyo:WCE00190/kyo:WCE00220/kyo:WCE00230/kyo:WCE00250=''">　</xsl:when>
											<xsl:otherwise>
												<xsl:choose>
													<xsl:when test="string-length(./kyo:WCE00190/kyo:WCE00220/kyo:WCE00230/kyo:WCE00250)&gt;15">
														<xsl:choose>
															<xsl:when test="substring(./kyo:WCE00190/kyo:WCE00220/kyo:WCE00230/kyo:WCE00250,1,1)='-'">
																<xsl:value-of select="format-number(substring(./kyo:WCE00190/kyo:WCE00220/kyo:WCE00230/kyo:WCE00250,(string-length(./kyo:WCE00190/kyo:WCE00220/kyo:WCE00230/kyo:WCE00250)-14),15),'-000,000,000,000,000')"/>
															</xsl:when>
															<xsl:otherwise>
																<xsl:value-of select="format-number(substring(./kyo:WCE00190/kyo:WCE00220/kyo:WCE00230/kyo:WCE00250,(string-length(./kyo:WCE00190/kyo:WCE00220/kyo:WCE00230/kyo:WCE00250)-14),15),'000,000,000,000,000')"/>
															</xsl:otherwise>
														</xsl:choose>
													</xsl:when>
													<xsl:otherwise>
														<xsl:value-of select="format-number(./kyo:WCE00190/kyo:WCE00220/kyo:WCE00230/kyo:WCE00250,'###,###,###,###,##0')"/>
													</xsl:otherwise>
												</xsl:choose>
											</xsl:otherwise>
										</xsl:choose>円</td>
									<td colspan="28" align="right" class="line3">
										<xsl:choose>
											<xsl:when test="count(./kyo:WCE00190/kyo:WCE00220/kyo:WCE00230/kyo:WCE00260)=0">　</xsl:when>
											<xsl:when test="./kyo:WCE00190/kyo:WCE00220/kyo:WCE00230/kyo:WCE00260=''">　</xsl:when>
											<xsl:otherwise>
												<xsl:choose>
													<xsl:when test="string-length(./kyo:WCE00190/kyo:WCE00220/kyo:WCE00230/kyo:WCE00260)&gt;15">
														<xsl:choose>
															<xsl:when test="substring(./kyo:WCE00190/kyo:WCE00220/kyo:WCE00230/kyo:WCE00260,1,1)='-'">
																<xsl:value-of select="format-number(substring(./kyo:WCE00190/kyo:WCE00220/kyo:WCE00230/kyo:WCE00260,(string-length(./kyo:WCE00190/kyo:WCE00220/kyo:WCE00230/kyo:WCE00260)-14),15),'-000,000,000,000,000')"/>
															</xsl:when>
															<xsl:otherwise>
																<xsl:value-of select="format-number(substring(./kyo:WCE00190/kyo:WCE00220/kyo:WCE00230/kyo:WCE00260,(string-length(./kyo:WCE00190/kyo:WCE00220/kyo:WCE00230/kyo:WCE00260)-14),15),'000,000,000,000,000')"/>
															</xsl:otherwise>
														</xsl:choose>
													</xsl:when>
													<xsl:otherwise>
														<xsl:value-of select="format-number(./kyo:WCE00190/kyo:WCE00220/kyo:WCE00230/kyo:WCE00260,'###,###,###,###,##0')"/>
													</xsl:otherwise>
												</xsl:choose>
											</xsl:otherwise>
										</xsl:choose>円</td>
								</tr>
								<tr height="15px">
									<td rowspan="2" colspan="11" align="center" class="line4">年金</td>
									<td colspan="28" align="left" class="line2">個人年金保険料(Ｃ)</td>
									<td colspan="28" align="left" class="line2">配当金(相当額)(Ｄ)</td>
									<td colspan="28" align="left" class="line2">個人年金証明額(Ｃ－Ｄ)</td>
								</tr>
								<tr height="15px">
									<td colspan="28" align="right" class="line5">
										<xsl:choose>
											<xsl:when test="count(./kyo:WCE00190/kyo:WCE00220/kyo:WCE00270/kyo:WCE00280)=0">　</xsl:when>
											<xsl:when test="./kyo:WCE00190/kyo:WCE00220/kyo:WCE00270/kyo:WCE00280=''">　</xsl:when>
											<xsl:otherwise>
												<xsl:choose>
													<xsl:when test="string-length(./kyo:WCE00190/kyo:WCE00220/kyo:WCE00270/kyo:WCE00280)&gt;15">
														<xsl:choose>
															<xsl:when test="substring(./kyo:WCE00190/kyo:WCE00220/kyo:WCE00270/kyo:WCE00280,1,1)='-'">
																<xsl:value-of select="format-number(substring(./kyo:WCE00190/kyo:WCE00220/kyo:WCE00270/kyo:WCE00280,(string-length(./kyo:WCE00190/kyo:WCE00220/kyo:WCE00270/kyo:WCE00280)-14),15),'-000,000,000,000,000')"/>
															</xsl:when>
															<xsl:otherwise>
																<xsl:value-of select="format-number(substring(./kyo:WCE00190/kyo:WCE00220/kyo:WCE00270/kyo:WCE00280,(string-length(./kyo:WCE00190/kyo:WCE00220/kyo:WCE00270/kyo:WCE00280)-14),15),'000,000,000,000,000')"/>
															</xsl:otherwise>
														</xsl:choose>
													</xsl:when>
													<xsl:otherwise>
														<xsl:value-of select="format-number(./kyo:WCE00190/kyo:WCE00220/kyo:WCE00270/kyo:WCE00280,'###,###,###,###,##0')"/>
													</xsl:otherwise>
												</xsl:choose>
											</xsl:otherwise>
										</xsl:choose>円</td>
									<td colspan="28" align="right" class="line5">
										<xsl:choose>
											<xsl:when test="count(./kyo:WCE00190/kyo:WCE00220/kyo:WCE00270/kyo:WCE00290)=0">　</xsl:when>
											<xsl:when test="./kyo:WCE00190/kyo:WCE00220/kyo:WCE00270/kyo:WCE00290=''">　</xsl:when>
											<xsl:otherwise>
												<xsl:choose>
													<xsl:when test="string-length(./kyo:WCE00190/kyo:WCE00220/kyo:WCE00270/kyo:WCE00290)&gt;15">
														<xsl:choose>
															<xsl:when test="substring(./kyo:WCE00190/kyo:WCE00220/kyo:WCE00270/kyo:WCE00290,1,1)='-'">
																<xsl:value-of select="format-number(substring(./kyo:WCE00190/kyo:WCE00220/kyo:WCE00270/kyo:WCE00290,(string-length(./kyo:WCE00190/kyo:WCE00220/kyo:WCE00270/kyo:WCE00290)-14),15),'-000,000,000,000,000')"/>
															</xsl:when>
															<xsl:otherwise>
																<xsl:value-of select="format-number(substring(./kyo:WCE00190/kyo:WCE00220/kyo:WCE00270/kyo:WCE00290,(string-length(./kyo:WCE00190/kyo:WCE00220/kyo:WCE00270/kyo:WCE00290)-14),15),'000,000,000,000,000')"/>
															</xsl:otherwise>
														</xsl:choose>
													</xsl:when>
													<xsl:otherwise>
														<xsl:value-of select="format-number(./kyo:WCE00190/kyo:WCE00220/kyo:WCE00270/kyo:WCE00290,'###,###,###,###,##0')"/>
													</xsl:otherwise>
												</xsl:choose>
											</xsl:otherwise>
										</xsl:choose>円</td>
									<td colspan="28" align="right" class="line5">
										<xsl:choose>
											<xsl:when test="count(./kyo:WCE00190/kyo:WCE00220/kyo:WCE00270/kyo:WCE00300)=0">　</xsl:when>
											<xsl:when test="./kyo:WCE00190/kyo:WCE00220/kyo:WCE00270/kyo:WCE00300=''">　</xsl:when>
											<xsl:otherwise>
												<xsl:choose>
													<xsl:when test="string-length(./kyo:WCE00190/kyo:WCE00220/kyo:WCE00270/kyo:WCE00300)&gt;15">
														<xsl:choose>
															<xsl:when test="substring(./kyo:WCE00190/kyo:WCE00220/kyo:WCE00270/kyo:WCE00300,1,1)='-'">
																<xsl:value-of select="format-number(substring(./kyo:WCE00190/kyo:WCE00220/kyo:WCE00270/kyo:WCE00300,(string-length(./kyo:WCE00190/kyo:WCE00220/kyo:WCE00270/kyo:WCE00300)-14),15),'-000,000,000,000,000')"/>
															</xsl:when>
															<xsl:otherwise>
																<xsl:value-of select="format-number(substring(./kyo:WCE00190/kyo:WCE00220/kyo:WCE00270/kyo:WCE00300,(string-length(./kyo:WCE00190/kyo:WCE00220/kyo:WCE00270/kyo:WCE00300)-14),15),'000,000,000,000,000')"/>
															</xsl:otherwise>
														</xsl:choose>
													</xsl:when>
													<xsl:otherwise>
														<xsl:value-of select="format-number(./kyo:WCE00190/kyo:WCE00220/kyo:WCE00270/kyo:WCE00300,'###,###,###,###,##0')"/>
													</xsl:otherwise>
												</xsl:choose>
											</xsl:otherwise>
										</xsl:choose>円</td>
								</tr>
								<tr height="15px">
									<td rowspan="6" colspan="5" align="center">新<br/>制<br/>度</td>
									<td rowspan="2" colspan="11" align="center">一般</td>
									<td colspan="28" align="left" class="line2">一般生命保険料(Ｅ)</td>
									<td colspan="28" align="left" class="line2">配当金(相当額)(Ｆ)</td>
									<td colspan="28" align="left" class="line2">一般証明額(Ｅ－Ｆ)</td>
								</tr>
								<tr height="15px">
									<td colspan="28" align="right" class="line3">
										<xsl:choose>
											<xsl:when test="count(./kyo:WCE00190/kyo:WCE00310/kyo:WCE00320/kyo:WCE00330)=0">　</xsl:when>
											<xsl:when test="./kyo:WCE00190/kyo:WCE00310/kyo:WCE00320/kyo:WCE00330=''">　</xsl:when>
											<xsl:otherwise>
												<xsl:choose>
													<xsl:when test="string-length(./kyo:WCE00190/kyo:WCE00310/kyo:WCE00320/kyo:WCE00330)&gt;15">
														<xsl:choose>
															<xsl:when test="substring(./kyo:WCE00190/kyo:WCE00310/kyo:WCE00320/kyo:WCE00330,1,1)='-'">
																<xsl:value-of select="format-number(substring(./kyo:WCE00190/kyo:WCE00310/kyo:WCE00320/kyo:WCE00330,(string-length(./kyo:WCE00190/kyo:WCE00310/kyo:WCE00320/kyo:WCE00330)-14),15),'-000,000,000,000,000')"/>
															</xsl:when>
															<xsl:otherwise>
																<xsl:value-of select="format-number(substring(./kyo:WCE00190/kyo:WCE00310/kyo:WCE00320/kyo:WCE00330,(string-length(./kyo:WCE00190/kyo:WCE00310/kyo:WCE00320/kyo:WCE00330)-14),15),'000,000,000,000,000')"/>
															</xsl:otherwise>
														</xsl:choose>
													</xsl:when>
													<xsl:otherwise>
														<xsl:value-of select="format-number(./kyo:WCE00190/kyo:WCE00310/kyo:WCE00320/kyo:WCE00330,'###,###,###,###,##0')"/>
													</xsl:otherwise>
												</xsl:choose>
											</xsl:otherwise>
										</xsl:choose>円</td>
									<td colspan="28" align="right" class="line3">
										<xsl:choose>
											<xsl:when test="count(./kyo:WCE00190/kyo:WCE00310/kyo:WCE00320/kyo:WCE00340)=0">　</xsl:when>
											<xsl:when test="./kyo:WCE00190/kyo:WCE00310/kyo:WCE00320/kyo:WCE00340=''">　</xsl:when>
											<xsl:otherwise>
												<xsl:choose>
													<xsl:when test="string-length(./kyo:WCE00190/kyo:WCE00310/kyo:WCE00320/kyo:WCE00340)&gt;15">
														<xsl:choose>
															<xsl:when test="substring(./kyo:WCE00190/kyo:WCE00310/kyo:WCE00320/kyo:WCE00340,1,1)='-'">
																<xsl:value-of select="format-number(substring(./kyo:WCE00190/kyo:WCE00310/kyo:WCE00320/kyo:WCE00340,(string-length(./kyo:WCE00190/kyo:WCE00310/kyo:WCE00320/kyo:WCE00340)-14),15),'-000,000,000,000,000')"/>
															</xsl:when>
															<xsl:otherwise>
																<xsl:value-of select="format-number(substring(./kyo:WCE00190/kyo:WCE00310/kyo:WCE00320/kyo:WCE00340,(string-length(./kyo:WCE00190/kyo:WCE00310/kyo:WCE00320/kyo:WCE00340)-14),15),'000,000,000,000,000')"/>
															</xsl:otherwise>
														</xsl:choose>
													</xsl:when>
													<xsl:otherwise>
														<xsl:value-of select="format-number(./kyo:WCE00190/kyo:WCE00310/kyo:WCE00320/kyo:WCE00340,'###,###,###,###,##0')"/>
													</xsl:otherwise>
												</xsl:choose>
											</xsl:otherwise>
										</xsl:choose>円</td>
									<td colspan="28" align="right" class="line3">
										<xsl:choose>
											<xsl:when test="count(./kyo:WCE00190/kyo:WCE00310/kyo:WCE00320/kyo:WCE00350)=0">　</xsl:when>
											<xsl:when test="./kyo:WCE00190/kyo:WCE00310/kyo:WCE00320/kyo:WCE00350=''">　</xsl:when>
											<xsl:otherwise>
												<xsl:choose>
													<xsl:when test="string-length(./kyo:WCE00190/kyo:WCE00310/kyo:WCE00320/kyo:WCE00350)&gt;15">
														<xsl:choose>
															<xsl:when test="substring(./kyo:WCE00190/kyo:WCE00310/kyo:WCE00320/kyo:WCE00350,1,1)='-'">
																<xsl:value-of select="format-number(substring(./kyo:WCE00190/kyo:WCE00310/kyo:WCE00320/kyo:WCE00350,(string-length(./kyo:WCE00190/kyo:WCE00310/kyo:WCE00320/kyo:WCE00350)-14),15),'-000,000,000,000,000')"/>
															</xsl:when>
															<xsl:otherwise>
																<xsl:value-of select="format-number(substring(./kyo:WCE00190/kyo:WCE00310/kyo:WCE00320/kyo:WCE00350,(string-length(./kyo:WCE00190/kyo:WCE00310/kyo:WCE00320/kyo:WCE00350)-14),15),'000,000,000,000,000')"/>
															</xsl:otherwise>
														</xsl:choose>
													</xsl:when>
													<xsl:otherwise>
														<xsl:value-of select="format-number(./kyo:WCE00190/kyo:WCE00310/kyo:WCE00320/kyo:WCE00350,'###,###,###,###,##0')"/>
													</xsl:otherwise>
												</xsl:choose>
											</xsl:otherwise>
										</xsl:choose>円</td>
								</tr>
								<tr height="15px">
									<td rowspan="2" colspan="11" align="center">介護医療</td>
									<td colspan="28" align="left" class="line2">介護医療保険料(Ｇ)</td>
									<td colspan="28" align="left" class="line2">配当金(相当額)(Ｈ)</td>
									<td colspan="28" align="left" class="line2">介護医療証明額(Ｇ－Ｈ)</td>
								</tr>
								<tr height="15px">
									<td colspan="28" align="right" class="line3">
										<xsl:choose>
											<xsl:when test="count(./kyo:WCE00190/kyo:WCE00310/kyo:WCE00360/kyo:WCE00370)=0">　</xsl:when>
											<xsl:when test="./kyo:WCE00190/kyo:WCE00310/kyo:WCE00360/kyo:WCE00370=''">　</xsl:when>
											<xsl:otherwise>
												<xsl:choose>
													<xsl:when test="string-length(./kyo:WCE00190/kyo:WCE00310/kyo:WCE00360/kyo:WCE00370)&gt;15">
														<xsl:choose>
															<xsl:when test="substring(./kyo:WCE00190/kyo:WCE00310/kyo:WCE00360/kyo:WCE00370,1,1)='-'">
																<xsl:value-of select="format-number(substring(./kyo:WCE00190/kyo:WCE00310/kyo:WCE00360/kyo:WCE00370,(string-length(./kyo:WCE00190/kyo:WCE00310/kyo:WCE00360/kyo:WCE00370)-14),15),'-000,000,000,000,000')"/>
															</xsl:when>
															<xsl:otherwise>
																<xsl:value-of select="format-number(substring(./kyo:WCE00190/kyo:WCE00310/kyo:WCE00360/kyo:WCE00370,(string-length(./kyo:WCE00190/kyo:WCE00310/kyo:WCE00360/kyo:WCE00370)-14),15),'000,000,000,000,000')"/>
															</xsl:otherwise>
														</xsl:choose>
													</xsl:when>
													<xsl:otherwise>
														<xsl:value-of select="format-number(./kyo:WCE00190/kyo:WCE00310/kyo:WCE00360/kyo:WCE00370,'###,###,###,###,##0')"/>
													</xsl:otherwise>
												</xsl:choose>
											</xsl:otherwise>
										</xsl:choose>円</td>
									<td colspan="28" align="right" class="line3">
										<xsl:choose>
											<xsl:when test="count(./kyo:WCE00190/kyo:WCE00310/kyo:WCE00360/kyo:WCE00380)=0">　</xsl:when>
											<xsl:when test="./kyo:WCE00190/kyo:WCE00310/kyo:WCE00360/kyo:WCE00380=''">　</xsl:when>
											<xsl:otherwise>
												<xsl:choose>
													<xsl:when test="string-length(./kyo:WCE00190/kyo:WCE00310/kyo:WCE00360/kyo:WCE00380)&gt;15">
														<xsl:choose>
															<xsl:when test="substring(./kyo:WCE00190/kyo:WCE00310/kyo:WCE00360/kyo:WCE00380,1,1)='-'">
																<xsl:value-of select="format-number(substring(./kyo:WCE00190/kyo:WCE00310/kyo:WCE00360/kyo:WCE00380,(string-length(./kyo:WCE00190/kyo:WCE00310/kyo:WCE00360/kyo:WCE00380)-14),15),'-000,000,000,000,000')"/>
															</xsl:when>
															<xsl:otherwise>
																<xsl:value-of select="format-number(substring(./kyo:WCE00190/kyo:WCE00310/kyo:WCE00360/kyo:WCE00380,(string-length(./kyo:WCE00190/kyo:WCE00310/kyo:WCE00360/kyo:WCE00380)-14),15),'000,000,000,000,000')"/>
															</xsl:otherwise>
														</xsl:choose>
													</xsl:when>
													<xsl:otherwise>
														<xsl:value-of select="format-number(./kyo:WCE00190/kyo:WCE00310/kyo:WCE00360/kyo:WCE00380,'###,###,###,###,##0')"/>
													</xsl:otherwise>
												</xsl:choose>
											</xsl:otherwise>
										</xsl:choose>円</td>
									<td colspan="28" align="right" class="line3">
										<xsl:choose>
											<xsl:when test="count(./kyo:WCE00190/kyo:WCE00310/kyo:WCE00360/kyo:WCE00390)=0">　</xsl:when>
											<xsl:when test="./kyo:WCE00190/kyo:WCE00310/kyo:WCE00360/kyo:WCE00390=''">　</xsl:when>
											<xsl:otherwise>
												<xsl:choose>
													<xsl:when test="string-length(./kyo:WCE00190/kyo:WCE00310/kyo:WCE00360/kyo:WCE00390)&gt;15">
														<xsl:choose>
															<xsl:when test="substring(./kyo:WCE00190/kyo:WCE00310/kyo:WCE00360/kyo:WCE00390,1,1)='-'">
																<xsl:value-of select="format-number(substring(./kyo:WCE00190/kyo:WCE00310/kyo:WCE00360/kyo:WCE00390,(string-length(./kyo:WCE00190/kyo:WCE00310/kyo:WCE00360/kyo:WCE00390)-14),15),'-000,000,000,000,000')"/>
															</xsl:when>
															<xsl:otherwise>
																<xsl:value-of select="format-number(substring(./kyo:WCE00190/kyo:WCE00310/kyo:WCE00360/kyo:WCE00390,(string-length(./kyo:WCE00190/kyo:WCE00310/kyo:WCE00360/kyo:WCE00390)-14),15),'000,000,000,000,000')"/>
															</xsl:otherwise>
														</xsl:choose>
													</xsl:when>
													<xsl:otherwise>
														<xsl:value-of select="format-number(./kyo:WCE00190/kyo:WCE00310/kyo:WCE00360/kyo:WCE00390,'###,###,###,###,##0')"/>
													</xsl:otherwise>
												</xsl:choose>
											</xsl:otherwise>
										</xsl:choose>円</td>
								</tr>
								<tr height="15px">
									<td rowspan="2" colspan="11" align="center">年金</td>
									<td colspan="28" align="left" class="line2">個人年金保険料(Ｉ)</td>
									<td colspan="28" align="left" class="line2">配当金(相当額)(Ｊ)</td>
									<td colspan="28" align="left" class="line2">個人年金証明額(Ｉ－Ｊ)</td>
								</tr>
								<tr height="15px">
									<td colspan="28" align="right" class="line3">
										<xsl:choose>
											<xsl:when test="count(./kyo:WCE00190/kyo:WCE00310/kyo:WCE00400/kyo:WCE00410)=0">　</xsl:when>
											<xsl:when test="./kyo:WCE00190/kyo:WCE00310/kyo:WCE00400/kyo:WCE00410=''">　</xsl:when>
											<xsl:otherwise>
												<xsl:choose>
													<xsl:when test="string-length(./kyo:WCE00190/kyo:WCE00310/kyo:WCE00400/kyo:WCE00410)&gt;15">
														<xsl:choose>
															<xsl:when test="substring(./kyo:WCE00190/kyo:WCE00310/kyo:WCE00400/kyo:WCE00410,1,1)='-'">
																<xsl:value-of select="format-number(substring(./kyo:WCE00190/kyo:WCE00310/kyo:WCE00400/kyo:WCE00410,(string-length(./kyo:WCE00190/kyo:WCE00310/kyo:WCE00400/kyo:WCE00410)-14),15),'-000,000,000,000,000')"/>
															</xsl:when>
															<xsl:otherwise>
																<xsl:value-of select="format-number(substring(./kyo:WCE00190/kyo:WCE00310/kyo:WCE00400/kyo:WCE00410,(string-length(./kyo:WCE00190/kyo:WCE00310/kyo:WCE00400/kyo:WCE00410)-14),15),'000,000,000,000,000')"/>
															</xsl:otherwise>
														</xsl:choose>
													</xsl:when>
													<xsl:otherwise>
														<xsl:value-of select="format-number(./kyo:WCE00190/kyo:WCE00310/kyo:WCE00400/kyo:WCE00410,'###,###,###,###,##0')"/>
													</xsl:otherwise>
												</xsl:choose>
											</xsl:otherwise>
										</xsl:choose>円</td>
									<td colspan="28" align="right" class="line3">
										<xsl:choose>
											<xsl:when test="count(./kyo:WCE00190/kyo:WCE00310/kyo:WCE00400/kyo:WCE00420)=0">　</xsl:when>
											<xsl:when test="./kyo:WCE00190/kyo:WCE00310/kyo:WCE00400/kyo:WCE00420=''">　</xsl:when>
											<xsl:otherwise>
												<xsl:choose>
													<xsl:when test="string-length(./kyo:WCE00190/kyo:WCE00310/kyo:WCE00400/kyo:WCE00420)&gt;15">
														<xsl:choose>
															<xsl:when test="substring(./kyo:WCE00190/kyo:WCE00310/kyo:WCE00400/kyo:WCE00420,1,1)='-'">
																<xsl:value-of select="format-number(substring(./kyo:WCE00190/kyo:WCE00310/kyo:WCE00400/kyo:WCE00420,(string-length(./kyo:WCE00190/kyo:WCE00310/kyo:WCE00400/kyo:WCE00420)-14),15),'-000,000,000,000,000')"/>
															</xsl:when>
															<xsl:otherwise>
																<xsl:value-of select="format-number(substring(./kyo:WCE00190/kyo:WCE00310/kyo:WCE00400/kyo:WCE00420,(string-length(./kyo:WCE00190/kyo:WCE00310/kyo:WCE00400/kyo:WCE00420)-14),15),'000,000,000,000,000')"/>
															</xsl:otherwise>
														</xsl:choose>
													</xsl:when>
													<xsl:otherwise>
														<xsl:value-of select="format-number(./kyo:WCE00190/kyo:WCE00310/kyo:WCE00400/kyo:WCE00420,'###,###,###,###,##0')"/>
													</xsl:otherwise>
												</xsl:choose>
											</xsl:otherwise>
										</xsl:choose>円</td>
									<td colspan="28" align="right" class="line3">
										<xsl:choose>
											<xsl:when test="count(./kyo:WCE00190/kyo:WCE00310/kyo:WCE00400/kyo:WCE00430)=0">　</xsl:when>
											<xsl:when test="./kyo:WCE00190/kyo:WCE00310/kyo:WCE00400/kyo:WCE00430=''">　</xsl:when>
											<xsl:otherwise>
												<xsl:choose>
													<xsl:when test="string-length(./kyo:WCE00190/kyo:WCE00310/kyo:WCE00400/kyo:WCE00430)&gt;15">
														<xsl:choose>
															<xsl:when test="substring(./kyo:WCE00190/kyo:WCE00310/kyo:WCE00400/kyo:WCE00430,1,1)='-'">
																<xsl:value-of select="format-number(substring(./kyo:WCE00190/kyo:WCE00310/kyo:WCE00400/kyo:WCE00430,(string-length(./kyo:WCE00190/kyo:WCE00310/kyo:WCE00400/kyo:WCE00430)-14),15),'-000,000,000,000,000')"/>
															</xsl:when>
															<xsl:otherwise>
																<xsl:value-of select="format-number(substring(./kyo:WCE00190/kyo:WCE00310/kyo:WCE00400/kyo:WCE00430,(string-length(./kyo:WCE00190/kyo:WCE00310/kyo:WCE00400/kyo:WCE00430)-14),15),'000,000,000,000,000')"/>
															</xsl:otherwise>
														</xsl:choose>
													</xsl:when>
													<xsl:otherwise>
														<xsl:value-of select="format-number(./kyo:WCE00190/kyo:WCE00310/kyo:WCE00400/kyo:WCE00430,'###,###,###,###,##0')"/>
													</xsl:otherwise>
												</xsl:choose>
											</xsl:otherwise>
										</xsl:choose>円</td>
								</tr>
							</table>
							<br/>
							<table border="0">
								<tr height="14">
									<td colspan="100" align="left">《ご参考》
										<xsl:choose>
											<xsl:when test="count(kyo:WCE00010)=0">　</xsl:when>
											<xsl:otherwise>
												<xsl:choose>
													<xsl:when test="string-length(kyo:WCE00010/gen:yyyy)&gt;4">
														<xsl:choose>
															<xsl:when test="substring(kyo:WCE00010/gen:yyyy,1,1)='-'">
																<xsl:value-of select="format-number(substring(kyo:WCE00010/gen:yyyy,(string-length(kyo:WCE00010/gen:yyyy)-3),4),'-0000')"/>
															</xsl:when>
															<xsl:otherwise>
																<xsl:value-of select="format-number(substring(kyo:WCE00010/gen:yyyy,(string-length(kyo:WCE00010/gen:yyyy)-3),4),'0000')"/>
															</xsl:otherwise>
														</xsl:choose>
													</xsl:when>
													<xsl:when test="kyo:WCE00010/gen:yyyy=''">　</xsl:when>
													<xsl:otherwise>
														<xsl:value-of select="format-number(kyo:WCE00010/gen:yyyy,'###0')"/>
													</xsl:otherwise>
												</xsl:choose>
											</xsl:otherwise>
										</xsl:choose> 年 12 月末まで保険料をお払い込みの場合は下記金額をご申告ください。</td>
								</tr>
							</table>
							<table border="2" cellspacing="0" style="table-layout: fixed;">
								<tr height="15px">
									<td rowspan="4" colspan="5" align="center" class="line4">旧<br/>制<br/>度</td>
									<td rowspan="2" colspan="11" align="center">一般</td>
									<td colspan="28" align="left" class="line2">一般生命保険料(ａ)</td>
									<td colspan="28" align="left" class="line2">配当金(相当額)(ｂ)</td>
									<td colspan="28" align="left" class="line7">一般申告額(ａ－ｂ)</td>
								</tr>
								<tr height="15px">
									<td colspan="28" align="right" class="line3">
										<xsl:choose>
											<xsl:when test="count(./kyo:WCE00440/kyo:WCE00460/kyo:WCE00470/kyo:WCE00480)=0">　</xsl:when>
											<xsl:when test="./kyo:WCE00440/kyo:WCE00460/kyo:WCE00470/kyo:WCE00480=''">　</xsl:when>
											<xsl:otherwise>
												<xsl:choose>
													<xsl:when test="string-length(./kyo:WCE00440/kyo:WCE00460/kyo:WCE00470/kyo:WCE00480)&gt;15">
														<xsl:choose>
															<xsl:when test="substring(./kyo:WCE00440/kyo:WCE00460/kyo:WCE00470/kyo:WCE00480,1,1)='-'">
																<xsl:value-of select="format-number(substring(./kyo:WCE00440/kyo:WCE00460/kyo:WCE00470/kyo:WCE00480,(string-length(./kyo:WCE00440/kyo:WCE00460/kyo:WCE00470/kyo:WCE00480)-14),15),'-000,000,000,000,000')"/>
															</xsl:when>
															<xsl:otherwise>
																<xsl:value-of select="format-number(substring(./kyo:WCE00440/kyo:WCE00460/kyo:WCE00470/kyo:WCE00480,(string-length(./kyo:WCE00440/kyo:WCE00460/kyo:WCE00470/kyo:WCE00480)-14),15),'000,000,000,000,000')"/>
															</xsl:otherwise>
														</xsl:choose>
													</xsl:when>
													<xsl:otherwise>
														<xsl:value-of select="format-number(./kyo:WCE00440/kyo:WCE00460/kyo:WCE00470/kyo:WCE00480,'###,###,###,###,##0')"/>
													</xsl:otherwise>
												</xsl:choose>
											</xsl:otherwise>
										</xsl:choose>円</td>
									<td colspan="28" align="right" class="line3">
										<xsl:choose>
											<xsl:when test="count(./kyo:WCE00440/kyo:WCE00460/kyo:WCE00470/kyo:WCE00490)=0">　</xsl:when>
											<xsl:when test="./kyo:WCE00440/kyo:WCE00460/kyo:WCE00470/kyo:WCE00490=''">　</xsl:when>
											<xsl:otherwise>
												<xsl:choose>
													<xsl:when test="string-length(./kyo:WCE00440/kyo:WCE00460/kyo:WCE00470/kyo:WCE00490)&gt;15">
														<xsl:choose>
															<xsl:when test="substring(./kyo:WCE00440/kyo:WCE00460/kyo:WCE00470/kyo:WCE00490,1,1)='-'">
																<xsl:value-of select="format-number(substring(./kyo:WCE00440/kyo:WCE00460/kyo:WCE00470/kyo:WCE00490,(string-length(./kyo:WCE00440/kyo:WCE00460/kyo:WCE00470/kyo:WCE00490)-14),15),'-000,000,000,000,000')"/>
															</xsl:when>
															<xsl:otherwise>
																<xsl:value-of select="format-number(substring(./kyo:WCE00440/kyo:WCE00460/kyo:WCE00470/kyo:WCE00490,(string-length(./kyo:WCE00440/kyo:WCE00460/kyo:WCE00470/kyo:WCE00490)-14),15),'000,000,000,000,000')"/>
															</xsl:otherwise>
														</xsl:choose>
													</xsl:when>
													<xsl:otherwise>
														<xsl:value-of select="format-number(./kyo:WCE00440/kyo:WCE00460/kyo:WCE00470/kyo:WCE00490,'###,###,###,###,##0')"/>
													</xsl:otherwise>
												</xsl:choose>
											</xsl:otherwise>
										</xsl:choose>円</td>
									<td colspan="28" align="right" class="line8">
										<xsl:choose>
											<xsl:when test="count(./kyo:WCE00440/kyo:WCE00460/kyo:WCE00470/kyo:WCE00500)=0">　</xsl:when>
											<xsl:when test="./kyo:WCE00440/kyo:WCE00460/kyo:WCE00470/kyo:WCE00500=''">　</xsl:when>
											<xsl:otherwise>
												<xsl:choose>
													<xsl:when test="string-length(./kyo:WCE00440/kyo:WCE00460/kyo:WCE00470/kyo:WCE00500)&gt;15">
														<xsl:choose>
															<xsl:when test="substring(./kyo:WCE00440/kyo:WCE00460/kyo:WCE00470/kyo:WCE00500,1,1)='-'">
																<xsl:value-of select="format-number(substring(./kyo:WCE00440/kyo:WCE00460/kyo:WCE00470/kyo:WCE00500,(string-length(./kyo:WCE00440/kyo:WCE00460/kyo:WCE00470/kyo:WCE00500)-14),15),'-000,000,000,000,000')"/>
															</xsl:when>
															<xsl:otherwise>
																<xsl:value-of select="format-number(substring(./kyo:WCE00440/kyo:WCE00460/kyo:WCE00470/kyo:WCE00500,(string-length(./kyo:WCE00440/kyo:WCE00460/kyo:WCE00470/kyo:WCE00500)-14),15),'000,000,000,000,000')"/>
															</xsl:otherwise>
														</xsl:choose>
													</xsl:when>
													<xsl:otherwise>
														<xsl:value-of select="format-number(./kyo:WCE00440/kyo:WCE00460/kyo:WCE00470/kyo:WCE00500,'###,###,###,###,##0')"/>
													</xsl:otherwise>
												</xsl:choose>
											</xsl:otherwise>
										</xsl:choose>円</td>
								</tr>
								<tr height="15px">
									<td rowspan="2" colspan="11" align="center" class="line4">年金</td>
									<td colspan="28" align="left" class="line2">個人年金保険料(ｃ)</td>
									<td colspan="28" align="left" class="line2">配当金(相当額)(ｄ)</td>
									<td colspan="28" align="left" class="line9">個人年金申告額(ｃ－ｄ)</td>
								</tr>
								<tr height="15px">
									<td colspan="28" align="right" class="line5">
										<xsl:choose>
											<xsl:when test="count(./kyo:WCE00440/kyo:WCE00460/kyo:WCE00510/kyo:WCE00520)=0">　</xsl:when>
											<xsl:when test="./kyo:WCE00440/kyo:WCE00460/kyo:WCE00510/kyo:WCE00520=''">　</xsl:when>
											<xsl:otherwise>
												<xsl:choose>
													<xsl:when test="string-length(./kyo:WCE00440/kyo:WCE00460/kyo:WCE00510/kyo:WCE00520)&gt;15">
														<xsl:choose>
															<xsl:when test="substring(./kyo:WCE00440/kyo:WCE00460/kyo:WCE00510/kyo:WCE00520,1,1)='-'">
																<xsl:value-of select="format-number(substring(./kyo:WCE00440/kyo:WCE00460/kyo:WCE00510/kyo:WCE00520,(string-length(./kyo:WCE00440/kyo:WCE00460/kyo:WCE00510/kyo:WCE00520)-14),15),'-000,000,000,000,000')"/>
															</xsl:when>
															<xsl:otherwise>
																<xsl:value-of select="format-number(substring(./kyo:WCE00440/kyo:WCE00460/kyo:WCE00510/kyo:WCE00520,(string-length(./kyo:WCE00440/kyo:WCE00460/kyo:WCE00510/kyo:WCE00520)-14),15),'000,000,000,000,000')"/>
															</xsl:otherwise>
														</xsl:choose>
													</xsl:when>
													<xsl:otherwise>
														<xsl:value-of select="format-number(./kyo:WCE00440/kyo:WCE00460/kyo:WCE00510/kyo:WCE00520,'###,###,###,###,##0')"/>
													</xsl:otherwise>
												</xsl:choose>
											</xsl:otherwise>
										</xsl:choose>円</td>
									<td colspan="28" align="right" class="line5">
										<xsl:choose>
											<xsl:when test="count(./kyo:WCE00440/kyo:WCE00460/kyo:WCE00510/kyo:WCE00530)=0">　</xsl:when>
											<xsl:when test="./kyo:WCE00440/kyo:WCE00460/kyo:WCE00510/kyo:WCE00530=''">　</xsl:when>
											<xsl:otherwise>
												<xsl:choose>
													<xsl:when test="string-length(./kyo:WCE00440/kyo:WCE00460/kyo:WCE00510/kyo:WCE00530)&gt;15">
														<xsl:choose>
															<xsl:when test="substring(./kyo:WCE00440/kyo:WCE00460/kyo:WCE00510/kyo:WCE00530,1,1)='-'">
																<xsl:value-of select="format-number(substring(./kyo:WCE00440/kyo:WCE00460/kyo:WCE00510/kyo:WCE00530,(string-length(./kyo:WCE00440/kyo:WCE00460/kyo:WCE00510/kyo:WCE00530)-14),15),'-000,000,000,000,000')"/>
															</xsl:when>
															<xsl:otherwise>
																<xsl:value-of select="format-number(substring(./kyo:WCE00440/kyo:WCE00460/kyo:WCE00510/kyo:WCE00530,(string-length(./kyo:WCE00440/kyo:WCE00460/kyo:WCE00510/kyo:WCE00530)-14),15),'000,000,000,000,000')"/>
															</xsl:otherwise>
														</xsl:choose>
													</xsl:when>
													<xsl:otherwise>
														<xsl:value-of select="format-number(./kyo:WCE00440/kyo:WCE00460/kyo:WCE00510/kyo:WCE00530,'###,###,###,###,##0')"/>
													</xsl:otherwise>
												</xsl:choose>
											</xsl:otherwise>
										</xsl:choose>円</td>
									<td colspan="28" align="right" class="line10">
										<xsl:choose>
											<xsl:when test="count(./kyo:WCE00440/kyo:WCE00460/kyo:WCE00510/kyo:WCE00540)=0">　</xsl:when>
											<xsl:when test="./kyo:WCE00440/kyo:WCE00460/kyo:WCE00510/kyo:WCE00540=''">　</xsl:when>
											<xsl:otherwise>
												<xsl:choose>
													<xsl:when test="string-length(./kyo:WCE00440/kyo:WCE00460/kyo:WCE00510/kyo:WCE00540)&gt;15">
														<xsl:choose>
															<xsl:when test="substring(./kyo:WCE00440/kyo:WCE00460/kyo:WCE00510/kyo:WCE00540,1,1)='-'">
																<xsl:value-of select="format-number(substring(./kyo:WCE00440/kyo:WCE00460/kyo:WCE00510/kyo:WCE00540,(string-length(./kyo:WCE00440/kyo:WCE00460/kyo:WCE00510/kyo:WCE00540)-14),15),'-000,000,000,000,000')"/>
															</xsl:when>
															<xsl:otherwise>
																<xsl:value-of select="format-number(substring(./kyo:WCE00440/kyo:WCE00460/kyo:WCE00510/kyo:WCE00540,(string-length(./kyo:WCE00440/kyo:WCE00460/kyo:WCE00510/kyo:WCE00540)-14),15),'000,000,000,000,000')"/>
															</xsl:otherwise>
														</xsl:choose>
													</xsl:when>
													<xsl:otherwise>
														<xsl:value-of select="format-number(./kyo:WCE00440/kyo:WCE00460/kyo:WCE00510/kyo:WCE00540,'###,###,###,###,##0')"/>
													</xsl:otherwise>
												</xsl:choose>
											</xsl:otherwise>
										</xsl:choose>円</td>
								</tr>
								<tr height="15px">
									<td rowspan="6" colspan="5" align="center">新<br/>制<br/>度</td>
									<td rowspan="2" colspan="11" align="center">一般</td>
									<td colspan="28" align="left" class="line2">一般生命保険料(ｅ)</td>
									<td colspan="28" align="left" class="line2">配当金(相当額)(ｆ)</td>
									<td colspan="28" align="left" class="line11">一般申告額(ｅ－ｆ)</td>
								</tr>
								<tr height="15px">
									<td colspan="28" align="right" class="line3">
										<xsl:choose>
											<xsl:when test="count(./kyo:WCE00440/kyo:WCE00550/kyo:WCE00560/kyo:WCE00570)=0">　</xsl:when>
											<xsl:when test="./kyo:WCE00440/kyo:WCE00550/kyo:WCE00560/kyo:WCE00570=''">　</xsl:when>
											<xsl:otherwise>
												<xsl:choose>
													<xsl:when test="string-length(./kyo:WCE00440/kyo:WCE00550/kyo:WCE00560/kyo:WCE00570)&gt;15">
														<xsl:choose>
															<xsl:when test="substring(./kyo:WCE00440/kyo:WCE00550/kyo:WCE00560/kyo:WCE00570,1,1)='-'">
																<xsl:value-of select="format-number(substring(./kyo:WCE00440/kyo:WCE00550/kyo:WCE00560/kyo:WCE00570,(string-length(./kyo:WCE00440/kyo:WCE00550/kyo:WCE00560/kyo:WCE00570)-14),15),'-000,000,000,000,000')"/>
															</xsl:when>
															<xsl:otherwise>
																<xsl:value-of select="format-number(substring(./kyo:WCE00440/kyo:WCE00550/kyo:WCE00560/kyo:WCE00570,(string-length(./kyo:WCE00440/kyo:WCE00550/kyo:WCE00560/kyo:WCE00570)-14),15),'000,000,000,000,000')"/>
															</xsl:otherwise>
														</xsl:choose>
													</xsl:when>
													<xsl:otherwise>
														<xsl:value-of select="format-number(./kyo:WCE00440/kyo:WCE00550/kyo:WCE00560/kyo:WCE00570,'###,###,###,###,##0')"/>
													</xsl:otherwise>
												</xsl:choose>
											</xsl:otherwise>
										</xsl:choose>円</td>
									<td colspan="28" align="right" class="line3">
										<xsl:choose>
											<xsl:when test="count(./kyo:WCE00440/kyo:WCE00550/kyo:WCE00560/kyo:WCE00580)=0">　</xsl:when>
											<xsl:when test="./kyo:WCE00440/kyo:WCE00550/kyo:WCE00560/kyo:WCE00580=''">　</xsl:when>
											<xsl:otherwise>
												<xsl:choose>
													<xsl:when test="string-length(./kyo:WCE00440/kyo:WCE00550/kyo:WCE00560/kyo:WCE00580)&gt;15">
														<xsl:choose>
															<xsl:when test="substring(./kyo:WCE00440/kyo:WCE00550/kyo:WCE00560/kyo:WCE00580,1,1)='-'">
																<xsl:value-of select="format-number(substring(./kyo:WCE00440/kyo:WCE00550/kyo:WCE00560/kyo:WCE00580,(string-length(./kyo:WCE00440/kyo:WCE00550/kyo:WCE00560/kyo:WCE00580)-14),15),'-000,000,000,000,000')"/>
															</xsl:when>
															<xsl:otherwise>
																<xsl:value-of select="format-number(substring(./kyo:WCE00440/kyo:WCE00550/kyo:WCE00560/kyo:WCE00580,(string-length(./kyo:WCE00440/kyo:WCE00550/kyo:WCE00560/kyo:WCE00580)-14),15),'000,000,000,000,000')"/>
															</xsl:otherwise>
														</xsl:choose>
													</xsl:when>
													<xsl:otherwise>
														<xsl:value-of select="format-number(./kyo:WCE00440/kyo:WCE00550/kyo:WCE00560/kyo:WCE00580,'###,###,###,###,##0')"/>
													</xsl:otherwise>
												</xsl:choose>
											</xsl:otherwise>
										</xsl:choose>円</td>
									<td colspan="28" align="right" class="line8">
										<xsl:choose>
											<xsl:when test="count(./kyo:WCE00440/kyo:WCE00550/kyo:WCE00560/kyo:WCE00590)=0">　</xsl:when>
											<xsl:when test="./kyo:WCE00440/kyo:WCE00550/kyo:WCE00560/kyo:WCE00590=''">　</xsl:when>
											<xsl:otherwise>
												<xsl:choose>
													<xsl:when test="string-length(./kyo:WCE00440/kyo:WCE00550/kyo:WCE00560/kyo:WCE00590)&gt;15">
														<xsl:choose>
															<xsl:when test="substring(./kyo:WCE00440/kyo:WCE00550/kyo:WCE00560/kyo:WCE00590,1,1)='-'">
																<xsl:value-of select="format-number(substring(./kyo:WCE00440/kyo:WCE00550/kyo:WCE00560/kyo:WCE00590,(string-length(./kyo:WCE00440/kyo:WCE00550/kyo:WCE00560/kyo:WCE00590)-14),15),'-000,000,000,000,000')"/>
															</xsl:when>
															<xsl:otherwise>
																<xsl:value-of select="format-number(substring(./kyo:WCE00440/kyo:WCE00550/kyo:WCE00560/kyo:WCE00590,(string-length(./kyo:WCE00440/kyo:WCE00550/kyo:WCE00560/kyo:WCE00590)-14),15),'000,000,000,000,000')"/>
															</xsl:otherwise>
														</xsl:choose>
													</xsl:when>
													<xsl:otherwise>
														<xsl:value-of select="format-number(./kyo:WCE00440/kyo:WCE00550/kyo:WCE00560/kyo:WCE00590,'###,###,###,###,##0')"/>
													</xsl:otherwise>
												</xsl:choose>
											</xsl:otherwise>
										</xsl:choose>円</td>
								</tr>
								<tr height="15px">
									<td rowspan="2" colspan="11" align="center">介護医療</td>
									<td colspan="28" align="left" class="line2">介護医療保険料(ｇ)</td>
									<td colspan="28" align="left" class="line2">配当金(相当額)(ｈ)</td>
									<td colspan="28" align="left" class="line9">介護医療申告額(ｇ－ｈ)</td>
								</tr>
								<tr height="15px">
									<td colspan="28" align="right" class="line3">
										<xsl:choose>
											<xsl:when test="count(./kyo:WCE00440/kyo:WCE00550/kyo:WCE00600/kyo:WCE00610)=0">　</xsl:when>
											<xsl:when test="./kyo:WCE00440/kyo:WCE00550/kyo:WCE00600/kyo:WCE00610=''">　</xsl:when>
											<xsl:otherwise>
												<xsl:choose>
													<xsl:when test="string-length(./kyo:WCE00440/kyo:WCE00550/kyo:WCE00600/kyo:WCE00610)&gt;15">
														<xsl:choose>
															<xsl:when test="substring(./kyo:WCE00440/kyo:WCE00550/kyo:WCE00600/kyo:WCE00610,1,1)='-'">
																<xsl:value-of select="format-number(substring(./kyo:WCE00440/kyo:WCE00550/kyo:WCE00600/kyo:WCE00610,(string-length(./kyo:WCE00440/kyo:WCE00550/kyo:WCE00600/kyo:WCE00610)-14),15),'-000,000,000,000,000')"/>
															</xsl:when>
															<xsl:otherwise>
																<xsl:value-of select="format-number(substring(./kyo:WCE00440/kyo:WCE00550/kyo:WCE00600/kyo:WCE00610,(string-length(./kyo:WCE00440/kyo:WCE00550/kyo:WCE00600/kyo:WCE00610)-14),15),'000,000,000,000,000')"/>
															</xsl:otherwise>
														</xsl:choose>
													</xsl:when>
													<xsl:otherwise>
														<xsl:value-of select="format-number(./kyo:WCE00440/kyo:WCE00550/kyo:WCE00600/kyo:WCE00610,'###,###,###,###,##0')"/>
													</xsl:otherwise>
												</xsl:choose>
											</xsl:otherwise>
										</xsl:choose>円</td>
									<td colspan="28" align="right" class="line3">
										<xsl:choose>
											<xsl:when test="count(./kyo:WCE00440/kyo:WCE00550/kyo:WCE00600/kyo:WCE00620)=0">　</xsl:when>
											<xsl:when test="./kyo:WCE00440/kyo:WCE00550/kyo:WCE00600/kyo:WCE00620=''">　</xsl:when>
											<xsl:otherwise>
												<xsl:choose>
													<xsl:when test="string-length(./kyo:WCE00440/kyo:WCE00550/kyo:WCE00600/kyo:WCE00620)&gt;15">
														<xsl:choose>
															<xsl:when test="substring(./kyo:WCE00440/kyo:WCE00550/kyo:WCE00600/kyo:WCE00620,1,1)='-'">
																<xsl:value-of select="format-number(substring(./kyo:WCE00440/kyo:WCE00550/kyo:WCE00600/kyo:WCE00620,(string-length(./kyo:WCE00440/kyo:WCE00550/kyo:WCE00600/kyo:WCE00620)-14),15),'-000,000,000,000,000')"/>
															</xsl:when>
															<xsl:otherwise>
																<xsl:value-of select="format-number(substring(./kyo:WCE00440/kyo:WCE00550/kyo:WCE00600/kyo:WCE00620,(string-length(./kyo:WCE00440/kyo:WCE00550/kyo:WCE00600/kyo:WCE00620)-14),15),'000,000,000,000,000')"/>
															</xsl:otherwise>
														</xsl:choose>
													</xsl:when>
													<xsl:otherwise>
														<xsl:value-of select="format-number(./kyo:WCE00440/kyo:WCE00550/kyo:WCE00600/kyo:WCE00620,'###,###,###,###,##0')"/>
													</xsl:otherwise>
												</xsl:choose>
											</xsl:otherwise>
										</xsl:choose>円</td>
									<td colspan="28" align="right" class="line8">
										<xsl:choose>
											<xsl:when test="count(./kyo:WCE00440/kyo:WCE00550/kyo:WCE00600/kyo:WCE00630)=0">　</xsl:when>
											<xsl:when test="./kyo:WCE00440/kyo:WCE00550/kyo:WCE00600/kyo:WCE00630=''">　</xsl:when>
											<xsl:otherwise>
												<xsl:choose>
													<xsl:when test="string-length(./kyo:WCE00440/kyo:WCE00550/kyo:WCE00600/kyo:WCE00630)&gt;15">
														<xsl:choose>
															<xsl:when test="substring(./kyo:WCE00440/kyo:WCE00550/kyo:WCE00600/kyo:WCE00630,1,1)='-'">
																<xsl:value-of select="format-number(substring(./kyo:WCE00440/kyo:WCE00550/kyo:WCE00600/kyo:WCE00630,(string-length(./kyo:WCE00440/kyo:WCE00550/kyo:WCE00600/kyo:WCE00630)-14),15),'-000,000,000,000,000')"/>
															</xsl:when>
															<xsl:otherwise>
																<xsl:value-of select="format-number(substring(./kyo:WCE00440/kyo:WCE00550/kyo:WCE00600/kyo:WCE00630,(string-length(./kyo:WCE00440/kyo:WCE00550/kyo:WCE00600/kyo:WCE00630)-14),15),'000,000,000,000,000')"/>
															</xsl:otherwise>
														</xsl:choose>
													</xsl:when>
													<xsl:otherwise>
														<xsl:value-of select="format-number(./kyo:WCE00440/kyo:WCE00550/kyo:WCE00600/kyo:WCE00630,'###,###,###,###,##0')"/>
													</xsl:otherwise>
												</xsl:choose>
											</xsl:otherwise>
										</xsl:choose>円</td>
								</tr>
								<tr height="15px">
									<td rowspan="2" colspan="11" align="center">年金</td>
									<td colspan="28" align="left" class="line2">個人年金保険料(ｉ)</td>
									<td colspan="28" align="left" class="line2">配当金(相当額)(ｊ)</td>
									<td colspan="28" align="left" class="line9">個人年金申告額(ｉ－ｊ)</td>
								</tr>
								<tr height="15px">
									<td colspan="28" align="right" class="line3">
										<xsl:choose>
											<xsl:when test="count(./kyo:WCE00440/kyo:WCE00550/kyo:WCE00640/kyo:WCE00650)=0">　</xsl:when>
											<xsl:when test="./kyo:WCE00440/kyo:WCE00550/kyo:WCE00640/kyo:WCE00650=''">　</xsl:when>
											<xsl:otherwise>
												<xsl:choose>
													<xsl:when test="string-length(./kyo:WCE00440/kyo:WCE00550/kyo:WCE00640/kyo:WCE00650)&gt;15">
														<xsl:choose>
															<xsl:when test="substring(./kyo:WCE00440/kyo:WCE00550/kyo:WCE00640/kyo:WCE00650,1,1)='-'">
																<xsl:value-of select="format-number(substring(./kyo:WCE00440/kyo:WCE00550/kyo:WCE00640/kyo:WCE00650,(string-length(./kyo:WCE00440/kyo:WCE00550/kyo:WCE00640/kyo:WCE00650)-14),15),'-000,000,000,000,000')"/>
															</xsl:when>
															<xsl:otherwise>
																<xsl:value-of select="format-number(substring(./kyo:WCE00440/kyo:WCE00550/kyo:WCE00640/kyo:WCE00650,(string-length(./kyo:WCE00440/kyo:WCE00550/kyo:WCE00640/kyo:WCE00650)-14),15),'000,000,000,000,000')"/>
															</xsl:otherwise>
														</xsl:choose>
													</xsl:when>
													<xsl:otherwise>
														<xsl:value-of select="format-number(./kyo:WCE00440/kyo:WCE00550/kyo:WCE00640/kyo:WCE00650,'###,###,###,###,##0')"/>
													</xsl:otherwise>
												</xsl:choose>
											</xsl:otherwise>
										</xsl:choose>円</td>
									<td colspan="28" align="right" class="line3">
										<xsl:choose>
											<xsl:when test="count(./kyo:WCE00440/kyo:WCE00550/kyo:WCE00640/kyo:WCE00660)=0">　</xsl:when>
											<xsl:when test="./kyo:WCE00440/kyo:WCE00550/kyo:WCE00640/kyo:WCE00660=''">　</xsl:when>
											<xsl:otherwise>
												<xsl:choose>
													<xsl:when test="string-length(./kyo:WCE00440/kyo:WCE00550/kyo:WCE00640/kyo:WCE00660)&gt;15">
														<xsl:choose>
															<xsl:when test="substring(./kyo:WCE00440/kyo:WCE00550/kyo:WCE00640/kyo:WCE00660,1,1)='-'">
																<xsl:value-of select="format-number(substring(./kyo:WCE00440/kyo:WCE00550/kyo:WCE00640/kyo:WCE00660,(string-length(./kyo:WCE00440/kyo:WCE00550/kyo:WCE00640/kyo:WCE00660)-14),15),'-000,000,000,000,000')"/>
															</xsl:when>
															<xsl:otherwise>
																<xsl:value-of select="format-number(substring(./kyo:WCE00440/kyo:WCE00550/kyo:WCE00640/kyo:WCE00660,(string-length(./kyo:WCE00440/kyo:WCE00550/kyo:WCE00640/kyo:WCE00660)-14),15),'000,000,000,000,000')"/>
															</xsl:otherwise>
														</xsl:choose>
													</xsl:when>
													<xsl:otherwise>
														<xsl:value-of select="format-number(./kyo:WCE00440/kyo:WCE00550/kyo:WCE00640/kyo:WCE00660,'###,###,###,###,##0')"/>
													</xsl:otherwise>
												</xsl:choose>
											</xsl:otherwise>
										</xsl:choose>円</td>
									<td colspan="28" align="right" class="line12">
										<xsl:choose>
											<xsl:when test="count(./kyo:WCE00440/kyo:WCE00550/kyo:WCE00640/kyo:WCE00670)=0">　</xsl:when>
											<xsl:when test="./kyo:WCE00440/kyo:WCE00550/kyo:WCE00640/kyo:WCE00670=''">　</xsl:when>
											<xsl:otherwise>
												<xsl:choose>
													<xsl:when test="string-length(./kyo:WCE00440/kyo:WCE00550/kyo:WCE00640/kyo:WCE00670)&gt;15">
														<xsl:choose>
															<xsl:when test="substring(./kyo:WCE00440/kyo:WCE00550/kyo:WCE00640/kyo:WCE00670,1,1)='-'">
																<xsl:value-of select="format-number(substring(./kyo:WCE00440/kyo:WCE00550/kyo:WCE00640/kyo:WCE00670,(string-length(./kyo:WCE00440/kyo:WCE00550/kyo:WCE00640/kyo:WCE00670)-14),15),'-000,000,000,000,000')"/>
															</xsl:when>
															<xsl:otherwise>
																<xsl:value-of select="format-number(substring(./kyo:WCE00440/kyo:WCE00550/kyo:WCE00640/kyo:WCE00670,(string-length(./kyo:WCE00440/kyo:WCE00550/kyo:WCE00640/kyo:WCE00670)-14),15),'000,000,000,000,000')"/>
															</xsl:otherwise>
														</xsl:choose>
													</xsl:when>
													<xsl:otherwise>
														<xsl:value-of select="format-number(./kyo:WCE00440/kyo:WCE00550/kyo:WCE00640/kyo:WCE00670,'###,###,###,###,##0')"/>
													</xsl:otherwise>
												</xsl:choose>
											</xsl:otherwise>
										</xsl:choose>円</td>
								</tr>
							</table>
							<br/>
							<table border="0">
								<tr height="14">
									<td colspan="100" align="left">転換等一時払保険料</td>
								</tr>
							</table>
							<table border="2" cellspacing="0" style="table-layout: fixed;">
								<tr height="15px">
									<td colspan="33" align="left" class="line2">一般生命保険料</td>
									<td colspan="33" align="left" class="line2">介護医療保険料</td>
									<td colspan="34" align="left" class="line2">個人年金保険料</td>
								</tr>
								<tr height="15px">
									<td colspan="33" align="right" class="line3">
										<xsl:choose>
											<xsl:when test="count(./kyo:WCE00680/kyo:WCE00690)=0">　</xsl:when>
											<xsl:when test="./kyo:WCE00680/kyo:WCE00690=''">　</xsl:when>
											<xsl:otherwise>
												<xsl:choose>
													<xsl:when test="string-length(./kyo:WCE00680/kyo:WCE00690)&gt;15">
														<xsl:choose>
															<xsl:when test="substring(./kyo:WCE00680/kyo:WCE00690,1,1)='-'">
																<xsl:value-of select="format-number(substring(./kyo:WCE00680/kyo:WCE00690,(string-length(./kyo:WCE00680/kyo:WCE00690)-14),15),'-000,000,000,000,000')"/>
															</xsl:when>
															<xsl:otherwise>
																<xsl:value-of select="format-number(substring(./kyo:WCE00680/kyo:WCE00690,(string-length(./kyo:WCE00680/kyo:WCE00690)-14),15),'000,000,000,000,000')"/>
															</xsl:otherwise>
														</xsl:choose>
													</xsl:when>
													<xsl:otherwise>
														<xsl:value-of select="format-number(./kyo:WCE00680/kyo:WCE00690,'###,###,###,###,##0')"/>
													</xsl:otherwise>
												</xsl:choose>
											</xsl:otherwise>
										</xsl:choose>円</td>
									<td colspan="33" align="right" class="line3">
										<xsl:choose>
											<xsl:when test="count(./kyo:WCE00680/kyo:WCE00700)=0">　</xsl:when>
											<xsl:when test="./kyo:WCE00680/kyo:WCE00700=''">　</xsl:when>
											<xsl:otherwise>
												<xsl:choose>
													<xsl:when test="string-length(./kyo:WCE00680/kyo:WCE00700)&gt;15">
														<xsl:choose>
															<xsl:when test="substring(./kyo:WCE00680/kyo:WCE00700,1,1)='-'">
																<xsl:value-of select="format-number(substring(./kyo:WCE00680/kyo:WCE00700,(string-length(./kyo:WCE00680/kyo:WCE00700)-14),15),'-000,000,000,000,000')"/>
															</xsl:when>
															<xsl:otherwise>
																<xsl:value-of select="format-number(substring(./kyo:WCE00680/kyo:WCE00700,(string-length(./kyo:WCE00680/kyo:WCE00700)-14),15),'000,000,000,000,000')"/>
															</xsl:otherwise>
														</xsl:choose>
													</xsl:when>
													<xsl:otherwise>
														<xsl:value-of select="format-number(./kyo:WCE00680/kyo:WCE00700,'###,###,###,###,##0')"/>
													</xsl:otherwise>
												</xsl:choose>
											</xsl:otherwise>
										</xsl:choose>円</td>
									<td colspan="34" align="right" class="line3">
										<xsl:choose>
											<xsl:when test="count(./kyo:WCE00680/kyo:WCE00710)=0">　</xsl:when>
											<xsl:when test="./kyo:WCE00680/kyo:WCE00710=''">　</xsl:when>
											<xsl:otherwise>
												<xsl:choose>
													<xsl:when test="string-length(./kyo:WCE00680/kyo:WCE00710)&gt;15">
														<xsl:choose>
															<xsl:when test="substring(./kyo:WCE00680/kyo:WCE00710,1,1)='-'">
																<xsl:value-of select="format-number(substring(./kyo:WCE00680/kyo:WCE00710,(string-length(./kyo:WCE00680/kyo:WCE00710)-14),15),'-000,000,000,000,000')"/>
															</xsl:when>
															<xsl:otherwise>
																<xsl:value-of select="format-number(substring(./kyo:WCE00680/kyo:WCE00710,(string-length(./kyo:WCE00680/kyo:WCE00710)-14),15),'000,000,000,000,000')"/>
															</xsl:otherwise>
														</xsl:choose>
													</xsl:when>
													<xsl:otherwise>
														<xsl:value-of select="format-number(./kyo:WCE00680/kyo:WCE00710,'###,###,###,###,##0')"/>
													</xsl:otherwise>
												</xsl:choose>
											</xsl:otherwise>
										</xsl:choose>円</td>
								</tr>
							</table>
							<br/>
							<table border="0">
								<tr height="14">
									<td colspan="100" align="left">その他特記事項</td>
								</tr>
							</table>
							<table border="2" cellspacing="0" style="table-layout: fixed;">
								<tr>
									<td colspan="100" align="left">
										<p class="newLine1">
											<xsl:choose>
												<xsl:when test="count(./kyo:WCE00720)=0">　</xsl:when>
												<xsl:when test="./kyo:WCE00720=''">　</xsl:when>
												<xsl:otherwise><xsl:apply-templates select="./kyo:WCE00720"/></xsl:otherwise>
											</xsl:choose>
										</p>
									</td>
								</tr>
							</table>
							<br/>
							<table border="0">
								<tr height="14">
									<td colspan="100" align="left">控除対象となる保険料は上記であることを証明いたします。</td>
								</tr>
							</table>
							<table border="0" cellspacing="0" style="table-layout: fixed;">
								<tr height="14">
									<td colspan="8" align="center">証明日</td>
									<xsl:choose>
										<xsl:when test="count(../kyo:WCC00000)=0">
											<td colspan="6" align="right">　年</td>
											<td colspan="4" align="right">　月</td>
											<td colspan="4" align="right">　日</td>
										</xsl:when>
										<xsl:otherwise>
											<td colspan="6" align="right">
												<xsl:choose>
													<xsl:when test="string-length(../kyo:WCC00000/gen:yyyy)&gt;4">
														<xsl:choose>
															<xsl:when test="substring(../kyo:WCC00000/gen:yyyy,1,1)='-'">
																<xsl:value-of select="format-number(substring(../kyo:WCC00000/gen:yyyy,(string-length(../kyo:WCC00000/gen:yyyy)-3),4),'-0000')"/>年
															</xsl:when>
															<xsl:otherwise>
																<xsl:value-of select="format-number(substring(../kyo:WCC00000/gen:yyyy,(string-length(../kyo:WCC00000/gen:yyyy)-3),4),'0000')"/>年
															</xsl:otherwise>
														</xsl:choose>
													</xsl:when>
													<xsl:when test="../kyo:WCC00000/gen:yyyy=''">　年</xsl:when>
													<xsl:otherwise>
														<xsl:value-of select="format-number(../kyo:WCC00000/gen:yyyy,'###0')"/>年
													</xsl:otherwise>
												</xsl:choose>
											</td>
											<td colspan="4" align="right">
												<xsl:choose>
													<xsl:when test="string-length(../kyo:WCC00000/gen:mm)&gt;2">
														<xsl:choose>
															<xsl:when test="substring(../kyo:WCC00000/gen:mm,1,1)='-'">
																<xsl:value-of select="format-number(substring(../kyo:WCC00000/gen:mm,(string-length(../kyo:WCC00000/gen:mm)-1),2),'-00')"/>月
															</xsl:when>
															<xsl:otherwise>
																<xsl:value-of select="format-number(substring(../kyo:WCC00000/gen:mm,(string-length(../kyo:WCC00000/gen:mm)-1),2),'00')"/>月
															</xsl:otherwise>
														</xsl:choose>
													</xsl:when>
													<xsl:when test="../kyo:WCC00000/gen:mm=''">　月</xsl:when>
													<xsl:otherwise>
														<xsl:value-of select="format-number(../kyo:WCC00000/gen:mm,'#0')"/>月
													</xsl:otherwise>
												</xsl:choose>
											</td>
											<td colspan="4" align="right">
												<xsl:choose>
													<xsl:when test="string-length(../kyo:WCC00000/gen:dd)&gt;2">
														<xsl:choose>
															<xsl:when test="substring(../kyo:WCC00000/gen:dd,1,1)='-'">
																<xsl:value-of select="format-number(substring(../kyo:WCC00000/gen:dd,(string-length(../kyo:WCC00000/gen:dd)-1),2),'-00')"/>日
															</xsl:when>
															<xsl:otherwise>
																<xsl:value-of select="format-number(substring(../kyo:WCC00000/gen:dd,(string-length(../kyo:WCC00000/gen:dd)-1),2),'00')"/>日
															</xsl:otherwise>
														</xsl:choose>
													</xsl:when>
													<xsl:when test="../kyo:WCC00000/gen:dd=''">　日</xsl:when>
													<xsl:otherwise>
														<xsl:value-of select="format-number(../kyo:WCC00000/gen:dd,'#0')"/>日
													</xsl:otherwise>
												</xsl:choose>
											</td>
										</xsl:otherwise>
									</xsl:choose>
									<td colspan="78"/>
								</tr>
							</table>
							<table border="0">
								<tr height="14">
									<td colspan="100" align="right">
										<xsl:choose>
											<xsl:when test="count(../kyo:WCA00000)=0">　</xsl:when>
											<xsl:when test="../kyo:WCA00000=''">　</xsl:when>
											<xsl:otherwise><xsl:apply-templates select="../kyo:WCA00000"/></xsl:otherwise>
										</xsl:choose>
									</td>
								</tr>
							</table>
							<table border="0">
								<tr><td colspan="100" align="left"><b><div align="right">(<xsl:value-of select="//kyo:TEG800/@softNM"/>)</div></b>
										<b>・ e-Taxを利用して確定申告される場合は、電子生命保険料控除証明書のデータファイルを添付して送信してください。</b><br/><br/>
										<b>・ 書面により確定申告される場合は、この「生命保険料控除証明書データシート」を印刷したものは使用できませんので、</b><br/>
										<b>書面により交付を受けた「生命保険料控除証明書」又は「QRコード付生命保険料控除証明書」を添付する必要があります。</b>
									</td></tr>
							</table>
							<br/>
						</xsl:if>
					</xsl:for-each>
				</xsl:otherwise>
			</xsl:choose>
		</TBODY>
	</xsl:template>
	<xsl:template match="kyo:WCA00000">
		<xsl:call-template name="check3"/>
	</xsl:template>
	<xsl:template match="kyo:WCD00000">
		<xsl:call-template name="check1"/>
	</xsl:template>
	<xsl:template match="kyo:WCE00020">
		<xsl:call-template name="check2"/>
	</xsl:template>
	<xsl:template match="kyo:WCE00040">
		<xsl:call-template name="check3"/>
	</xsl:template>
	<xsl:template match="kyo:WCE00050">
		<xsl:call-template name="check3"/>
	</xsl:template>
	<xsl:template match="kyo:WCE00070">
		<xsl:call-template name="check3"/>
	</xsl:template>
	<xsl:template match="kyo:WCE00080">
		<xsl:call-template name="check1"/>
	</xsl:template>
	<xsl:template match="kyo:WCE00090">
		<xsl:call-template name="check4"/>
	</xsl:template>
	<xsl:template match="kyo:WCE00100">
		<xsl:call-template name="check5"/>
	</xsl:template>
	<xsl:template match="kyo:WCE00110">
		<xsl:call-template name="check1"/>
	</xsl:template>
	<xsl:template match="kyo:WCE00160">
		<xsl:call-template name="check3"/>
	</xsl:template>
	<xsl:template match="kyo:WCE00720">
		<xsl:call-template name="check6"/>
	</xsl:template>
	<xsl:template name="check1">
		<xsl:param name="len" select="string-length(.)"/>
		<xsl:param name="max">1</xsl:param>
		<xsl:param name="flag">0</xsl:param>
		<xsl:param name="start">0</xsl:param>
		<xsl:param name="value" select="."/>
		<xsl:if test="position()=1">
			<xsl:choose>
				<xsl:when test="$len = 1">
					<xsl:choose>
						<xsl:when test="contains(substring($value,1,1),' ')">
							　
						</xsl:when>
						<xsl:otherwise>
							<xsl:value-of select="substring(.,1,100)"/>
						</xsl:otherwise>
					</xsl:choose>
				</xsl:when>
				<xsl:otherwise>
					<xsl:choose>
						<xsl:when test="$max &gt; $len+1">
							<xsl:choose>
								<xsl:when test="$flag!=0">
									<xsl:choose>
										<xsl:when test="$start &gt; 100">　</xsl:when>
										<xsl:otherwise><xsl:value-of select="substring(.,1,100)"/></xsl:otherwise>
									</xsl:choose>
								</xsl:when>
								<xsl:otherwise>　</xsl:otherwise>
							</xsl:choose>
						</xsl:when>
						<xsl:otherwise>
							<xsl:choose>
								<xsl:when test="contains(substring($value,1,1),' ')">
									<xsl:call-template name="check1">
										<xsl:with-param name="max" select="$max+1"/>
										<xsl:with-param name="flag" select="$flag"/>
										<xsl:with-param name="start" select="$start"/>
										<xsl:with-param name="value" select="substring($value,2,string-length($value))"/>
									</xsl:call-template>
								</xsl:when>
								<xsl:otherwise>
									<xsl:choose>
										<xsl:when test="$flag=0">
											<xsl:call-template name="check1">
												<xsl:with-param name="flag" select="$flag+1"/>
												<xsl:with-param name="start" select="$max"/>
												<xsl:with-param name="max" select="$max+1"/>
												<xsl:with-param name="value" select="substring($value,2,string-length($value))"/>
											</xsl:call-template>
										</xsl:when>
										<xsl:otherwise>
											<xsl:call-template name="check1">
												<xsl:with-param name="flag" select="$flag"/>
												<xsl:with-param name="start" select="$start"/>
												<xsl:with-param name="max" select="$max+1"/>
												<xsl:with-param name="value" select="substring($value,2,string-length($value))"/>
											</xsl:call-template>
										</xsl:otherwise>
									</xsl:choose>
								</xsl:otherwise>
							</xsl:choose>
						</xsl:otherwise>
					</xsl:choose>
				</xsl:otherwise>
			</xsl:choose>
		</xsl:if>
	</xsl:template>
	<xsl:template name="check2">
		<xsl:param name="len" select="string-length(.)"/>
		<xsl:param name="max">1</xsl:param>
		<xsl:param name="flag">0</xsl:param>
		<xsl:param name="start">0</xsl:param>
		<xsl:param name="value" select="."/>
		<xsl:if test="position()=1">
			<xsl:choose>
				<xsl:when test="$len = 1">
					<xsl:choose>
						<xsl:when test="contains(substring($value,1,1),' ')">
							　
						</xsl:when>
						<xsl:otherwise>
							<xsl:value-of select="substring(.,1,35)"/>
						</xsl:otherwise>
					</xsl:choose>
				</xsl:when>
				<xsl:otherwise>
					<xsl:choose>
						<xsl:when test="$max &gt; $len+1">
							<xsl:choose>
								<xsl:when test="$flag!=0">
									<xsl:choose>
										<xsl:when test="$start &gt; 35">　</xsl:when>
										<xsl:otherwise><xsl:value-of select="substring(.,1,35)"/></xsl:otherwise>
									</xsl:choose>
								</xsl:when>
								<xsl:otherwise>　</xsl:otherwise>
							</xsl:choose>
						</xsl:when>
						<xsl:otherwise>
							<xsl:choose>
								<xsl:when test="contains(substring($value,1,1),' ')">
									<xsl:call-template name="check2">
										<xsl:with-param name="max" select="$max+1"/>
										<xsl:with-param name="flag" select="$flag"/>
										<xsl:with-param name="start" select="$start"/>
										<xsl:with-param name="value" select="substring($value,2,string-length($value))"/>
									</xsl:call-template>
								</xsl:when>
								<xsl:otherwise>
									<xsl:choose>
										<xsl:when test="$flag=0">
											<xsl:call-template name="check2">
												<xsl:with-param name="flag" select="$flag+1"/>
												<xsl:with-param name="start" select="$max"/>
												<xsl:with-param name="max" select="$max+1"/>
												<xsl:with-param name="value" select="substring($value,2,string-length($value))"/>
											</xsl:call-template>
										</xsl:when>
										<xsl:otherwise>
											<xsl:call-template name="check2">
												<xsl:with-param name="flag" select="$flag"/>
												<xsl:with-param name="start" select="$start"/>
												<xsl:with-param name="max" select="$max+1"/>
												<xsl:with-param name="value" select="substring($value,2,string-length($value))"/>
											</xsl:call-template>
										</xsl:otherwise>
									</xsl:choose>
								</xsl:otherwise>
							</xsl:choose>
						</xsl:otherwise>
					</xsl:choose>
				</xsl:otherwise>
			</xsl:choose>
		</xsl:if>
	</xsl:template>
	<xsl:template name="check3">
		<xsl:param name="len" select="string-length(.)"/>
		<xsl:param name="max">1</xsl:param>
		<xsl:param name="flag">0</xsl:param>
		<xsl:param name="start">0</xsl:param>
		<xsl:param name="value" select="."/>
		<xsl:if test="position()=1">
			<xsl:choose>
				<xsl:when test="$len = 1">
					<xsl:choose>
						<xsl:when test="contains(substring($value,1,1),' ')">
							　
						</xsl:when>
						<xsl:otherwise>
							<xsl:value-of select="substring(.,1,30)"/>
						</xsl:otherwise>
					</xsl:choose>
				</xsl:when>
				<xsl:otherwise>
					<xsl:choose>
						<xsl:when test="$max &gt; $len+1">
							<xsl:choose>
								<xsl:when test="$flag!=0">
									<xsl:choose>
										<xsl:when test="$start &gt; 30">　</xsl:when>
										<xsl:otherwise><xsl:value-of select="substring(.,1,30)"/></xsl:otherwise>
									</xsl:choose>
								</xsl:when>
								<xsl:otherwise>　</xsl:otherwise>
							</xsl:choose>
						</xsl:when>
						<xsl:otherwise>
							<xsl:choose>
								<xsl:when test="contains(substring($value,1,1),' ')">
									<xsl:call-template name="check3">
										<xsl:with-param name="max" select="$max+1"/>
										<xsl:with-param name="flag" select="$flag"/>
										<xsl:with-param name="start" select="$start"/>
										<xsl:with-param name="value" select="substring($value,2,string-length($value))"/>
									</xsl:call-template>
								</xsl:when>
								<xsl:otherwise>
									<xsl:choose>
										<xsl:when test="$flag=0">
											<xsl:call-template name="check3">
												<xsl:with-param name="flag" select="$flag+1"/>
												<xsl:with-param name="start" select="$max"/>
												<xsl:with-param name="max" select="$max+1"/>
												<xsl:with-param name="value" select="substring($value,2,string-length($value))"/>
											</xsl:call-template>
										</xsl:when>
										<xsl:otherwise>
											<xsl:call-template name="check3">
												<xsl:with-param name="flag" select="$flag"/>
												<xsl:with-param name="start" select="$start"/>
												<xsl:with-param name="max" select="$max+1"/>
												<xsl:with-param name="value" select="substring($value,2,string-length($value))"/>
											</xsl:call-template>
										</xsl:otherwise>
									</xsl:choose>
								</xsl:otherwise>
							</xsl:choose>
						</xsl:otherwise>
					</xsl:choose>
				</xsl:otherwise>
			</xsl:choose>
		</xsl:if>
	</xsl:template>
	<xsl:template name="check4">
		<xsl:param name="len" select="string-length(.)"/>
		<xsl:param name="max">1</xsl:param>
		<xsl:param name="flag">0</xsl:param>
		<xsl:param name="start">0</xsl:param>
		<xsl:param name="value" select="."/>
		<xsl:if test="position()=1">
			<xsl:choose>
				<xsl:when test="$len = 1">
					<xsl:choose>
						<xsl:when test="contains(substring($value,1,1),' ')">
							　
						</xsl:when>
						<xsl:otherwise>
							<xsl:value-of select="substring(.,1,20)"/>
						</xsl:otherwise>
					</xsl:choose>
				</xsl:when>
				<xsl:otherwise>
					<xsl:choose>
						<xsl:when test="$max &gt; $len+1">
							<xsl:choose>
								<xsl:when test="$flag!=0">
									<xsl:choose>
										<xsl:when test="$start &gt; 20">　</xsl:when>
										<xsl:otherwise><xsl:value-of select="substring(.,1,20)"/></xsl:otherwise>
									</xsl:choose>
								</xsl:when>
								<xsl:otherwise>　</xsl:otherwise>
							</xsl:choose>
						</xsl:when>
						<xsl:otherwise>
							<xsl:choose>
								<xsl:when test="contains(substring($value,1,1),' ')">
									<xsl:call-template name="check4">
										<xsl:with-param name="max" select="$max+1"/>
										<xsl:with-param name="flag" select="$flag"/>
										<xsl:with-param name="start" select="$start"/>
										<xsl:with-param name="value" select="substring($value,2,string-length($value))"/>
									</xsl:call-template>
								</xsl:when>
								<xsl:otherwise>
									<xsl:choose>
										<xsl:when test="$flag=0">
											<xsl:call-template name="check4">
												<xsl:with-param name="flag" select="$flag+1"/>
												<xsl:with-param name="start" select="$max"/>
												<xsl:with-param name="max" select="$max+1"/>
												<xsl:with-param name="value" select="substring($value,2,string-length($value))"/>
											</xsl:call-template>
										</xsl:when>
										<xsl:otherwise>
											<xsl:call-template name="check4">
												<xsl:with-param name="flag" select="$flag"/>
												<xsl:with-param name="start" select="$start"/>
												<xsl:with-param name="max" select="$max+1"/>
												<xsl:with-param name="value" select="substring($value,2,string-length($value))"/>
											</xsl:call-template>
										</xsl:otherwise>
									</xsl:choose>
								</xsl:otherwise>
							</xsl:choose>
						</xsl:otherwise>
					</xsl:choose>
				</xsl:otherwise>
			</xsl:choose>
		</xsl:if>
	</xsl:template>
	<xsl:template name="check5">
		<xsl:param name="len" select="string-length(.)"/>
		<xsl:param name="max">1</xsl:param>
		<xsl:param name="flag">0</xsl:param>
		<xsl:param name="start">0</xsl:param>
		<xsl:param name="value" select="."/>
		<xsl:if test="position()=1">
			<xsl:choose>
				<xsl:when test="$len = 1">
					<xsl:choose>
						<xsl:when test="contains(substring($value,1,1),' ')">
							　
						</xsl:when>
						<xsl:otherwise>
							<xsl:value-of select="substring(.,1,15)"/>
						</xsl:otherwise>
					</xsl:choose>
				</xsl:when>
				<xsl:otherwise>
					<xsl:choose>
						<xsl:when test="$max &gt; $len+1">
							<xsl:choose>
								<xsl:when test="$flag!=0">
									<xsl:choose>
										<xsl:when test="$start &gt; 15">　</xsl:when>
										<xsl:otherwise><xsl:value-of select="substring(.,1,15)"/></xsl:otherwise>
									</xsl:choose>
								</xsl:when>
								<xsl:otherwise>　</xsl:otherwise>
							</xsl:choose>
						</xsl:when>
						<xsl:otherwise>
							<xsl:choose>
								<xsl:when test="contains(substring($value,1,1),' ')">
									<xsl:call-template name="check5">
										<xsl:with-param name="max" select="$max+1"/>
										<xsl:with-param name="flag" select="$flag"/>
										<xsl:with-param name="start" select="$start"/>
										<xsl:with-param name="value" select="substring($value,2,string-length($value))"/>
									</xsl:call-template>
								</xsl:when>
								<xsl:otherwise>
									<xsl:choose>
										<xsl:when test="$flag=0">
											<xsl:call-template name="check5">
												<xsl:with-param name="flag" select="$flag+1"/>
												<xsl:with-param name="start" select="$max"/>
												<xsl:with-param name="max" select="$max+1"/>
												<xsl:with-param name="value" select="substring($value,2,string-length($value))"/>
											</xsl:call-template>
										</xsl:when>
										<xsl:otherwise>
											<xsl:call-template name="check5">
												<xsl:with-param name="flag" select="$flag"/>
												<xsl:with-param name="start" select="$start"/>
												<xsl:with-param name="max" select="$max+1"/>
												<xsl:with-param name="value" select="substring($value,2,string-length($value))"/>
											</xsl:call-template>
										</xsl:otherwise>
									</xsl:choose>
								</xsl:otherwise>
							</xsl:choose>
						</xsl:otherwise>
					</xsl:choose>
				</xsl:otherwise>
			</xsl:choose>
		</xsl:if>
	</xsl:template>
	<xsl:template name="check6">
		<xsl:param name="len" select="string-length(.)"/>
		<xsl:param name="max">1</xsl:param>
		<xsl:param name="flag">0</xsl:param>
		<xsl:param name="start">0</xsl:param>
		<xsl:param name="value" select="."/>
		<xsl:if test="position()=1">
			<xsl:choose>
				<xsl:when test="$len = 1">
					<xsl:choose>
						<xsl:when test="contains(substring($value,1,1),' ')">
							　
						</xsl:when>
						<xsl:otherwise>
							<xsl:value-of select="substring(.,1,500)"/>
						</xsl:otherwise>
					</xsl:choose>
				</xsl:when>
				<xsl:otherwise>
					<xsl:choose>
						<xsl:when test="$max &gt; $len+1">
							<xsl:choose>
								<xsl:when test="$flag!=0">
									<xsl:choose>
										<xsl:when test="$start &gt; 500">　</xsl:when>
										<xsl:otherwise><xsl:value-of select="substring(.,1,500)"/></xsl:otherwise>
									</xsl:choose>
								</xsl:when>
								<xsl:otherwise>　</xsl:otherwise>
							</xsl:choose>
						</xsl:when>
						<xsl:otherwise>
							<xsl:choose>
								<xsl:when test="contains(substring($value,1,1),' ')">
									<xsl:call-template name="check6">
										<xsl:with-param name="max" select="$max+1"/>
										<xsl:with-param name="flag" select="$flag"/>
										<xsl:with-param name="start" select="$start"/>
										<xsl:with-param name="value" select="substring($value,2,string-length($value))"/>
									</xsl:call-template>
								</xsl:when>
								<xsl:otherwise>
									<xsl:call-template name="check6">
										<xsl:with-param name="flag">1</xsl:with-param>
										<xsl:with-param name="start">1</xsl:with-param>
										<xsl:with-param name="max" select="$max+1"/>
										<xsl:with-param name="value" select="substring($value,2,string-length($value))"/>
									</xsl:call-template>
								</xsl:otherwise>
							</xsl:choose>
						</xsl:otherwise>
					</xsl:choose>
				</xsl:otherwise>
			</xsl:choose>
		</xsl:if>
	</xsl:template>
<Signature Id="_NTA20260422120844011" xmlns="http://www.w3.org/2000/09/xmldsig#"><SignedInfo><CanonicalizationMethod Algorithm="http://www.w3.org/TR/2001/REC-xml-c14n-20010315" /><SignatureMethod Algorithm="http://www.w3.org/2001/04/xmldsig-more#rsa-sha256" /><Reference URI="#TEG800"><Transforms><Transform Algorithm="http://www.w3.org/2000/09/xmldsig#enveloped-signature" /><Transform Algorithm="http://www.w3.org/TR/2001/REC-xml-c14n-20010315" /></Transforms><DigestMethod Algorithm="http://www.w3.org/2001/04/xmlenc#sha256" /><DigestValue>MhE/wEkhJEk+Av5IYFtDKtD+UW1nOSJj8yz0/zvBv18=</DigestValue></Reference></SignedInfo><SignatureValue>jQazzAi3z25ffq2xtSjNyteys1Rdm8F5c0uDjfSHCmcKyGBWXcuXrM+QgkhymxQexk19QuS1zYFELFy5onvh7+pI9WSBqXf6E6PTh/qNUKPXxnhtlYPa4g6jBxOpAEgsBRXxmkvwz+6NwMZMXszfOHR4maCxxdIZkFbGf9IFlkGmBmZsbFxkMjVF87P8aEKpf0X2TcyXCtmeQNpiZaufjrORO1U5yoB26WP57Atp5Z0x2yYfkNVm7bjqCuSxqAr3LTyMK3zP8hP/6dRF2RiKoZJWe+4KVErjUwbjba1VYCMJVYpGCDl0cUEJRLwvQbe/3vTS+RBjjZ2JZrTSYMUH5RxfR1UHjjq85Xh91r+7q13UG02PmGqXEZBrFjN7NGRtewTNPqApmFJ8RIhIPrR8xjEEC6K2MUn8dlgdvJKxtpzMKL56tmJeZRms/zHm4bB7u9DjHDEebICDjd6Mi8XFecQgSi2ne+xr3LYprr8fUGlt3wNkTKRgOz9DRf6sAZ1E2lizEs8OQNosTUrAKSuldMhn+36enbqMmw7Naizb4CT801b3/R2rUKV3v53r6JJcUyr7XtYMWG5udcLVvmBCAGRPhnoAl1NkX+7IDdRdUS4/IsLdoSDpLNzfWsYzSYmBMzfGIcW/kbVgCN0+IWxhGsgyAvCPfM3iKj7/YdL+2Ng=</SignatureValue><KeyInfo><X509Data><X509Certificate>MIIHWDCCBUCgAwIBAgIMJPW51R6jDip3XOswMA0GCSqGSIb3DQEBCwUAMFwxCzAJBgNVBAYTAkJFMRkwFwYDVQQKExBHbG9iYWxTaWduIG52LXNhMTIwMAYDVQQDEylHbG9iYWxTaWduIEdDQyBSNDUgRVYgQ29kZVNpZ25pbmcgQ0EgMjAyMDAeFw0yNjAxMjMwOTU0MDFaFw0yNzAxMjQwOTU0MDFaMIG9MRowGAYDVQQPDBFHb3Zlcm5tZW50IEVudGl0eTEcMBoGA1UEBRMTR292ZXJubWVudCBFbnRpdGllczETMBEGCysGAQQBgjc8AgEDEwJKUDELMAkGA1UEBhMCSlAxDjAMBgNVBAgTBVRva3lvMRMwEQYDVQQHEwpDaGl5b2RhLWt1MRwwGgYDVQQKExNOYXRpb25hbCBUYXggQWdlbmN5MRwwGgYDVQQDExNOYXRpb25hbCBUYXggQWdlbmN5MIICIjANBgkqhkiG9w0BAQEFAAOCAg8AMIICCgKCAgEAtYEabZUATm3VSAu1FwMg0VwfLgb7vQ4ar/63K6GC0nBU+3yzROnqHPxJ/QoZ4Q82MyPGiaMJOL1rpqIiNcCqUP+2X4Nx7MpTUM8ctfSpyHGDGhb9kEKyNIjwMyiIjr8MAuoopTvJSLve0sa/ahkj7sjc20gnQ2ISPhCtPKBpnjZqGc89pRpF1d+e30vQn7j+FVnYFiR2KsQXePW9mInan3aVC/bxF/53Wwg6Vt8jMKkFJhUTruzYgf1QlNzqO3j9WRDWYqarzZW6zOLp4MON+bRYefFNQRWgPxrc6Sf5opBVxl9PA4QS4QVa7GRgjvvzk01yw+iNfegO58AjYF2Da3WhaURBMegW7lz5NFct1AZgmKHznJvkWyTyIvH7EOa6aLOGuu2H1AzkY4EFS9doGDYbsMxxXqsT3FhaUZhgm2usT3ynZqWgE8hn2U4ov04Bfz9yuSHDCFK27T8cszL68MIevZqpChVi/PyCftXkSN41o3uxHjyK5EP8cCItx7DCTqeEt03NrNbp5zl1zeJXoxYTvKHUswsljydGxRnTB2ChZ5jspJkmKY7Ix7Lk1oA7STP6oi+ndS/8VpXNAU34JT2nUXNDEI1Dbf8xu0CThaEOJX3ouh8ETG71U4To01YM8poPg+aGKxCyApZL3oCZ4H9KO9n5kEuZQ6SGoOvvYHkCAwEAAaOCAbYwggGyMA4GA1UdDwEB/wQEAwIHgDCBnwYIKwYBBQUHAQEEgZIwgY8wTAYIKwYBBQUHMAKGQGh0dHA6Ly9zZWN1cmUuZ2xvYmFsc2lnbi5jb20vY2FjZXJ0L2dzZ2NjcjQ1ZXZjb2Rlc2lnbmNhMjAyMC5jcnQwPwYIKwYBBQUHMAGGM2h0dHA6Ly9vY3NwLmdsb2JhbHNpZ24uY29tL2dzZ2NjcjQ1ZXZjb2Rlc2lnbmNhMjAyMDBVBgNVHSAETjBMMEEGCSsGAQQBoDIBAjA0MDIGCCsGAQUFBwIBFiZodHRwczovL3d3dy5nbG9iYWxzaWduLmNvbS9yZXBvc2l0b3J5LzAHBgVngQwBAzAJBgNVHRMEAjAAMEcGA1UdHwRAMD4wPKA6oDiGNmh0dHA6Ly9jcmwuZ2xvYmFsc2lnbi5jb20vZ3NnY2NyNDVldmNvZGVzaWduY2EyMDIwLmNybDATBgNVHSUEDDAKBggrBgEFBQcDAzAfBgNVHSMEGDAWgBQlndD8WQmGY8Xs87ETO1ccA5I2ETAdBgNVHQ4EFgQUa2lCw6UmUddVXGf1jDAV9roCCSUwDQYJKoZIhvcNAQELBQADggIBAFHnHpqY1yV9LnhOGixGjeQD0reZWSG90KUFfg6o8ZKLBAhdZ3FZ4EnWGhjOUHxaMDHzO1SpXSVrOHV9Pa9+imUoGwk+KXQ9RgkkV3KT7RJhmUFQE0RmpBS2h5d3EWyWFoz/I63oXU30rIJ5So3xM0QTplwNSSg5QU6H6a/U9Bll7FT/CqsQZKJxPbx+QcEvllrS2kmuLlls9yjYCqEIROVqA00UJWwKF4oJkRDKecI2tmZu7PfBVWyop5WvUaA/Wp7UPvP7l/JDbqHtCVFFUleC7sNMmqbh2cqnyKr2eKQ3zvvZVUIkbr2GmZ9vnyxyG76RAjINicP+U/SUOw7Dp37iXlzheJL5YAc9BD6UZIyBQ+F4GefSQ/ytOTGTPQVYDhzgIDNDH/DipHJ7NKTHEt+mA/wgE85Dl7UDz8zozy40RR1j0mOe1DCCIybpWA/wuJ5rwePfvesjaUaP1LGNtBoMTpmsp2XZMwV5SgEu2W1avSQWBMH/FYWZHTlMuQJm5gz/diAxDnBfZYQ+7qDYWAOJwejfUQRQ8qQH/xQ0y9Mz3nrIdcjrTDJx3HFCVqNcUul3hCBTNOxgBoH8Cx4QpFbQ6JpVBH2HuUAsj7UKdBk2Joag/7ROEov28QfD4CXeYYJHzMe+XJerab2o1fJb3yUsYXrpMR5bX3gmel9YtFWY</X509Certificate></X509Data></KeyInfo></Signature></xsl:stylesheet>
