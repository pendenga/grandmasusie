<?php

include_once 'module.php';

class StatForm extends Module {

	private $colorList = array("0xFF0000","0x00FF00","0x0000FF","0xFF6600","0x42FF8E","0x6600FF","0xFFFF00","0x00FFFF","0xFF00FF","0x66FF00","0x0066FF","0xFF0066","0xCC0000","0x00CC00","0x0000CC");

	private function colors_default() {
		return <<<EOD
     <chartColors docBorder="0xcccccc" docBg1="0xfafafa" docBg2="0xdfdfdf" xText="0x33485c" yText="0x33485c" title="0x990033" misc="0x999999" altBorder="0xdfdfdf" altBg="0xeeeeee" altText="0x64798d" graphBorder="0x758a9e" graphBg1="0xb0cbe2" graphBg2="0xf9f9f9" graphLines="0xffffff" graphText="0x990033" graphTextShadow="0xf9f9f9" barBorder="0xeeeeee" barBorderHilite="0x990033" legendBorder="0xdfdfdf" legendBg1="0xf9f9f9" legendBg2="0xf9f9f9" legendText="0x444444" legendColorKeyBorder="0x777777" scrollBar="0xdfdfdf" scrollBarBorder="0xfafafa" scrollBarTrack="0xeeeeee" scrollBarTrackBorder="0xcccccc"/>
EOD;
	}
	
	private function colors_pie() {
		return <<<EOD
     <chartColors  docBorder="0xffffff"  docBg1="0xffffff"  docBg2="0xffffff"  title="0x333333"  subtitle="0x666666"  misc="0x999999"  altBorder="0xffffff"  altBg="0xffffff"  altText="0x666666"  graphText="0x33485c"  graphTextShadow="0xf9f9f9"  pieBorder="0xffffff"  pieBorderHilite="0x333333"  legendBorder="0xffffff"  legendBg1="0xffffff"  legendBg2="0xffffff"  legendText="0x444444"  legendColorKeyBorder="0x777777"  scrollBar="0xdfdfdf"  scrollBarBorder="0xfafafa"  scrollBarTrack="0xeeeeee"  scrollBarTrackBorder="0xcccccc"  />
EOD;
	}

	private function format_xData($bar, $sum, $prefix='', $suffix='') {
		foreach ($bar as $year=>$bars) {
			$dataRows .= "\t\t<dataRow title=\"{$year}\" endLabel=\"{$sum[$year]}\">\n";
			$dataRows .= implode("\n", $bars);
			$dataRows .= "\n\t\t</dataRow>\n";
		}
		$numCols = 14;
		if (count($sum)>0) {
			asort($sum);
			$highCol = array_pop($sum);
			$highCol = ceil($highCol/100)*100;
		} else {
			$highCol = 1;
		}
		return <<<EOD
	     <xData length="20">\n{$dataRows}</xData>
	     <yData min="0" max="{$highCol}" length="10" prefix="{$prefix}" suffix="{$suffix}" kDelim="," defaultAltText="Rollover a bar for details."/>
EOD;
	}

	private function graph_info($info) {
		return <<<EOD
     <graphInfo>
          <![CDATA[{$info}]]>
     </graphInfo>
EOD;
	}

	private function legend_single($label='Comments', $color='ff9900') {
		return <<<EOD
     <colorLegend status="on">
          <mapping id="1" name="{$label}" color="0x{$color}"/>
     </colorLegend>
EOD;
	}

	private function legend_monthYear() {
		return <<<EOD
     <colorLegend status="on">
          <mapping id="1" name="January" color="0xffffcc"/>
          <mapping id="2" name="February" color="0xffeba3"/>
          <mapping id="3" name="March" color="0xffd980"/>
          <mapping id="4" name="April" color="0xffc65a"/>
          <mapping id="5" name="May" color="0xffb83d"/>
          <mapping id="6" name="June" color="0xffa71c"/>
          <mapping id="7" name="July" color="0xff9900"/>
          <mapping id="8" name="August" color="0xf37600"/>
          <mapping id="9" name="September" color="0xea5a00"/>
          <mapping id="10" name="October" color="0xe13e00"/>
          <mapping id="11" name="November" color="0xd71f00"/>
          <mapping id="12" name="December" color="0xcc0000"/>
     </colorLegend>
EOD;
	}

	private function getCommentsByMonth($user_id='') {
		if ($user_id!='') {
			// photo comments only
			// $years = $this->db->do_sql("SELECT count(comment_id) cCount, YEAR(updated_dt) cYear, MONTH(updated_dt) cMonth FROM photo_comment WHERE user_id={$user_id} GROUP BY YEAR(updated_dt), MONTH(updated_dt) ORDER BY YEAR(updated_dt), MONTH(updated_dt)");

			// aggregate photo comments and blog commments
			$years = $this->db->do_sql("SELECT COUNT(comment_id) cCount, YEAR(updated_dt) cYear, MONTH(updated_dt) cMonth FROM (SELECT comment_id, updated_dt FROM photo_comment WHERE user_id={$user_id} UNION SELECT comment_id, updated_dt FROM blog_comment WHERE user_id={$user_id}) a GROUP BY YEAR(updated_dt), MONTH(updated_dt) ORDER BY YEAR(updated_dt), MONTH(updated_dt)");
		} else {
			// photo comments only
			// $years = $this->db->do_sql("SELECT count(comment_id) cCount, YEAR(updated_dt) cYear, MONTH(updated_dt) cMonth FROM photo_comment GROUP BY YEAR(updated_dt), MONTH(updated_dt) ORDER BY YEAR(updated_dt), MONTH(updated_dt)");

			// aggregate photo comments and blog commments
			$years = $this->db->do_sql("SELECT COUNT(comment_id) cCount, YEAR(updated_dt) cYear, MONTH(updated_dt) cMonth FROM (SELECT comment_id, updated_dt FROM photo_comment UNION SELECT comment_id, updated_dt FROM blog_comment) a GROUP BY YEAR(updated_dt), MONTH(updated_dt) ORDER BY YEAR(updated_dt), MONTH(updated_dt)");
		}
		$bar = array();
		$rowPart = '';
		$curYear = false;
		foreach ($years as $month) {
			if ($month['cYear']!=$curYear) {
				$bar[$month['cYear']][] = "\t\t\t<bar id=\"{$month['cMonth']}\" totalSize=\"{$month['cCount']}\" altText=\"{$month['cMonth']}/{$month['cYear']}: {$month['cCount']} posted\" url=\"\"/>";
				$sum[$month['cYear']] += intval($month['cCount']);
			}
		}
		return $this->format_xData($bar, $sum, '', ' comments');
	}

	private function getPhotosByMonth($user_id='') {
		if ($user_id!='') {
			$years = $this->db->do_sql("SELECT count(photo_id) pCount, YEAR(uploaded_dt) pYear, MONTH(uploaded_dt) pMonth FROM photo WHERE user_id={$user_id} GROUP BY YEAR(uploaded_dt), MONTH(uploaded_dt) ORDER BY YEAR(uploaded_dt), MONTH(uploaded_dt)");
		} else {
			$years = $this->db->do_sql("SELECT count(photo_id) pCount, YEAR(uploaded_dt) pYear, MONTH(uploaded_dt) pMonth FROM photo GROUP BY YEAR(uploaded_dt), MONTH(uploaded_dt) ORDER BY YEAR(uploaded_dt), MONTH(uploaded_dt)");
		}
		$bar = array();
		$rowPart = '';
		$curYear = false;
		foreach ($years as $month) {
			if ($month['pYear']!=$curYear) {
				$bar[$month['pYear']][] = "\t\t\t<bar id=\"{$month['pMonth']}\" totalSize=\"{$month['pCount']}\" altText=\"{$month['pMonth']}/{$month['pYear']}: {$month['pCount']} uploaded\" url=\"/report/uploads/{$month['pYear']}/{$month['pMonth']}/\"/>";
				$sum[$month['pYear']] += intval($month['pCount']);
			}
		}
		return $this->format_xData($bar, $sum, '', ' photos');
	}

	private function getPhotosByUser($year='', $month='') {
		if ($month!='' && $year!='') {
			$users = $this->db->do_sql("SELECT count(p.photo_id) pCount, u.user_id, u.first_name, u.last_name FROM photo p INNER JOIN users u ON u.user_id=p.user_id WHERE MONTH(uploaded_dt)={$month} AND YEAR(uploaded_dt)={$year} GROUP BY u.user_id ORDER BY count(p.photo_id) DESC");
		} else {
			$users = $this->db->do_sql("SELECT count(p.photo_id) pCount, u.user_id, u.first_name, u.last_name FROM photo p INNER JOIN users u ON u.user_id=p.user_id GROUP BY u.user_id ORDER BY count(p.photo_id) DESC");
		}

		$pie = array();
		$wedgeLimit = 8;
		foreach ($users as $index=>$user) {
			if ($index < $wedgeLimit) {
				$pie[$index] = array('first_name'=>$user['first_name'], 'last_name'=>$user['last_name'], 'uploads'=>$user['pCount']);
			} else {
				$pie[$wedgeLimit]['first_name'] = 'Other';
				$pie[$wedgeLimit]['last_name'] = '';
				$pie[$wedgeLimit]['uploads'] += intval($user['pCount']);
			}
		}

		foreach ($pie as $index=>$slice) {
			$wedges .= <<<EOD
			<wedge title="{$slice['first_name']} {$slice['last_name']}" value="{$slice['uploads']}" color="{$this->colorList[$index]}" labelText="{$slice['first_name']}: {$slice['uploads']}" altText="{$slice['first_name']} uploaded {$slice['uploads']} photos in {$month}/{$year}"/>

EOD;
		}

		return <<<EOD
			<pie defaultAltText="Rollover a wedge for details." legendStatus="on">
			{$wedges}
			</pie>
EOD;
	}

	private function getCommentsByPhoto($user_id='') {
		$user_id = ($user_id=='') ? -1 : $user_id;
		$comments = $this->db->do_sql("call photoCommentHistogram({$user_id})");
		$minc = $comments[0]['commentCount'];
		foreach ($comments as $comm) {
			$cdata[ceil($comm['commentCount']/2)] += $comm['photoCount'];
			$maxc = $comm['commentCount'];
			$high = max($high, $comm['photoCount']);
		}
		
		/*$last = end($comments);
		$maxc = $last['commentCount'];
		*/
		$high = ceil($high/10)*10;
		$xlen = ceil($maxc/2);
		for ($i=$minc; $i<=$xlen; $i++) {
			$i2 = $i*2;
			$cdata[$i] = ($cdata[$i]) ? $cdata[$i] : 0;
			$clbl = ($cdata[$i]==1) ? 'photo' : 'photos';
			$lbor = ($i2>0) ? ($i2-1)." or " : '';
			$bars .= <<<EOD
		<dataRow title="{$i2}" endLabel="{$cdata[$i]}">
			<bar id="1" totalSize="{$cdata[$i]}" altText="{$cdata[$i]} {$clbl} with {$lbor}{$i2} comments" url=""/>
		</dataRow>\n
EOD;
		}

		$output = <<<EOD
		<xData length="{$xlen}" defaultAltText="Rollover a bar for details.">
\n		$bars
		</xData>
		<yData min="0" max="{$high}" length="10" prefix="" suffix=" photos" kDelim="," defaultAltText="Rollover a bar for details."/>
EOD;
		return $output;
	}

	function data_commentsPerPhoto($user_id='') {
		$g_info = $this->graph_info("Graph shows comments posted per photo.");
		$legend = $this->legend_single('Comments','ff9900');
		$colors = $this->colors_default();
		$g_data = $this->getCommentsByPhoto($user_id);
		return <<<EOD
<graphData title="Comments per Photo on GrandmaSusie.com">
{$g_data}
{$legend}
{$g_info}
{$colors}
</graphData>
EOD;
	}

	function data_commentsByYear($user_id='') {
		$g_info = $this->graph_info("Date range is Nov-2001 to Present<br/>Graph shows total number of comments posted in each month of each year.");
		$legend = $this->legend_monthYear();
		$colors = $this->colors_default();
		$g_data = $this->getCommentsByMonth($user_id);
		return <<<EOD
<graphData title="Photo and Blog Comments by Month/Year on GrandmaSusie.com">
{$g_data}
{$legend}
{$g_info}
{$colors}
</graphData>
EOD;
	}

	function data_photosByYear($user_id='') {
		$g_info = $this->graph_info("Date range is Nov-2001 to Present<br/>Graph shows total number of photos posted in each month of each year.");
		$legend = $this->legend_monthYear();
		$colors = $this->colors_default();
		$g_data = $this->getPhotosByMonth($user_id);
		return <<<EOD
<graphData title="Photo Uploads by Month/Year on GrandmaSusie.com">
{$g_data}
{$legend}
{$g_info}
{$colors}
</graphData>
EOD;
	}

	function data_photosByUser($year='', $month='') {
		$g_data = $this->getPhotosByUser($year, $month);
		$g_info = "<graphInfo><![CDATA[]]></graphInfo>";
		$colors = $this->colors_default();
		return <<<EOD
<graphData title="Photo Uploads by Month/Year on GrandmaSusie.com">
{$g_data}
{$g_info}
{$colors}
</graphData>
EOD;
	}

	function posterOptions($active) {
		$users = $this->db->do_sql("SELECT u.user_id, u.first_name, u.last_name FROM users u WHERE u.user_id IN (SELECT user_id FROM photo GROUP BY user_id) OR u.user_id IN (SELECT user_id FROM photo_comment GROUP BY user_id) ORDER BY u.last_name, u.first_name");
		$selected[$active] = 'selected="selected"';
		foreach ($users as $user) {
			$options .= "<option value=\"{$user['user_id']}\" {$selected[$user['user_id']]}>{$user['first_name']} {$user['last_name']}</option>\n";
		}
		return $options;
	}
}

?>