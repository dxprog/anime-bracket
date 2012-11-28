<?php

require('lib/aal.php');

$result = Lib\Db::Query('SELECT * FROM `round` WHERE round_final = 0 ORDER BY round_order');
$i = 0;
while ($row = Lib\Db::Fetch($result)) {
	
	$group = (int)($i / 16);
	Lib\Db::Query('UPDATE `round` SET round_group = ' . $group . ' WHERE round_id = ' . $row->round_id);
	$i++;

}