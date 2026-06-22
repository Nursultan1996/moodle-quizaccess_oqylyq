<?php
/**
 * Generate the links to open the Oqylyq Application with correct settings.
 *
 * @package    quizaccess_oqylyq
 * @author     Eduard Zaukarnaev <eduard.zaukarnaev@gmail.com>
 * @copyright  2020 Ertumar LLP
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace quizaccess_oqylyq\local;

use moodle_url;
use auth_oqylyq\authkey;

defined('MOODLE_INTERNAL') || die();

/**
 * Generate the links to open/download the Safe Exam Browser with correct settings.
 *
 * @copyright  2020 Ertumar LLP
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

class link_generator {
    /**
     * Get a link to force the download of the file over https.
     *
     * @param object $quiz Quiz module record.
     * @param quiz_settings $settings Quiz access settings.
     * @return string Generated or cached launch link.
     */
    public static function get_link($quiz, quiz_settings $settings) : string {
        global $USER;

        /* check exists url */
        $link = quiz_urls::checkExists($USER, $quiz);

        /* if link exists */
        if ($link) {
            return $link->url;
        }

        // Check if course module exists.
        get_coursemodule_from_id('quiz', $quiz->cmid, 0, false, MUST_EXIST);

        /* make external url */
        $starturl = new moodle_url(sprintf('/mod/quiz/view.php?id=%d&forceview=1&hash=%s', $quiz->cmid, self::get_hash_current_url()));
        $exiturl  = new moodle_url(sprintf('/mod/quiz/view.php?id=%d&forceview=1&', $quiz->cmid));

        /* create student */
        $session = new session([
            'student' => [
                'external_id' => $USER->id,

                'user' => [
                    'firstname' => $USER->firstname,
                    'lastname'  => $USER->lastname,
                    'email'     => $USER->email,
                    'password'  => md5($USER->id . $USER->username)
                ]
            ],

            'group' => [
                'external_id' => $quiz->cmid,
                'name'        => sprintf('%s - %d', $quiz->name, $quiz->cmid),
                'description' => 'Auto-generated group for moodle'
            ],

            'assignment' => [
                'type'          => 'external',
                'application'   => $settings->get('application'),
                'name'          => $quiz->name,
                'external_id'   => $quiz->cmid,
                'external_url'  => str_replace('&amp;', '&', $starturl->out()),

                'is_proctoring' => (bool) $settings->get('proctoring'),

                'settings' => [
                    'proctoring_settings' => [
                        'main_camera_record'    => (bool) $settings->get('main_camera_record'),
                        'second_camera_record'  => (bool) $settings->get('second_camera_record'),
                        'screen_share_record'   => (bool) $settings->get('screen_share_record'),
                        'photo_head_identity'   => (bool) $settings->get('photo_head_identity'),
                        'id_verification'       => (bool) $settings->get('id_verification'),
                        'display_checks'        => (bool) $settings->get('display_checks'),
                        'hdcp_checks'           => (bool) $settings->get('hdcp_checks'),
                        'content_protect'       => (bool) $settings->get('content_protect'),
                        'fullscreen_mode'       => (bool) $settings->get('fullscreen_mode'),
                        'extension_detector'    => (bool) $settings->get('extension_detector'),
                        'focus_detector'        => (bool) $settings->get('focus_detector'),
                    ],

                    'exit_url' => str_replace('&amp;', '&', $exiturl->out()),
                ]
            ],

            'session_data' => [
                'query' => [
                    'authkey' => self::get_auth_key()
                ]
            ]
        ]);

        /* call api request */
        $response = gate::make($session);
        /* remember link for 1 hour */
        quiz_urls::createLink($USER, $quiz, $response['session_url'], 3600);

        return $response['session_url'];
    }

    /**
     * Get hashed url
     *
     * @return string A Hash URL.
     */
    public static function get_hash_current_url() : string {
        global $CFG, $FULLME;

        /* */
        $url = $FULLME;

        // If $FULLME not set fall back to wwwroot.
        if ($FULLME == null) {
            $url = $CFG->wwwroot;
        }

        return hash('sha256', $url);
    }

    public static function get_auth_key() : string {
        global $USER;

        /* create one time life auth key */
        $key = md5(time() . $USER->id);

        /* create authkey */
        if (class_exists('\auth_oqylyq\authkey')) {
            authkey::factory($USER, $key);
        }

        return $key;
    }
}
