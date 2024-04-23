<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

namespace tool_powerusers;

/**
 * Class constants
 *
 * @package    tool_powerusers
 * @copyright  2022 David Carrillo <davidmc@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class constants {

    /** @var int Manual */
    public const MANUAL = 0;

    /** @var int Random */
    public const RANDOM = 1;

    /** @var string Search exact matches */
    public const SEARCH_EXACT_MATCH = 'exactmatch';

    /** @var string Search starts with */
    public const SEARCH_STARTS_WITH = 'namestartswith';

    /** @var string JSON filename */
    public const FILENAME = 'charactersnames.json';

    /** @var int OK */
    public const OK = 1;

    /** @var int Error */
    public const ERROR = 2;
}
