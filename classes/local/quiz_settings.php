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

namespace quizaccess_oqylyq\local;

use core\persistent;
use lang_string;
use moodle_exception;
use moodle_url;

defined('MOODLE_INTERNAL') || die();

/**
 * Entity model representing quiz settings for the plugin.
 *
 * @package    quizaccess_oqylyq
 * @author     Eduard Zaukarnaev <eduard.zaukarnaev@gmail.com>
 * @copyright  2020 Ertumar LLP
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class quiz_settings extends persistent {
    /** Table name for the persistent. */
    const TABLE = 'quizaccess_oqylyq_settings';

    /** @var string $config The SEB config represented as a string. */
    private $config;

    /** @var string $configkey The SEB config key represented as a string. */
    private $configkey;

    /**
     * Return the definition of the properties of this model.
     *
     * @return array
     */
    protected static function define_properties() : array {
        return [
            'quizid' => [
                'type' => PARAM_INT,
            ],
            'cmid' => [
                'type' => PARAM_INT,
            ],
            'proctoring' => [
                'type' => PARAM_INT,
                'default' => 1,
                'null' => NULL_ALLOWED
            ],
            'application' => [
                'type' => PARAM_ALPHANUMEXT,
                'default' => 'browser',
                'null' => NULL_ALLOWED
            ],
            'main_camera_record' => [
                'type' => PARAM_INT,
                'default' => 1,
                'null' => NULL_ALLOWED
            ],
            'second_camera_record' => [
                'type' => PARAM_INT,
                'default' => 1,
                'null' => NULL_ALLOWED
            ],
            'screen_share_record' => [
                'type' => PARAM_INT,
                'default' => 1,
                'null' => NULL_ALLOWED
            ],
            'photo_head_identity' => [
                'type' => PARAM_INT,
                'default' => 1,
                'null' => NULL_ALLOWED
            ],
            'id_verification' => [
              'type' => PARAM_INT,
              'default' => 0,
              'null' => NULL_ALLOWED
            ],
            'display_checks' => [
              'type' => PARAM_INT,
              'default' => 1,
              'null' => NULL_ALLOWED
            ],
            'hdcp_checks' => [
                'type' => PARAM_INT,
                'default' => 1,
                'null' => NULL_ALLOWED
            ],
            'content_protect' => [
                'type' => PARAM_INT,
                'default' => 1,
                'null' => NULL_ALLOWED
            ],
            'fullscreen_mode' => [
              'type' => PARAM_INT,
              'default' => 0,
              'null' => NULL_ALLOWED
            ],
            'focus_detector' => [
              'type' => PARAM_INT,
              'default' => 0,
              'null' => NULL_ALLOWED
            ],
            'extension_detector' => [
              'type' => PARAM_INT,
              'default' => 0,
              'null' => NULL_ALLOWED
            ]
        ];
    }

    /**
     * @param int $quizid
     * @return mixed
     */
    public static function get_by_quiz_id(int $quizid) {
        return self::get_record([
            'quizid' => $quizid
        ]);
    }
}
