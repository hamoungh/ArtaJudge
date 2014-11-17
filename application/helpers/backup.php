<?php
define('BASEPATH',realpath(dirname(__FILE__))."/../");
require_once(realpath(dirname(__FILE__))."/../config/database.php");
require_once(realpath(dirname(__FILE__))."/../config/config.php");

class backup {

	public static function it(){
		global  $db,$config;
		$db_host = $db['default']['hostname'];
		$db_user = $db['default']['username'];            // Database username
		$db_pass = $db['default']['password'];            // Database password
		$db_name = $db['default']['database'];            // Database name. Use --all-databases if you have more than one
		$db_prefix = $db['default']['dbprefix'];
		$tables = '*';
		$return ="";
		$con= mysql_connect($db_host,$db_user,$db_pass);
		mysql_select_db($db_name,$con);
		mysql_query ('SET NAMES utf8');

		//get all of the tables
		if($tables == '*')
		{
			$tables = array();
			$result = mysql_query('SHOW TABLES');
			while($row = mysql_fetch_row($result))
			{
				$tables[] = $row[0];
			}
		}
		else
		{
			$tables = is_array($tables) ? $tables : explode(',',$tables);
		}

		//cycle through
		foreach($tables as $table)
		{
			//if($table == $db_prefix."sessions"  )
			//	continue;
			$result = mysql_query('SELECT * FROM '.$table);
			$num_fields = mysql_num_fields($result);

			$return.= 'DROP TABLE '.$table.';';
			$row2 = mysql_fetch_row(mysql_query('SHOW CREATE TABLE '.$table));
			$return.= "\n\n".$row2[1].";\n\n";

			for ($i = 0; $i < $num_fields; $i++)
			{
			while($row = mysql_fetch_row($result))
			{
			$return.= 'INSERT INTO '.$table.' VALUES(';
					for($j=0; $j<$num_fields; $j++)
					{
					$row[$j] = addslashes($row[$j]);
					$row[$j] = str_replace("\n","\\n",$row[$j]);
					if (isset($row[$j])) {
					$return.= '"'.$row[$j].'"' ;
					} else { $return.= '""';
					}
					if ($j<($num_fields-1)) {
					$return.= ',';
					}
					}
					$return.= ");\n";
			}
			}
			$return.="\n\n\n";
		}

		//save file
		$filename='db-backup-'.time().'-'.(md5(implode(',',$tables))).'.sql';
		$handle = fopen(realpath(dirname(__FILE__))."/".$filename,'w+');
		fwrite($handle,$return);
		fclose($handle);
		//self::compress($filename);
			
		//self::send($filename.".zip","beporsin-backup-".date("Y-m-d"));
		self::gzip(realpath(dirname(__FILE__))."/".$filename);
		self::send(realpath(dirname(__FILE__))."/".$filename.".gz","sharifjudge-backup-".date("Y-m-d"));
		unlink(realpath(dirname(__FILE__))."/".$filename);
	}



private static function compress($filepath) {
	global  $db,$config;
	$zip = new ZipArchive();
	$file=$filepath.".zip";
	if($zip->open($file,1?ZIPARCHIVE::OVERWRITE:ZIPARCHIVE::CREATE)===TRUE)
	{
		// Add the files to the .zip file
		$zip->addFile($filepath);

		// Closing the zip file
		$zip->close();
	}
}

private static function send($filename,$title){
	global  $db,$config;
	$datestamp = date("Y-m-d");      // Current date to append to filename of backup file in format of YYYY-MM-DD
	$from =$config['backup_email'];
	$to=$config['backup_email'];
	$subject = $title;

	$attachmentname = $filename;   // If a path was included, strip it out for the attachment name

	echo $message = "Compressed database backup file $attachmentname attached.";
	$mime_boundary = "< <<:" . md5(time());
	$data = chunk_split(base64_encode(implode("", file($filename))));

	$headers = "From: $from\r\n";
	$headers .= "MIME-Version: 1.0\r\n";
	$headers .= "Content-type: multipart/mixed;\r\n";
	$headers .= " boundary=\"".$mime_boundary."\"\r\n";

	$content = "This is a multi-part message in MIME format.\r\n\r\n";
	$content.= "--".$mime_boundary."\r\n";
	$content.= "Content-Type: text/plain; charset=\"iso-8859-1\"\r\n";
	$content.= "Content-Transfer-Encoding: 7bit\r\n\r\n";
	$content.= $message."\r\n";
	$content.= "--".$mime_boundary."\r\n";
	$content.= "Content-Disposition: attachment;\r\n";
	$content.= "Content-Type: Application/Octet-Stream; name=\"$attachmentname\"\r\n";
	$content.= "Content-Transfer-Encoding: base64\r\n\r\n";
	$content.= $data."\r\n";
	$content.= "--" . $mime_boundary . "\r\n";

	mail($to, $subject, $content, $headers);

	unlink($filename);   //delete the backup file from the server

}

public static function gzip($src, $level = 5, $dst = false){
	if($dst == false){
		$dst = $src.".gz";
	}
	if(file_exists($src)){
		$filesize = filesize($src);
		$src_handle = fopen($src, "r");
		if(!file_exists($dst)){
			$dst_handle = gzopen($dst, "w$level");
			while(!feof($src_handle)){
				$chunk = fread($src_handle, 2048);
				gzwrite($dst_handle, $chunk);
			}
			fclose($src_handle);
			gzclose($dst_handle);
			return true;
		} else {
			echo ("$dst already exists");
		}
	} else {
		echo ("$src doesn't exist");
	}
	return false;
	unlink($src);   //delete the backup file from the server
}

}
$backup = new backup();
$backup->it();
?>