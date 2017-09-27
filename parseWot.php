<?php

require "phpickle/phpickle.php";
class parseWot {
	private $source;

	public function __construct($filename_source){
		$this->source = $filename_source;
	}

	public function parse(){
		$replay_version = "0.0.0.0";
		$replay_version_dict = array('0', '0', '0', '0');

		$result_blocks = array();
		$result_blocks['common'] = array();
		$result_blocks['identify'] = array();
		$result_blocks['identify']['arenaUniqueID'] = 0;
		$filename_source = $this->source;
		$fp = fopen($filename_source, 'rb');
		try{
			//read content: [4:8]
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

						if( ($replay_version_dict[1] == 8 and $replay_version_dict[2] > 10) or $replay_version_dict[1] > 8 or $myblock[0]=='[' ){
							$br_json_list = json_decode($myblock, true);
							$br_block = $br_json_list[0];
						}else{
							$br_block = phpickle::loads($myblock);
						}
						foreach ($br_block['vehicles'] as $key => $value) {
							if(array_key_exists("details", $value)){
								unset($br_block['vehicles'][$key]['details']);
							}
						}
						$result_blocks['datablock_battle_result'] = $br_block;

						$result_blocks['common']['datablock_battle_result'] = 1;
						$result_blocks['identify']['arenaUniqueID'] = strval($result_blocks['datablock_battle_result']['arenaUniqueID']);

					}else{

						$blockdict = json_decode($myblock, true);
						if (array_key_exists("clientVersionFromExe", $blockdict)) {
							$replay_version = $this->cleanReplayVersion($blockdict['clientVersionFromExe']);
							$result_blocks['common']['replay_version'] = $replay_version;
							$result_blocks['identify']['replay_version'] = $replay_version;
							$replay_version_dict = explode('.', $replay_version);
						}
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
		$result_blocks = $this->get_identify($result_blocks);
		$this->dumpjson($result_blocks, $filename_source,0);
		return $result_blocks;
	}

	function cleanReplayVersion($replay_version){
			$replay_version = str_replace(array(", ",' '), array(".",'.'), $replay_version);
			return $replay_version;
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

	function get_identify($result_blocks){



		$team = 0;
		$internaluserID = 0;

		foreach ($result_blocks['datablock_1']['vehicles'] as $key => $value) {
			if ( $result_blocks['datablock_1']['vehicles'][$key]['name'] == $result_blocks['datablock_1']['playerName'] ){
				$internaluserID = $key;
				$team = $value['team'];
				break;
			}
		}
		if($team>0){
			if($result_blocks['datablock_battle_result']['common']['winnerTeam'] == $team)
				$result_blocks['identify']['isWinner'] = 1;
			elseif($result_blocks['datablock_battle_result']['common']['winnerTeam'] == "")
				$result_blocks['identify']['isWinner'] = -1;
			else
				$result_blocks['identify']['isWinner'] = 0;
		}

		$result_blocks['identify']['internaluserID'] = $internaluserID;
		$result_blocks['identify']['arenaCreateTime'] = $result_blocks['datablock_1']['dateTime'];
		$result_blocks['identify']['playername'] = $result_blocks['datablock_1']['playerName'];
		$result_blocks['identify']['accountDBID'] = $result_blocks['datablock_1']['playerID'];
		$result_blocks['identify']['mapName'] = $result_blocks['datablock_1']['mapName'];

		$result_blocks['identify']['error'] = 'none';
		$result_blocks['identify']['error_details'] = 'none';


		if (!array_key_exists("datablock_battle_result", $result_blocks['common']) )
			return $result_blocks;

		foreach($result_blocks['datablock_battle_result']['players'] as $wID => $player) {
			$result_blocks['datablock_battle_result']['players'][$wID]['platoonID'] =$result_blocks['datablock_battle_result']['players'][$wID]['prebattleID'];
			foreach ($result_blocks['datablock_battle_result']['vehicles'] as $vkey => $vvalue) {
				if ( $vvalue[0]['accountDBID'] == $wID ){
		            $result_blocks['datablock_battle_result']['players'][$wID]['vehicleid'] = $vkey;
		            break;
		        }
			}
		}
		return $result_blocks;
	}
}