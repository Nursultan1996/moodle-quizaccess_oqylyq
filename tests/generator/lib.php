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
 * Test data generator for quizaccess_oqylyq.
 *
 * @package    quizaccess_oqylyq
 * @copyright  2020 Ertumar LLP
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

use quizaccess_oqylyq\local\quiz_settings;
use quizaccess_oqylyq\local\quiz_urls;

/**
 * Test data generator for quizaccess_oqylyq.
 *
 * @package    quizaccess_oqylyq
 * @copyright  2020 Ertumar LLP
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class quizaccess_oqylyq_generator extends component_generator_base {

    /**
     * Create a quiz_settings persistent with sensible defaults.
     *
     * @param array $data Override data.
     * @return quiz_settings
     */
    public function create_quiz_settings(array $data = []): quiz_settings {
        $defaults = [
            'quizid' => 0,
            'cmid' => 0,
            'proctoring' => 1,
            'application' => 'browser',
            'main_camera_record' => 1,
            'second_camera_record' => 1,
            'screen_share_record' => 1,
            'photo_head_identity' => 1,
            'id_verification' => 0,
            'display_checks' => 1,
            'hdcp_checks' => 1,
            'content_protect' => 1,
            'fullscreen_mode' => 0,
            'focus_detector' => 0,
            'extension_detector' => 0,
        ];

        $record = (object) array_merge($defaults, $data);
        $settings = new quiz_settings(0, $record);
        $settings->create();

        return $settings;
    }

    /**
     * Create a quiz_urls persistent with sensible defaults.
     *
     * @param array $data Override data.
     * @return quiz_urls
     */
    public function create_quiz_url(array $data = []): quiz_urls {
        $defaults = [
            'quizid' => 0,
            'cmid' => 0,
            'userid' => 0,
            'url' => 'https://test.oqylyq.kz/session/123',
            'lifetime' => 3600,
        ];

        $record = (object) array_merge($defaults, $data);
        $url = new quiz_urls(0, $record);
        $url->create();

        return $url;
    }
}
