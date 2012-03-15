<?php
header("Content-type: image/png");

$os = array();
foreach (file('cs_os.txt') as $line)
{
	$t = explode("\t", trim($line));
	$os[$t[0]] = $t[1];
}

function console_out($string)
{
	global $im, $realblack;
	static $cy = 0;
	imagestring($im, 2, 0, $cy+=10, $string, $realblack);
}

//foreach (array_unique($os) as $f)
//	@unlink("$f.dat");

$dat = array();
// It's assumed that the file is stable sorted by nick, with the most recent further down the file.
foreach (array_reverse(file('polcom2.txt')) as $line)
{
	$t = explode("\t", $line);
	$nick = trim($t[2]);
	if (!isset($os[$nick]))
		die("$nick has no os!");
	//file_put_contents($os[$nick] . '.dat', $line, FILE_APPEND);
	$dat[$os[$nick]][$nick][$t[0]] = $t[1];
}

$h=$w=1000;
$im = imagecreatetruecolor($w, $h);
//imagecolortransparent($im, $clear = imagecolorallocate($im, 13, 33, 37));
$white = imagecolorallocate($im, 255, 255, 255);
imagefill($im, 0, 0, $white);

$black = imagecolorallocate($im, 190, 190, 190);
$realblack = imagecolorallocate($im, 0, 0, 0);
imageline($im, $w/2, 0, $w/2, $h, $black);
imageline($im, 0, $h/2, $w, $h/2, $black);

for ($i=$w/20; $i < $w; $i+=($w/20))
{
	if ($i % 200 == 0) $f = 6; else $f = 2;
	imageline($im, $i, $h/2+$f, $i, $h/2-$f, $black);
	imageline($im, $h/2+$f, $i, $h/2-$f, $i, $black);
}

$os_col = array('l' => imagecolorallocate($im, 255, 0, 0),
	'w' => imagecolorallocate($im, 0, 0, 255),
	'm' => imagecolorallocate($im, 0, 180, 0),
	'x' => imagecolorallocate($im, 255, 0, 255),
	'?' => imagecolorallocate($im, 128, 128, 128),
);

$alph = 115;

$ext_col = array('l' => imagecolorallocatealpha($im, 255, 0, 0, $alph),
	'w' => imagecolorallocatealpha($im, 0, 0, 255, $alph),
	'm' => imagecolorallocatealpha($im, 0, 180, 0, $alph),
	'x' => imagecolorallocatealpha($im, 255, 0, 255, $alph),
	'?' => imagecolorallocatealpha($im, 128, 128, 128, $alph),
);

$by_os = array();

$os_name = array('w' => 'Windows', 'l' => 'Linux', 'm' => 'Mac', 'x' => 'Indecisive', '?' => 'Unknown');

function whiteness($x, $xmax, $y, $ymax) {
	global $white, $im, $os_col;
	$cols = 0;
	for ($mx = $x; $mx < $xmax; ++$mx)
		for ($my = $y; $my < $ymax; ++ $my)
			$cols += imagecolorat($im, $mx, $my) == $white;
#	imageline($im, $x, $y, $xmax, $ymax, $os_col['w']);
	return $cols/($ymax-$y)/($xmax-$x);
}

$last_for = array();
function point_at_real($x, $y, $label, $os, $ll = 1)
{
	global $im, $last_for, $os_col, $ext_col;
	$has_last = isset($last_for[$label]);
	$lab = $has_last ? '' : $label . (isset($_GET{'cb'}) ? ' (' . $os . ')' : '');
	if ($lab != '') {
		$cols = 0;
		$xbox = $ll+3+strlen($lab)*6;
		$congleft = whiteness($x-$xbox, $x, $y-3, $y+3);
		$congright = whiteness($x, $x+$xbox, $y-3, $y+3);
		if ($congleft <= $congright)
			imagestring($im, 2, $x+$ll+3, $y-6, $lab, $os_col[$os]);
		else
			imagestring($im, 2, $x-$xbox, $y-6, $lab, $os_col[$os]);
	}
	imageline($im, $x-$ll, $y-$ll, $x+$ll, $y+$ll, $os_col[$os]);
	imageline($im, $x-$ll, $y+$ll, $x+$ll, $y-$ll, $os_col[$os]);
	if ($has_last)
		imageline($im, $last_for[$label][0], $last_for[$label][1], $x, $y, $ext_col[$os]);
	$last_for[$label] = array($x, $y);
}


imagestringup($im, 2, $w/2+2, 137, "Authoritarian (fascism)", $black);
imagestringup($im, 2, $w/2+2, $h-2, "Libertarian (anarchism)", $black);
imagestring($im, 2, 0, $h/2+2, "Left (communism)", $black);
imagestring($im, 2, $w-132, $h/2+2, "Right (Neo-liberalism)", $black);
function point_at($x, $y, $nick, $os)
{
	global $w, $h, $os_col, $by_os;
	$x+=10;
	$y+=10;
	$x*=$w/20;
	$y*=$h/20;
	$y=$h-$y;
	$by_os[$os][$nick][] = array($x, $y);
	point_at_real($x, $y, $nick, $os);
}

foreach ($dat as $os => $nicks)
	foreach ($nicks as $nick => $coord)
		foreach ($coord as $x => $y)
			point_at($x, $y, $nick, $os);

$average = array();
foreach ($by_os as $os => $points_list_nick)
	for ($i=0; $i<2; ++$i)
		foreach ($points_list_nick as $nick => $points_list)
			foreach ($points_list as $points)
				@$average[$os][$nick][$i] += $points[$i];

foreach ($average as $os => $nick_points)
	for ($i=0; $i<2; ++$i)
		foreach ($nick_points as $nick => $p)
			@$average[$os][$nick][$i] = $p[$i] / count($by_os[$os][$nick]);

$by_os = $average;

$average = array();

foreach ($by_os as $os => $points_list)
	for ($i=0; $i<2; ++$i)
		foreach ($points_list as $points)
			@$average[$os][$i] += $points[$i];

foreach ($average as $os => $p)
	for ($i=0; $i<2; ++$i)
		@$average[$os][$i] = $p[$i] / count($by_os[$os]);


foreach ($average as $os => $p)
	if (count($by_os[$os]) > 1)
		point_at_real($p[0], $p[1], $os_name[$os], $os, 8);


$win = $average['w'];
$lin = $average['l'];

$pt = array();

for ($i=0; $i<2; ++$i)
	$pt[$i] = ($win[$i]+$lin[$i])/2.0;

$grad = -($win[0]-$lin[0])/($win[1]-$lin[1]);
$newy = $c = $pt[1] - $grad*$pt[0];

$counts = array();
$nicks_for = array();

foreach ($by_os['w'] as $nick => $points)
	if (($points[1]-$newy)/$points[0] < $grad)
		@$nicks_for['w'][] = $nick;

foreach ($by_os['l'] as $nick => $points)
	if (($points[1]-$newy)/$points[0] /**/>/**/ $grad)
		@$nicks_for['l'][] = $nick;



sort($nicks_for['w']);

if ($newy < 0)
	$newy = 0;

function nick_preview($nick)
{
	return substr($nick, 0, 3);
}

imageline($im, 0, $newy, $pt[0]+1600, $pt[1]+1600*$grad, $black);

imagestring($im, 2, 15, $newy, "Windows: " . round(100*(count(@$nicks_for['w']) / count($by_os['w'])), 0) . "% this side.", $os_col['w']);
imagestringup($im, 2, 0, $newy+73, "Linux: " . round(100*(count(@$nicks_for['l']) / count($by_os['l'])), 0) . "%", $os_col['l']);

//imagestringup($im, 2, 0, $newy+73, "Linux: " . round(100*($counts['l'] / count($by_os['l'])), 0) . "%", $os_col['l']);


$top = 0;
foreach ($os_name as $token => $text)
	point_at_real(700, $top+=20, $text . ' (' . count($by_os[$token]) . ')', $token);


imagepng($im);
