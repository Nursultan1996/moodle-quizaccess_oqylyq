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

require_once(__DIR__ . '/../../vendor/autoload.php');

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception as GuzzleException;

/**
 * Gateway class for API communication.
 *
 * @package    quizaccess_oqylyq
 * @copyright  2020 Ertumar LLP
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class gate {
    /**
     * Execute an API command via Guzzle HTTP client.
     *
     * @param command_interface $command The command object to execute
     * @return array Decoded JSON response
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public static function make(command_interface $command) {
        // Initialize Guzzle HTTP client.
        $client = new GuzzleClient();

        $response = $client->request(
            $command->get_request_method(),
            implode([get_config('quizaccess_oqylyq', 'oqylyq_api_url'), $command->get_request_url()]),
            [
                'headers' => array_merge($command->get_request_headers(), [
                    'Accept'          => 'application/json',
                    'X-Authorization' => get_config('quizaccess_oqylyq', 'oqylyq_api_key'),
                    'Content-Type'    => 'application/json'
                ]),
                'query'   => $command->get_request_query(),
                'json'    => $command->get_request_data()
            ]
        );

        return json_decode($response->getBody()->getContents(), true);
    }
}
