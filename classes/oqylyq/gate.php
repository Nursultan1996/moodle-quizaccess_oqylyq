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
  * @author     Eduard Zaukarnaev
  * @copyright  2020 Ertumar LLP
  * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
  */


namespace quizaccess_oqylyq\oqylyq;

require_once(__DIR__ . '/../../vendor/autoload.php');

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception as GuzzleException;

class gate
{
    public static function make(icommand $command) {
        /* initialize client */
        $client = new GuzzleClient();

        $response = $client->request(
            $command->getRequestMethod(),
            implode([get_config('quizaccess_oqylyq', 'oqylyq_api_url'), $command->getRequestUrl()]),
            [
                'headers' => array_merge($command->getRequestHeaders(), [
                    'Accept'          => 'application/json',
                    'X-Authorization' => get_config('quizaccess_oqylyq', 'oqylyq_api_key'),
                    'Content-Type'    => 'application/json'
                ]),
                'query'   => $command->getRequestQuery(),
                'json'    => $command->getRequestData()
            ]
        );

        return json_decode($response->getBody()->getContents(), true);
    }
}
