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

/**
 * Command object representing a student payload for Oqylyq API.
 *
 * @package    quizaccess_oqylyq
 * @author     Eduard Zaukarnaev <eduard.zaukarnaev@gmail.com>
 * @copyright  2020 Ertumar LLP
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace quizaccess_oqylyq\local;

class student implements icommand {
    protected $user = [];

    public function __construct(array $data = []) {
        $this->user = [];
    }

    public function getRequestUrl () : string {
        return '/students';
    }

    public function getRequestData () : array {
        return $this->user;
    }

    public function getRequestQuery() : array {
        return [];
    }

    public function getRequestHeaders() : array {
        return [];
    }

    public function getRequestMethod () : string {
        return 'POST';
    }
}
