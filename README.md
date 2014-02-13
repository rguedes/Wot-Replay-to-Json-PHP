Wot-Replay-to-Json
==================

Extract JSON data from Replays


Supported Versions
==================
WoT 0.7.x and higher.
Latest tested version: WoT 0.8.11.

Usage
==================

parseWot - creates a text file with the name of the replay where the extension has been replaced by ".json" and returns array.

$wot = new parseWot($filepath);
$wot->parse();

Usage
==================
common[status] = "ok" or "error"
common[message] = detailed error, otherwise "ok"

common[datablock_1] = Datablock 1 exists
common[datablock_battle_result] = Battle Result exists

If the replay can be read, the file will contain additional blocks:

datablock_1
datablock_battle_result - available only for replays created by WoT 0.8.2 or higher Value of -1 is indicating a corrupt/wrong inserted Battle Result due to a bug in WoT

Credits
==================
https://github.com/Phalynx/WoT-Replay-To-JSON