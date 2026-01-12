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
 * Gateway class responsible for executing API requests via Guzzle client.
 *
 * @package    quizaccess_oqylyq
 * @author     Eduard Zaukarnaev <eduard.zaukarnaev@gmail.com>
 * @copyright  2020 Ertumar LLP
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace quizaccess_oqylyq\local;

use curl;
use moodle_exception;

defined('MOODLE_INTERNAL') || die();

/**
 * Gateway class for API communication.
 *
 * @package    quizaccess_oqylyq
 * @copyright  2020 Ertumar LLP
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class gate {
    /**
     * Execute an API command via Moodle's curl class.
     *
     * @param command_interface $command The command object to execute
     * @return array Decoded JSON response
     * @throws moodle_exception
     */
    public static function make(command_interface $command) {
        $curl = new curl();

        // Build full URL.
        $baseurl = get_config('quizaccess_oqylyq', 'oqylyq_api_url');
        $url = $baseurl . $command->get_request_url();

        // Prepare headers.
        $headers = array_merge($command->get_request_headers(), [
            'Accept: application/json',
            'X-Authorization: ' . get_config('quizaccess_oqylyq', 'oqylyq_api_key'),
            'Content-Type: application/json'
        ]);

        $curl->setHeader($headers);

        // Execute request based on method.
        $method = $command->get_request_method();
        $data = $command->get_request_data();

        if ($method === 'POST') {
            $response = $curl->post($url, json_encode($data));
        } else if ($method === 'GET') {
            $response = $curl->get($url, $command->get_request_query());
        } else {
            throw new moodle_exception('unsupportedmethod', 'quizaccess_oqylyq', '', $method);
        }

        // Check for errors.
        $info = $curl->get_info();
        if ($info['http_code'] >= 400) {
            throw new moodle_exception('apierror', 'quizaccess_oqylyq', '', $info['http_code']);
        }

        return json_decode($response, true);
    }
}
