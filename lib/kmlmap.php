<?php

class KmlMap {
	public $fh;
	public $finishStr = '';
	public $userAuth = '';
	public $folder = array();
	private $finished = false;
	private $filename = '';

	function __construct($filename, $name) {
		// check for directory
		if (!is_dir(dirname($filename)))
			mkdir(dirname($filename), 0755, true);
		$this->fh = fopen($filename, "w");
		$this->finishStr = "</Document></kml>";
		$this->filename = $filename;
		$output = <<<EOD
<kml xmlns="http://earth.google.com/kml/2.1">
<Document>
	<name>{$name}</name>

EOD;
		fwrite($this->fh, $output);
		fclose($this->fh);
		$this->fh = fopen($filename, "a");
		$this->addPageStyle('star', 'http://maps.google.com/mapfiles/kml/shapes/star.png');
	}

	function addPageStyle($name, $icon) {
		$output = <<<EOD
	<StyleMap id="m_{$name}">
		<Pair><key>normal</key><styleUrl>#s_{$name}</styleUrl></Pair>
		<Pair><key>highlight</key><styleUrl>#s_{$name}_em</styleUrl></Pair>
	</StyleMap>
	<Style id="s_{$name}_em">
		<IconStyle><scale>1.3</scale><Icon><href>{$icon}</href></Icon></IconStyle>
		<LabelStyle><scale>0.85</scale></LabelStyle>
		<ListStyle><ItemIcon><href>{$icon}</href></ItemIcon></ListStyle>
	</Style>
	<Style id="s_{$name}">
		<IconStyle><scale>1.1</scale><Icon><href>{$icon}</href></Icon></IconStyle>
		<LabelStyle><scale>0.75</scale></LabelStyle>
		<ListStyle><ItemIcon><href>{$icon}</href></ItemIcon></ListStyle>
	</Style>

EOD;
		fwrite($this->fh, $output);
	}

	function addPoint($name, $latitude, $longitude, $altitude=100, $description='') {
		$point_id = rand();
		$output = <<<EOD
		<Placemark id="point">
			<name>{$name}</name>
			<Snippet maxLines="2"></Snippet>
			<description><![CDATA[{$description}]]></description>
			<LookAt>
				<longitude>{$longitude}</longitude>
				<latitude>{$latitude}</latitude>
				<altitude>{$altitude}</altitude>
				<range>500</range>
				<tilt>70</tilt>
				<heading>0</heading>
			</LookAt>
			<styleUrl>#m_star</styleUrl>
			<Point id="{$point_id}">
				<extrude>1</extrude>
				<altitudeMode>relativeToGround</altitudeMode>
				<coordinates>{$longitude},{$latitude},{$altitude}</coordinates>
			</Point>
		</Placemark>

EOD;
		fwrite($this->fh, $output);
	}

	function addPolygon($name, $coords, $description='', $fillColor="66cccccc", $lineColor="66cccccc") {
		$output = <<<EOD
		<Placemark id="polygon">
			<name>{$name}</name>
			<Snippet maxLines="2"></Snippet>
			<description><![CDATA[{$description}]]></description>
			<Style>
				<LineStyle><color>{$lineColor}</color></LineStyle>
				<PolyStyle><color>{$fillColor}</color><fill>1</fill></PolyStyle>
			</Style>
			<Polygon>
				<extrude>1</extrude>
				<altitudeMode>relativeToGround</altitudeMode>
				<outerBoundaryIs><LinearRing><coordinates>{$coords}</coordinates></LinearRing></outerBoundaryIs>
			</Polygon>
		</Placemark>

EOD;
		fwrite($this->fh, $output);
	}

	function folderOpen($name) {
		$this->folder[] = $name;
		$pad = str_repeat("\t", count($this->folder));
		fwrite($this->fh, "{$pad}<Folder><name>{$name}</name>\n");
	}

	function folderClose() {
		$pad = str_repeat("\t", count($this->folder));
		if (count($this->folder)>0) {
			array_pop($this->folder);
			fwrite($this->fh, "{$pad}</Folder>\n");
			return true;
		} else {
			return false;
		}
	}
	
	function finish() {
		while($this->folderClose()) {}
		fwrite($this->fh, $this->finishStr);
		fclose($this->fh);
		$this->finished = true;
	}

	function download() {
		if (!$this->finished) {
			$this->finish();
		}

		$output = file_get_contents($this->filename);
		header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
		header("content-type: application/vnd.google-earth.kml kml");
		header('Content-disposition: inline; filename="output.kml"');
		header("Content-Length: ".strlen($output));
		echo $output;
		return true;
	}
}

?>