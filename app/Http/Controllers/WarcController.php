<?php

namespace App\Http\Controllers;


class WarcController extends Controller
{
    public 	$warc_path = NULL;
	private $fp = NULL;

	function __construct($warc_file_path)
	{
		if ( !file_exists($warc_file_path) ) {
			throw new Exception('WARC file not found.');
		}

		if(stripos(strrev($warc_file_path), "zg.") === 0)
			$path_for_fopen = "compress.zlib://$warc_file_path";
		else
			$path_for_fopen = $warc_file_path;

		$fp = fopen($path_for_fopen, 'r');
		if(!$fp)
			throw new Exception('Could not open WARC file.');
		else{
			$this->fp = $fp;
			$this->warc_path = $warc_file_path;
		}
	}

	public function nextRecord()
	{
		if(!@feof($this->fp)){
			$warc_header = array();
			$line = fgets($this->fp);
			while( $line != "\r\n" && !feof($this->fp)){
				$split_parts = array();
				$split_parts = explode(": ", $line, 2);
				if(trim($split_parts[0]) == 'WARC/1.0' || trim($split_parts[0]) == 'WARC/1.1')
					@$warc_header['version'] = trim($split_parts[0]);
				else
					@$warc_header[trim($split_parts[0])] = trim($split_parts[1]);
				$line = fgets($this->fp);
			}
			$warc_content_block = fread($this->fp, $warc_header['Content-Length']);
			fgets($this->fp);
			fgets($this->fp);

			$warc_record['header'] 	= $warc_header;
			$warc_record['content'] = $warc_content_block;
			return $warc_record;
		}
		else
			return FALSE;
	}

	function __destruct() {
		fclose($this->fp);
	}
}