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
 * Command object representing an assignment request sent to Oqylyq API.
 *
 * @package    quizaccess_oqylyq
 * @author     Eduard Zaukarnaev <eduard.zaukarnaev@gmail.com>
 * @copyright  2020 Ertumar LLP
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace quizaccess_oqylyq\local;

/**
 * Assignment command class.
 *
 * @package    quizaccess_oqylyq
 * @copyright  2020 Ertumar LLP
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class assignment implements command_interface {
    /** @var array Assignment data */
    protected $assignment = [];

    /**
     * Constructor.
     *
     * @param array $data Assignment data
     */
    public function __construct(array $data = []) {
        $this->assignment = $data;
    }

    /**
     * Get the request URL path.
     *
     * @return string
     */
    public function get_request_url() : string {
        return '/assignments';
    }

    /**
     * Get the request data payload.
     *
     * @return array
     */
    public function get_request_data() : array {
        return $this->assignment;
    }

    /**
     * Get the request query parameters.
     *
     * @return array
     */
    public function get_request_query() : array {
        return [];
    }

    /**
     * Get the request headers.
     *
     * @return array
     */
    public function get_request_headers() : array {
        return [];
    }

    /**
     * Get the HTTP request method.
     *
     * @return string
     */
    public function get_request_method() : string {
        return 'POST';
    }
}
