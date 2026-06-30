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

use stdClass;

defined('MOODLE_INTERNAL') || die();

/**
 * Entity model representing quiz settings for the plugin.
 *
 * @package    quizaccess_oqylyq
 * @author     Eduard Zaukarnaev <eduard.zaukarnaev@gmail.com>
 * @copyright  2020 Ertumar LLP
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class quiz_urls extends persistent {
    /** Table name for the persistent. */
    const TABLE = 'quizaccess_oqylyq_urls';

    /** @var string $config The oqylyq config represented as a string. */
    private $config;

    /** @var string $configkey The oqylyq config key represented as a string. */
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
            'userid' => [
                'type' => PARAM_INT,
            ],
            'url' => [
                'type' => PARAM_TEXT,
            ],
            'lifetime' => [
                'type' => PARAM_INT,
                'default' => 0,
                'null' => NULL_ALLOWED
            ]
        ];
    }

    public static function checkExists($user, $quiz) : ?stdClass {
        global $CFG, $DB;

        $link = $DB->get_record_sql(
            sprintf('SELECT * FROM %s WHERE %s = %s AND %s = %s AND %s = %s', $CFG->prefix . self::TABLE,
                $DB->sql_compare_text('userid'), $DB->sql_compare_text(':userid'),
                $DB->sql_compare_text('quizid'), $DB->sql_compare_text(':quizid'),
                $DB->sql_compare_text('cmid'), $DB->sql_compare_text(':cmid')
            ), ['userid' => $user->id, 'quizid' => $quiz->id, 'cmid' => $quiz->cmid]
        );

        if (!$link) {
            return NULL;
        }

        /* check expire */
        if ($link->lifetime > 0) {
            if (($link->lifetime + $link->timecreated) < time()) {
                return NULL;
            }
        }

        return $link;
    }

    public static function createLink($user, $quiz, $url, $expire = 3600) : quiz_urls {
        global $CFG, $DB;

        /* before, remove old user urls */
        foreach (self::get_records(['userid' => $user->id]) as $item) {
            $item->delete();
        }

        /* after, create new */
        $link = new self();
        $link->set('userid', $user->id);
        $link->set('quizid', $quiz->id);
        $link->set('cmid', $quiz->cmid);
        $link->set('url', $url);
        $link->set('lifetime', $expire);
        $link->create();

        return $link;
    }
}
