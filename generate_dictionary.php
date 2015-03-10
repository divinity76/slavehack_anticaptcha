<?php
	require_once('hhb_.inc.php');
	function generate_dictionary(){
		$dir=hhb_combine_filepaths(__DIR__,'saved_images');
		$dictionary=array();
		assert(is_dir($dir));
		$dirh=opendir($dir);
		assert(false!==$dirh);
		$file=false;
		while (false!==($file = readdir($dirh))){
			if($file=="." || $file==".." || 0===stripos($file,"FINISHED_") ){
				continue;
			}
			$dir2=hhb_combine_filepaths($dir,$file);
			assert(is_dir($dir2));
			$dirh2=opendir($dir2);
			assert(false!==$dirh2);
			$file2=false;
			while (false!==($file2 = readdir($dirh2))){
				if($file2=="." || $file2==".." || $file=="tempfile.png" || 0===stripos($file2,"FINISHED_")){
					continue;
				}
				//var_dump("before removing png:",$file2);
				$cutindex=stripos($file2,".png");
				assert(false!==$cutindex);
				$file2=substr($file2,0,$cutindex);
				//var_dump("after removing png:",$file2);
				$dictionary[$file2]=$file;
			}
			closedir($dirh2);
		}
        closedir($dirh);
		return $dictionary;
	}	