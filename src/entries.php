<?php

/* 
 * Copyright (C) 2014 rfgunion.
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation; either
 * version 2.1 of the License, or (at your option) any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 * Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public
 * License along with this library; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston,
 * MA 02110-1301  USA
 */
require_once('auth.php');
require_once('classes/Race.php');
require_once('classes/Boat.php');
require_once('classes/Entry.php');

if (!array_key_exists('raceid', $_GET) || !is_numeric($_GET['raceid'])) {
	header('Location: index.php');
	exit();
}
$edit = false;
if (array_key_exists('edit', $_GET) && getAccessLevel()>= User::ADMIN_ACCESS) {
	$edit = !!$_GET['edit'];
}

$raceid = intval($_GET['raceid']);
$race = new Race($raceid);
$boat = new Boat();
$allboats = $boat->findAll();

require_once('classes/Entry.php');

function strtohms($secs) {
	$hr = fmod($secs, 3600);
	$h = ($secs - $hr)/3600;
	$mr = fmod($hr, 60);
	$m = ($hr - $mr)/60;
	$s = $mr;
	$result = sprintf('%02d:%02d:%02d', $h, $m, $s);
	return $result;
}

function fixFinishTimeFormat($entry) {
	$finish = $entry->finish;
	if (!$finish) return;
	$hour = $minute = $second = 0;
	if (preg_match('/\d{6}/', $finish)) {
		$hour = substr($finish, 0, 2);
		$minute = substr($finish, 2, 2);
		$second = substr($finish, 4, 2);
		// TODO: be more flexible on time format (javascript?)
		$entry->finish = sprintf('%02d:%02d:%02d', $hour, $minute, $second);
	} else {
		return;
	}
	$race = $entry->race;
	$strfinish = $race->racedate.' '.$entry->finish;
	$tstart = strtotime($race->racedate.' 13:00:00');
	$tend = strtotime($strfinish);
	$tcf = 800/(550+$entry->phrf);
	$tcfspin = empty($entry->spinnaker) ? 0.04*$tcf : 0;
	$tcffurl = empty($entry->rollerFurling) ? 0 : 0.02*$tcf;
	$entry->tcf = $tcf - $tcfspin - $tcffurl;
	$entry->corrected = $entry->tcf*($tend - $tstart);
}

function parseEntryForm() {
	global $entry;
	$postedentry = $_POST['entry'];
	$entry = new Entry($postedentry);
	// checkbox values won't come through quite right
	$entry->spinnaker = array_key_exists('spinnaker', $postedentry);
	$entry->rollerFurling = array_key_exists('rollerFurling', $postedentry);
	fixFinishTimeFormat($entry);
	if ($entry->save()) {
		// Punt complex javascript by just reloading the page
		header('Location: entries.php?raceid='.$_GET['raceid'].'&edit=true');
	}
}

$entry = new Entry();
if (array_key_exists('entry_submit', $_POST) && $edit) {
	parseEntryForm();
}

?>
<html>
    <head>
        <meta charset="UTF-8">
        <title>Berkeley YC Results Program</title>
		<link rel="stylesheet" type="text/css" href="style.css">
		<script type="text/javascript" src="js/jquery.js"></script>
		<script type="text/javascript" src="js/entryform.js"></script>
		<?php if ($edit) { ?>
		<script type="text/javascript" src="js/raceform.js"></script>
		<?php } ?>
    </head>
    <body>
		<div id="header">
		<img src="http://www.berkeleyyc.org/sites/default/files/byc_site_logo.jpg" alt="Home">
		</div>
		<h1>Berkeley Yacht Club Results</h1>
		<?php require_once('nav.inc.php'); ?>

		<?php if ($race->id && $edit) {
			foreach ($allboats as $b) {
				echo '<span style="display:none" id="boat_'.$b->id.'">'.$b->name.'$$'.$b->sail.'$$'.$b->model.'$$'.$b->phrf.'$$'.$b->length.'$$'.$b->rollerFurling.'</span>';
			} 
			foreach ($race->divisions as $d) {
				echo '<span style="display:none" class="division" id="division_'.$d->id.'">'.$d->name.'$$'.$d->starttime.'$$'.$d->minphrf.'$$'.$d->maxphrf.'$$'.$d->minlength.'$$'.$d->maxlength.'</span>';
			} ?>
			<h3>Race Results:</h3>
			<table id="entries">
				<tr>
					<th>Place</th>
					<th>Division</th>
					<th>Sail Number</th>
					<th>Boat</th>
					<th>Type</th>
					<th>PHRF</th>
					<th>Spinnaker?</th>
					<th>Roller Furling?</th>
					<th>Finish Time</th>
					<th>Elapsed</th>
					<th>TCF</th>
					<th>Corrected</th>
					<th>Ahead of Next</th>
				</tr>
				<?php $i = 1;
				if ($edit) { 
					require_once('entryform.inc.php');
				}
	
				foreach ($race->divisions as $division) {
					$entries = $entry->findAll('raceid='.$race->id.' AND divisionid='.$division->id);
					$tstart = strtotime($race->racedate) + $division->starttime;
					foreach ($entries as $entry) {
						$tend = strtotime($race->racedate.' '.$entry->finish);
						$telapsed = $tend - $tstart;
						$elapsed = strtohms($telapsed);
						$corrected = $entry->corrected;
						$tcf = sprintf('%.02f', $entry->tcf);
						$gap = 'n/a';
						if (count($entries) > $i) {
							$othercorr = $entries[$i]->corrected;
							$secs = $othercorr - $entry->corrected;
							$gap = strtohms($secs);
						}
						echo '<tr>
							<td>'.$i.'</td>
							<td>'.$division->id.'</td>
							<td>'.$entry->sail.'</td>
							<td>'.$entry->name.'</td>
							<td>'.$entry->model.'</td>
							<td>'.$entry->phrf.'</td>
							<td>'.$entry->spinnaker.'</td>
							<td>'.$entry->rollerFurling.'</td>
							<td>'.$entry->finish.'</td>
							<td>'.$elapsed.'</td>
							<td>'.$tcf.'</td>
							<td>'.$corrected.'</td>
							<td>'.$gap.'</td>
						</tr>';
						++$i;
					}
				}?>
			</table>
		<?php } ?>
	</body>
</html>