<?php

/**
 * Defines operations to take place on a single photo.  Can load the class with
 * an array of details on a photo or with the photo id.
 */
class Photo {
    const CONFIG_FILE = '/../conf/config.xml';

    public $img;
	public $editable = false;
	protected $db;
	protected $famu;
    private $photo_root;
    private $default_img = array('farm_id'=>1, 'server_id'=>8, 'ext'=>'jpg', 'complete'=>0, 'photo_uid'=>'notavailable', 'orig_size'=>0);
	private $convert_app = '/usr/bin/convert';

	function __construct(DBObject &$db, SiteUser &$famu, $img=array()) {
		$this->db = $db;
		$this->famu = $famu;
		$this->img = array_merge($this->default_img, $img);

        $xml = simplexml_load_file(realpath(dirname(__FILE__) . self::CONFIG_FILE));
        $this->photo_root = trim($xml->photo);

		// editable if owner in user list
		$this->editable = (in_array($this->img['user_id'], $this->famu->switch_list));

		// Do some of this stuff that's supposed to happen when the photo gets uploaded.
		if ($this->img['photo_id']!='' && $this->img['complete']==0) {
			$this->save();
		}
	}

	function loadFromDB () {
		$db_photo = $this->db->do_sql("SELECT * FROM photo WHERE photo_uid='{$this->img['photo_uid']}'");
		$this->img = array_merge($db_photo[0], $this->img);
	}

	function newPhoto($new_photo_id, $server_id=2) {
		// insert this into the database
		$this->db->do_sql("INSERT INTO photo (user_id, photo_uid, server_id) VALUES ({$this->famu->active_id}, '{$new_photo_id}', {$server_id})");
	}

	/**
	 * Tries to get the date taken from the exif information
	 */
	function getDateAndType() {
		if ($this->img['mime']=='image/jpeg' || $this->img['ext']=='jpg') {
			GTools::logOutput("trying to open ".$this->getFile_original());
			$exif = exif_read_data($this->getFile_original());
			if (strtotime($exif['DateTimeOriginal'])) {
				$this->img['take_dt'] = date('Y-m-d H:i:s', strtotime($exif['DateTimeOriginal']));
				$this->img['take_exif'] = 1;

			} elseif (preg_match('/(\d+):(\d+):(\d+) (\d+:\d+:\d+)/', $exif['DateTimeOriginal'], $matches)==1) {
				$this->img['take_dt'] = "{$matches[1]}-{$matches[2]}-{$matches[3]} {$matches[4]}";
				$this->img['take_exif'] = 1;

			} elseif (strtotime($exif['FileDateTime'])) {
				$this->img['take_dt'] = date('Y-m-d H:i:s', strtotime($exif['FileDateTime']));
				$this->img['take_exif'] = 1;

			} elseif (preg_match('/(\d+):(\d+):(\d+) (\d+:\d+:\d+)/', $exif['FileDateTime'], $matches)==1) {
				$this->img['take_dt'] = "{$matches[1]}-{$matches[2]}-{$matches[3]} {$matches[4]}";
				$this->img['take_exif'] = 1;
			}
			$this->img['mime'] = $exif['MimeType'];
		}
	}

	function getFile_medium() {
		return "{$this->photo_root}{$this->img['server_id']}/{$this->img['photo_uid']}.{$this->img['ext']}";
	}
	function getFile_original() {
		return "{$this->photo_root}{$this->img['server_id']}/{$this->img['photo_uid']}_o.{$this->img['ext']}";
	}
	function getFile_small() {
		return "{$this->photo_root}{$this->img['server_id']}/{$this->img['photo_uid']}_m.{$this->img['ext']}";
	}
	function getFile_square() {
		return "{$this->photo_root}{$this->img['server_id']}/{$this->img['photo_uid']}_s.{$this->img['ext']}";
	}
	function getFile_thumbnail() {
		return "{$this->photo_root}{$this->img['server_id']}/{$this->img['photo_uid']}_t.{$this->img['ext']}";
	}

	// according to flickr standard, this is 500x375
	function getUrl_medium() {
		return "http://{$_SERVER['HTTP_HOST']}/static/{$this->img['server_id']}/{$this->img['photo_uid']}.{$this->img['ext']}";
	}
	// according to flickr standard, size is arbitrary
	function getUrl_original() {
		return "http://{$_SERVER['HTTP_HOST']}/static/{$this->img['server_id']}/{$this->img['photo_uid']}_o.{$this->img['ext']}";
	}
	// according to flickr standard, this is 240x180
	function getUrl_small() {
		return "http://{$_SERVER['HTTP_HOST']}/static/{$this->img['server_id']}/{$this->img['photo_uid']}_m.{$this->img['ext']}";
	}
	// according to flickr standard, this is 75x75
	function getUrl_square() {
		return "http://{$_SERVER['HTTP_HOST']}/static/{$this->img['server_id']}/{$this->img['photo_uid']}_s.{$this->img['ext']}";
	}
	// according to flickr standard, this is 100x75
	function getUrl_thumbnail() {
		return "http://{$_SERVER['HTTP_HOST']}/static/{$this->img['server_id']}/{$this->img['photo_uid']}_t.{$this->img['ext']}";
	}

	function addUserFeatured($user_id) {
		if ($user_id != '') {
			$this->db->do_sql("REPLACE INTO photo_featuring (photo_id, user_id) VALUES ({$this->img['photo_id']}, {$user_id})");
			return true;
		}
		return false;
	}

	function assignTag($tag_text, $suggested=false) {
		$suggestion = ($suggested) ? 1 : 0;
		if ($tag_text != '') {
			$rs = $this->db->do_sql("SELECT * FROM tag WHERE tag='{$tag_text}'");
			if (count($rs) < 1) {
				$this->db->do_sql("INSERT INTO tag (tag) VALUES ('{$tag_text}')");
				$rs = $this->db->do_sql("SELECT LAST_INSERT_ID() AS tag_id");
			}
			$tag_id = $rs[0]['tag_id'];
			$this->db->do_sql("REPLACE INTO photo_tag (photo_id, tag_id, user_id, suggested) VALUES ({$this->img['photo_id']}, {$tag_id}, {$this->famu->active_id}, {$suggestion})");
			return true;
		}
		return false;
	}

	function removeUserFeatured($user_id) {
		if ($user_id != '') {
			$this->db->do_sql("DELETE FROM photo_featuring WHERE photo_id={$this->img['photo_id']} AND user_id={$user_id} LIMIT 1");
			return true;
		}
		return false;
	}

	function unassignTag($tag_id) {
		if ($tag_id != '') {
			$this->db->do_sql("DELETE FROM photo_tag WHERE photo_id={$this->img['photo_id']} AND tag_id={$tag_id} LIMIT 1");
			return true;
		}
		return false;
	}

	function getUsersFeatured() {
		return $this->db->do_sql("CALL photoUsersFeatured({$this->img['photo_id']})");
	}

	function getTagsAssigned() {
		return $this->db->do_sql("CALL photoGetTags({$this->famu->site_id}, {$this->img['photo_id']})");
	}

	function getViewsAndFaves() {
		$rs = $this->db->do_sql("SELECT p.photo_id, count(distinct pv.user_id) AS cntViews, max(pv.updated_dt) AS lastView, count(distinct pf.user_id) AS cntFavorite FROM photo p LEFT OUTER JOIN photo_view pv ON p.photo_id=pv.photo_id AND pv.user_id != {$this->famu->active_id} LEFT OUTER JOIN photo_favorite pf ON p.photo_id=pf.photo_id WHERE p.photo_id={$this->img['photo_id']} GROUP BY p.photo_id");
		return array($rs[0]['cntViews'], $rs[0]['cntFavorite'], $rs[0]['lastView']);
	}

	function makeAvatar($s, $x, $y) {
		$orig = $this->getFile_medium();
		$new_id = uniqid();
		$create = "{$this->convert_app} {$orig} -crop {$s}x{$s}+{$x}+{$y} +repage -thumbnail x96 -resize '96x<' -resize 50% -gravity center -crop 48x48+0+0 +repage -format jpg -quality 91 {$this->photo_root}avatar/{$new_id}.jpg";
		GTools::logOutput("Creating Avatar: $create");
		exec ($create);
		return $new_id;
	}

	function makeSizesFromOriginal() {
		$this->unlinkSizes();
		$orig = $this->getFile_original();

		// medium photo 500x375
		$path = $this->getFile_medium();
		$resize = "{$this->convert_app} -size 500 \"{$orig}\" -resize 500x500 -format jpg -quality 91 \"{$path}\"";
		exec ($resize);

		// small photo 240x180
		$path = $this->getFile_small();
		$resize = "{$this->convert_app} -size 240 \"{$orig}\" -resize 240x240 -format jpg -quality 91 \"{$path}\"";
		exec ($resize);

		// thumbnail photo 100x75
		$path = $this->getFile_thumbnail();
		$resize = "{$this->convert_app} -size 100 \"{$orig}\" -resize 100x100 -format jpg -quality 91 \"{$path}\"";
		exec ($resize);

		// square photo 75x75
		$path = $this->getFile_square();
		//$resize = "{$this->convert_app} -size 100 \"{$orig}\" -crop 75x75+12+0 \"{$path}\"";
		$resize = "{$this->convert_app} \"{$orig}\" -thumbnail x150 -resize '150x<' -resize 50% -gravity center -crop 75x75+0+0 +repage -format jpg -quality 91 \"{$path}\"";
		exec ($resize);
	}

	function saveCaption($caption) {
		$caption = (trim($caption)=='') ? 'NULL' : "'".addslashes(trim($caption))."'";
		$valid_ids = implode(',',$this->famu->switch_list);
		$this->db->do_sql("UPDATE photo SET caption={$caption} WHERE user_id IN ({$valid_ids}) AND photo_uid='{$this->img['photo_uid']}' LIMIT 1");

		// update photo with what actually got saved
		$rs = $this->db->do_sql("SELECT caption FROM photo WHERE photo_uid='{$this->img['photo_uid']}'");
		$this->img['caption'] = $rs[0]['caption'];
		return true;
	}

	function saveDescription($description) {
		$description = addslashes($description);
		$valid_ids = implode(',',$this->famu->switch_list);
		$this->db->do_sql("UPDATE photo SET description='{$description}' WHERE user_id IN ({$valid_ids}) AND photo_uid='{$this->img['photo_uid']}' LIMIT 1");
		return true;
	}

	function saveTaken($taken) {
		$taken = (trim($taken)=='' || trim($taken)=='NULL') ? 'NULL' : "'".date('Y-m-d', strtotime($taken))."'";
		$valid_ids = implode(',',$this->famu->switch_list);
		$this->db->do_sql("UPDATE photo SET take_dt={$taken}, take_exif=0 WHERE user_id IN ({$valid_ids}) AND photo_uid='{$this->img['photo_uid']}' LIMIT 1");

		// update photo with what actually got saved
		$rs = $this->db->do_sql("SELECT take_dt, take_exif FROM photo WHERE photo_uid='{$this->img['photo_uid']}'");
		$this->img['take_dt'] = $rs[0]['take_dt'];
		$this->img['take_exif'] = $rs[0]['take_exif'];
		return true;
	}

	function saveFavorite($fav=true) {
		if ($fav) {
			$this->db->do_sql("REPLACE INTO photo_favorite (photo_id, user_id) VALUES ({$this->img['photo_id']}, {$this->famu->active_id})");
		} else {
			$this->db->do_sql("DELETE FROM photo_favorite WHERE photo_id={$this->img['photo_id']} AND user_id={$this->famu->active_id} LIMIT 1");
		}

		// update local value
		$this->img['favorite'] = ($fav) ? 1 : 0;
	}

	function saveFlagged($flagged=true) {
		if ($flagged) {
			$this->db->do_sql("REPLACE INTO photo_flag (photo_id, user_id) VALUES ({$this->img['photo_id']}, {$this->famu->active_id})");
		} else {
			$this->db->do_sql("DELETE FROM photo_flag WHERE photo_id={$this->img['photo_id']} AND user_id={$this->famu->active_id} LIMIT 1");
		}

		// update local value
		$this->img['flagged'] = ($flagged) ? 1 : 0;
	}

	function save() {
		$this->loadFromDB();
		$this->makeSizesFromOriginal();
		$arguments = array("complete=1");

		// save filename if available
		if ($this->img['filename']!='') {
			$filename = addslashes($this->img['filename']);
			$arguments[] = "filename = '{$filename}'";
		}

		// get original take date and mime type
		if ($this->img['take_dt']=='' || $this->img['mime']=='') {
			$this->getDateAndType();
			if ($this->img['take_dt']!='') {
				$arguments[] = "take_dt = '{$this->img['take_dt']}'";
				$arguments[] = "take_exif = {$this->img['take_exif']}";
			}
			$arguments[] = "mime='{$this->img['mime']}'";
		}

		// get original dimensions
		if ($this->img['orig_height']=='' || $this->img['orig_width']=='') {
			$imagesize = getimagesize($this->getFile_original());
			$this->img['orig_height'] = $imagesize[1];
			$this->img['orig_width'] = $imagesize[0];
			$arguments[] = "orig_height={$imagesize[1]}";
			$arguments[] = "orig_width={$imagesize[0]}";
		}

		// get original file size
		if ($this->img['orig_size']=='') {
			$this->img['orig_size'] = filesize($this->getFile_original());
			$arguments[] = "orig_size={$this->img['orig_size']}";
		}

		// update condition: photo ownd by one in switch_list
		// $condition = ' AND user_id IN ('.implode(',',$this->famu->switch_list).')';
		$query = "UPDATE photo SET ".implode(', ', $arguments)." WHERE photo_uid='{$this->img['photo_uid']}' LIMIT 1";
		$this->db->do_sql($query);
	}

	function unlinkSizes() {
		$path = $this->getFile_medium();
		if (is_file($path)) {
			unlink($path);
		}
		$path = $this->getFile_small();
		if (is_file($path)) {
			unlink($path);
		}
		$path = $this->getFile_thumbnail();
		if (is_file($path)) {
			unlink($path);
		}
		$path = $this->getFile_square();
		if (is_file($path)) {
			unlink($path);
		}
	}
}

?>
