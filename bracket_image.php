<?php

define('AWS_KEY', '-AWS-KEY-');
define('AWS_SECRET', '-AWS-SECRET-');
define('AWS_ENABLED', true);

function drawText($img, $text, $size, $maxWidth, $x, $bottom, $side) {
	
	global $textColor;
	
	$text = explode(' ', $text);
	$draw = $text[0];
	for ($i = 1, $count = count($text); $i < $count; $i++) {
		$temp = $draw . ' ' . $text[$i];
		$textSize = imageftbbox($size, 0, 'HelveticaLTStd-BoldCond.otf', $temp);
		if ($textSize[2] > $maxWidth) {
			$draw .= PHP_EOL . $text[$i];
		} else {
			$draw .= ' ' . $text[$i];
		}
	}
	
	$textSize = imageftbbox($size, 0, 'HelveticaLTStd-BoldCond.otf', $draw);
	$x = $x + $side * ($maxWidth - $textSize[2]);
	imagefttext($img, $size, 0, $x, $bottom - $textSize[3], $textColor, 'HelveticaLTStd-BoldCond.otf', $draw);

}

function drawCenterText($img, $text, $size, $y) {
	
	global $textColor, $width;
	
	$textSize = imageftbbox($size, 0, 'HelveticaLTStd-BoldCond.otf', $text);
	$x = ($width - $textSize[2]) / 2;
	imagefttext($img, $size, 0, $x, $y, $textColor, 'HelveticaLTStd-BoldCond.otf', $text);

}

function characterGetImage($id) {
	if (!file_exists('cache/' . $id . '.jpg')) {
		$image = file_get_contents('http://cdn.awwni.me/bracket/' . $id . '.jpg');
		file_put_contents('cache/' . $id . '.jpg', $image);
		unset($image);
	}
	return imagecreatefromjpeg('cache/' . $id . '.jpg');
}

function characterDraw($img, $character, $x, $y, $side) {
	
	global $bgGreen, $blueHighlight, $textColor, $highlightBox;
	
	if ($side == 0) {
		imagefilledrectangle($img, $x + 80, $y + 35, $x + 280, $y + 74, $bgGreen);
		// imagefilledrectangle($img, $x, $y + 5, $x + 75, $y + 80, $blueHighlight);
		imagecopy($img, $highlightBox, $x, $y + 5, 0, 0, 75, 75);
		imageline($img, $x + 280, $y + 56, $x + 290, $y + 56, $bgGreen);
		if (null != $character && $character->characterId > 1) {
			$char = characterGetImage(base_convert($character->characterId, 10, 36));
			if (isset($character->votes) && $character->votes > 0) {
				drawText($img, $character->votes . ' votes', 16, 192, $x + 86, $y + 56, $side);
			}
			imagecopyresampled($img, $char, $x + 5, $y, 0, 0, 75, 75, 150, 150);
			drawText($img, strtoupper($character->characterName), 12, 192, $x + 85, $y + 30, $side);
		} else {
			$char = characterGetImage('unknown');
			imagecopyresampled($img, $char, $x + 5, $y, 0, 0, 75, 75, 150, 150);
		}
		imagedestroy($char);
	} else {
		imagefilledrectangle($img, $x + 20, $y + 35, $x + 220, $y + 74, $bgGreen);
		// imagefilledrectangle($img, $x + 220, $y + 5, $x + 295, $y + 80, $blueHighlight);
		imagecopy($img, $highlightBox, $x + 220, $y + 5, 0, 0, 75, 75);
		imageline($img, $x + 10, $y + 56, $x + 20, $y + 56, $bgGreen);
		if (null != $character && $character->characterId > 1) {
			$char = characterGetImage(base_convert($character->characterId, 10, 36));
			imagecopyresampled($img, $char, $x + 225, $y, 0, 0, 75, 75, 150, 150);
			drawText($img, strtoupper($character->characterName), 12, 192, $x + 15, $y + 30, $side);
			if (isset($character->votes) && $character->votes > 0) {
				drawText($img, $character->votes . ' votes', 16, 192, $x + 15, $y + 56, $side);
			}
		} else {
			$char = characterGetImage('unknown');
			imagecopyresampled($img, $char, $x + 225, $y, 0, 0, 75, 75, 150, 150);
		}
		imagedestroy($char);	
	}
	
}

function roundDraw($img, $character1, $character2, $tier, $order, $side = 0) {
	
	global $width, $height, $count, $bgGreen, $paddingTop;
	
	$rHeight = pow(2, $tier + 1) * 102.5;
	$offsetY = ($rHeight / 2 - 102.5) / 2;
	
	// Character
	$x = $tier * 298 + 10;
	$x = abs($side * $width - ($x + $side * 298));
	
	$y = $rHeight * round($order - ($count / 2 * $side)) + 5 + $offsetY + $paddingTop;
	$lineY1 = $y + 56;
	
	echo $tier, ', ', $x, ', ', $y, PHP_EOL;
	
	characterDraw($img, $character1, $x, $y, $side);
	
	$y += ($offsetY * 2) + 102.5;
	if ($y > $height) {
		$side = 1;
		$x = abs($side * $width - ($x + $side * 300));
		$y -= ($offsetY * 2) + 102.5;
		echo $x, ',', $y;
	} else {
		$centerY = $lineY1 + ($y + 56 - $lineY1) / 2;
		if ($side == 0) {
			imageline($img, $x + 290, $lineY1, $x + 290, $y + 56, $bgGreen);
			imageline($img, $x + 290, $centerY, $x + 300, $centerY, $bgGreen);
		} else {
			imageline($img, $x + 10, $lineY1, $x + 10, $y + 56, $bgGreen);
			imageline($img, $x + 10, $centerY, $x, $centerY, $bgGreen);
		}
	}
	characterDraw($img, $character2, $x, $y, $side);

}

include('lib/aal.php');
include('lib/S3.php');

$paddingTop = Lib\Url::GetInt('paddingTop', 0);
$startTier = Lib\Url::GetInt('tier');
$title = 'ROUND ' . $startTier;
$group = Lib\Url::GetInt('group', false);

$startTier = $startTier ?: 1;
if (false !== $group) {
	$title .= ' - GROUP ' . chr(65 + $group);
	$row = Lib\Db::Fetch(Lib\Db::Query('SELECT COUNT(1) AS total FROM round WHERE bracket_id = :bracketId AND round_tier = :tier AND round_group = :group', array( ':bracketId' => BRACKET_ID, ':tier' => $startTier, ':group' => $group )));
} else {
	$row = Lib\Db::Fetch(Lib\Db::Query('SELECT COUNT(1) AS total FROM round WHERE bracket_id = :bracketId AND round_tier = :tier', array( ':bracketId' => BRACKET_ID, ':tier' => $startTier )));
}

$title = false === $group && $startTier === 1 ? 'FULL BRACKET' : $title;
$title = isset($_GET['title']) ? strtoupper($_GET['title']) : $title;

$count = $roundCount = $row->total;
$columns = 1;
while ($count > 1) {
	$columns++;
	$count /= 2;
}
$count = $roundCount;
echo $count, ', ', $columns, PHP_EOL;

$width = ($columns * 2) * 300 + 330;
$height = $count / 2 * 205 + 30 + $paddingTop;

$out = imagecreatetruecolor($width, $height);
$highlightBox = imagecreatefrompng('bracket_highlight.png');
$lightGreen = imagecolorallocate($out, 196, 231, 221);
$bgGreen = imagecolorallocate($out, 150, 188, 177);
$blueHighlight = imagecolorallocate($out, 0xe6, 0xff, 0xf7);
$textColor = imagecolorallocate($out, 96, 105, 104);

imagefill($out, 0, 0, $lightGreen);
$img = imagecreatefrompng('bracket_top.png');
imagecopyresampled($out, $img, 0, 0, 0, 0, $width, 700, 48, 700);
imagedestroy($img);

$img = imagecreatefrompng('view/awwnime/styles/images/awwnime_logo.png');
$x = ($width - imagesx($img)) / 2;
imagecopy($out, $img, $x, 40, 0, 0, imagesx($img), imagesy($img));
imagedestroy($img);

drawCenterText($out, $title, 32, 207);

$end = $startTier + $columns;
for ($i = $startTier; $i < $end; $i++) {
	
	echo '--', $i , ',', $end, '--', PHP_EOL;
	
	if (false !== $group) {
		$rounds = Api\Round::getRoundsByGroup(BRACKET_ID, $i, $group);
	} else {
		$rounds = Api\Round::getRoundsByTier(BRACKET_ID, $i);
	}
	$count = round($roundCount / pow(2, $i - $startTier));
	$order = 0;
	if ($rounds) {
		foreach ($rounds as $round) {
			$char1 = Lib\Db::Fetch(Lib\Db::Query('SELECT COUNT(DISTINCT vote_ip) AS total FROM votes WHERE character_id = :id AND round_id = :round', array( ':id' => $round->roundCharacter1Id, ':round' => $round->roundId)));
			$char2 = Lib\Db::Fetch(Lib\Db::Query('SELECT COUNT(DISTINCT vote_ip) AS total FROM votes WHERE character_id = :id AND round_id = :round', array( ':id' => $round->roundCharacter2Id, ':round' => $round->roundId)));
			$round->roundCharacter1->votes = $char1->total;
			$round->roundCharacter2->votes = $char2->total;
			roundDraw($out, $round->roundCharacter1, $round->roundCharacter2, $round->roundTier - $startTier, $order, floor($order / ($count / 2)) * 1);
			$order++;
		}
	}
	
	if ($order >= $count && $i + 1 >= $end && $round->roundCharacter1->votes > 0 && $round->roundCharacter2->votes) {
		
		// Draw the winner
		$char = $round->roundCharacter1->votes > $round->roundCharacter2->votes ? $round->roundCharacter1 : $round->roundCharacter2;
		$charImg = characterGetImage(base_convert($char->characterId, 10, 36));
		$srcWidth = imagesx($charImg);
		$srcHeight = imagesy($charImg);
		$x = ($width - $srcWidth) / 2;
		$y = ($height - $paddingTop - $srcHeight) / 2 + $paddingTop;
		imageline($out, $x - 100, $y + $srcHeight / 2 - 6, $x + $srcWidth + 100, $y + $srcHeight / 2 - 6, $bgGreen);
		imagecopy($out, $highlightBox, $x - 5, $y + 5, 0, 0, 150, 150);
		imagecopy($out, $charImg, $x, $y, 0, 0, $srcWidth, $srcHeight);
		drawCenterText($out, strtoupper($char->characterName), 32, $y + $srcHeight + 45);
		
	} else {	
		while ($order < $count) {
			roundDraw($out, null, null, $i - $startTier, $order, floor($order / ($count / 2)) * 1);
			$order++;
		}
		
		if ($i + 1 >= $end) {
			$charImg = characterGetImage('unknown');
			$srcWidth = imagesx($charImg);
			$srcHeight = imagesy($charImg);
			$x = ($width - $srcWidth) / 2;
			$y = ($height - $paddingTop - $srcHeight) / 2 + $paddingTop;
			imageline($out, $x - 130, $y + $srcHeight / 2 - 6, $x + $srcWidth + 130, $y + $srcHeight / 2 - 6, $bgGreen);
			imagecopy($out, $highlightBox, $x - 5, $y + 5, 0, 0, 150, 150);
			imagecopy($out, $charImg, $x, $y, 0, 0, $srcWidth, $srcHeight);
		}
	
	}
	
}

imagedestroy($highlightBox);

$fileName = 'bracket_';
if (false !== $group) {
	$fileName .= 'round' . $startTier . '_group' . chr($group + 65);
} else {
	$fileName .= $startTier === 1 ? 'full' : 'round' . $startTier;
}
$fileName .= '.jpg';

imagejpeg($out, 'cache/' . $fileName, 100);

if (AWS_ENABLED) {
	$s3 = new \S3(AWS_KEY, AWS_SECRET);
	$s3->deleteObject('cdn.awwni.me', 'bracket/' . $fileName);
	$data = $s3->inputFile('cache/' . $fileName);
	var_dump($s3->putObject($data, 'cdn.awwni.me', 'bracket/' . $fileName, \S3::ACL_PUBLIC_READ));
}
