<?php

require "phpickle/phpickle.php";
class parseWot {
	private $source;

	public function __construct($filename_source){
		$this->source = $filename_source;
	}

	public function parse(){
		$result_blocks = array();
		$result_blocks['common'] = array();
		$result_blocks['identify'] = array();
		$result_blocks['identify']['arenaUniqueID'] = 0;
		$filename_source = $this->source;
		$fp = fopen($filename_source, 'rb');
		try{
			fseek($fp, 4);
			$contents = fread($fp, 4);
			$contents = unpack("I", $contents);
			$numofblocks = $contents[1];
			$blockNum = 1;
			$datablockPointer = array();
			$datablockSize = array();
			$startPointer = 8;

		}catch(exception $e){
			$result_blocks['common']['message'] = $e->getMessage();
			return $result_blocks;
		}
		if ($numofblocks == 0) {
			$result_blocks['common']['message'] = "unknown file structure";
			return $result_blocks;
		}

		if ($numofblocks > 4) {
			$result_blocks['common']['message'] = "unknown file structure";
			return $result_blocks;
		}

		while($numofblocks >= 1){
			try{
				fseek($fp, $startPointer);
				$size = fread($fp, 4);
				$unpack = unpack("I", $size);
				$datablockSize[$blockNum] = $unpack[1];
				$datablockPointer[$blockNum] = $startPointer + 4;
				$startPointer=$datablockPointer[$blockNum]+$datablockSize[$blockNum];
				$blockNum += 1;
				$numofblocks -= 1;
				foreach ($datablockSize as $i => $value) {
					fseek($fp, $datablockPointer[$i]);
					$myblock = fread($fp, $value);

					if (strpos($myblock,'arenaUniqueID') !== false) {
						$br_block = phpickle::loads($myblock);
						foreach ($br_block['vehicles'] as $key => $value) {
							if(array_key_exists("details", $value)){
								unset($br_block['vehicles'][$key]['details']);
							}
						}
						$result_blocks['datablock_battle_result'] = $br_block;

						$result_blocks['common']['datablock_battle_result'] = 1;
						$result_blocks['identify']['arenaUniqueID'] = $result_blocks['datablock_battle_result']['arenaUniqueID'];

					}else{
						$blockdict = json_decode($myblock, true);
						$result_blocks['datablock_' . $i] = $blockdict;
						$result_blocks['common']['datablock_' . $i] = 1;
					}
				}
				$result_blocks['common']['message'] = "ok";
			}catch(exception $e){
				$result_blocks['common']['message'] = $e->getMessage();
			}

		}
		fclose($fp);
		$this->dumpjson($result_blocks, $filename_source,0);
		return $result_blocks;
	}
	function dumpjson($mydict, $filename_source, $exitcode){
		if ($exitcode == 0) {
			$mydict['common']['status'] = "ok";
		}else{
			$mydict['common']['status'] = "error";
		}

		$filename_target = str_replace(".wotreplay", ".json", $filename_source);

		$finalfile = fopen($filename_target, 'w');
		fwrite($finalfile, json_encode($mydict));
		fclose($finalfile);
	}
}
?>