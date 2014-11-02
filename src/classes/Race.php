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
require_once(dirname(__FILE__).'/SQLiteModel.php');
require_once(dirname(__FILE__).'/Entry.php');

/**
 * Description of Race
 *
 * @author rfgunion
 */
class Race extends SQLiteModel {
	protected $table = 'races';
	protected $columns = array(
		'id',
		'type',
		'racedate',
	);
	public static $VALID_TYPES = array(
		'Sunday Chowder',
		'Friday Night',
	);

	public function __get($name) {
		if ($name == 'entries') {
			if (!array_key_exists('entries', $this->data)) {
				if ($this->id) {
					$entry = new Entry();
					$this->data['entries'] = $entry->findAll('raceid='.$this->id, 'corrected');
				} else {
					$this->data['entries'] = array();
				}
			}
			return $this->data['entries'];
		}
		return parent::__get($name);
	}

	public function __set($name, $val) {
		if ($name == 'racedate') {
			$racedate = strtotime($val);
			$this->data['racedate'] = strftime('%F', $racedate);
			return;
		} else if ($name == 'entries') {
			// $val must be an array of either Entry instances or arrays
			$this->data['entries'] = array();
			foreach ($val as $entry) {
				if (is_a($entry, 'Entry')) {
					$this->data['entries'][] = $entry;
				} else { // array
					$this->data['entries'][] = new Entry($entry);
				}
			}
			return;
		}
		parent::__set($name, $val);
	}

	public function save() {
		if (!in_array($this->type, Race::$VALID_TYPES))
			return false;
		if (empty($this->racedate))
			return false;
		return parent::save();
	}
}
