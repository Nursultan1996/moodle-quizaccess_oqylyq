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
 * Manage the access to the quiz.
 *
 * @package    quizaccess_oqylyq
 * @author     Eduard Zaukarnaev <eduard.zaukarnaev@gmail.com>
 * @copyright  2020 Ertumar LLP
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace quizaccess_oqylyq\local;

use context_module;

defined('MOODLE_INTERNAL') || die();

/**
 * Manage the access to the quiz.
 *
 * @copyright  2020 Ertumar LLP
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class access_manager {
    /** Header sent by Oqylyq Application containing the url key hash. */
    private const EXAM_KEY_QUERY = 'hash';

    /** @var object $quiz A quiz object containing all information pertaining to current quiz. */
    private $quiz;

    /** @var quiz_settings $quizsettings A quiz settings persistent object containing plugin settings */
    private $quizsettings;

    /** @var context_module $context Context of this quiz activity. */
    private $context;

    /**
     * The access_manager constructor.
     *
     * @param object $quiz The details of the quiz.
     */
    public function __construct($quiz) {
        $this->quiz = $quiz;
        $this->context = context_module::instance($quiz->get_cmid());
        $this->quizsettings = quiz_settings::get_record(['quizid' => $quiz->get_quizid()]);
    }

    /**
     * Check if the browser exam opened in iframe
     *
     * @return bool True if header fetch_sec if passed
     */
    public function validate_iframe_parameters() : bool {
        return $this->has_fetch_sec_iframe();
    }

    /**
     * Check if the browser exam key hash in header matches one of the listed browser exam keys from quiz settings.
     *
     * @return bool True if header key matches one of the saved keys.
     */
    public function validate_hash_keys() : bool {
        /* allowed path without hash */
        if (isset($_SERVER['DOCUMENT_URI']) && in_array($_SERVER['DOCUMENT_URI'], ['/mod/quiz/attempt.php', '/mod/quiz/startattempt.php', '/mod/quiz/summary.php/mod/quiz/summary.php'])) {
            return true;
        }

        $receivedhash = $this->get_received_hash_key();

        /* a freshly supplied hash is validated and only remembered when it is valid */
        if (!is_null($receivedhash)) {
            if ($this->check_key($receivedhash)) {
                setcookie('proctoring_oqylyq_hash', $receivedhash, time() + 3600);
                return true;
            }

            return false;
        }

        /* fall back to a previously validated hash stored in the cookie */
        if (isset($_COOKIE['proctoring_oqylyq_hash'])) {
            return $this->check_key($_COOKIE['proctoring_oqylyq_hash']);
        }

        return false;
    }

    /**
     * Return the full URL that was used to request the current page, which is
     * what we need for verifying the hash.
     */
    private function get_this_page_url() : string {
        global $CFG, $FULLME;
        // If $FULLME not set fall back to wwwroot.
        if ($FULLME == null) {
            return $CFG->wwwroot;
        }
        return $FULLME;
    }

    /**
     * Getter for the quiz object.
     *
     * @return object
     */
    public function get_quiz() {
        return $this->quiz;
    }

    /**
     * Getter for the quiz settings object.
     *
     * @return quiz_settings
     */
    public function get_quizsettings() : quiz_settings {
        return $this->quizsettings;
    }

    /**
     * Returns Bool
     *
     * @return bool
     */
    public function has_fetch_sec_iframe() {
        return isset($_SERVER['HTTP_SEC_FETCH_DEST']) && $_SERVER['HTTP_SEC_FETCH_DEST'] == 'iframe';
    }

    /**
     * Check the supplied hash against the expected value for the current page.
     *
     * @param string $hash the hash supplied through the request or cookie.
     * @return bool true if the hash matches.
     */
    private function check_key($hash) : bool {
        if (empty($hash)) {
            return false;
        }

        // Constant-time comparison of the supplied hash against the expected value.
        return hash_equals(hash('sha256', $this->get_this_page_url()), (string) $hash);
    }

    /**
     * Returns Oqylyq Application Key hash.
     *
     * @return string|null
     */
    public function get_received_hash_key() {
        $hash = optional_param(self::EXAM_KEY_QUERY, '', PARAM_ALPHANUMEXT);

        if ($hash !== '') {
            return $hash;
        }

        return null;
    }

    /**
     * Get type of proctoring usage for the quiz.
     *
     * @return int
     */
    public function is_proctoring_enabled() : int {
        if (empty($this->quizsettings)) {
            return settings_provider::PROCTORING_DISABLED;
        } else {
            return $this->quizsettings->get('proctoring');
        }
    }
}
