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
 * Implementation of the quizaccess_oqylyq plugin.
 *
 * @package    quizaccess_oqylyq
 * @author     Eduard Zaukarnaev <eduard.zaukarnaev@gmail.com>
 * @copyright  2020 Ertumar LLP
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use quizaccess_oqylyq\local\access_manager;
use quizaccess_oqylyq\local\quiz_settings;
use quizaccess_oqylyq\local\settings_provider;
use quizaccess_oqylyq\event\access_prevented;

defined('MOODLE_INTERNAL') || die();

/**
 * Implementation of the quizaccess_oqylyq plugin.
 *
 * @copyright  2020 Ertumar LLP
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class quizaccess_oqylyq extends quiz_access_rule_base {

    /** @var access_manager $accessmanager Instance to manage the access to the quiz for this plugin. */
    private $accessmanager;

    /**
     * Create an instance of this rule for a particular quiz.
     *
     * @param quiz $quizobj information about the quiz in question.
     * @param int $timenow the time that should be considered as 'now'.
     * @param access_manager $accessmanager the quiz accessmanager.
     */
    public function __construct(quiz $quizobj, int $timenow, access_manager $accessmanager) {
        parent::__construct($quizobj, $timenow);
        $this->accessmanager = $accessmanager;
    }

    /**
     * Return an appropriately configured instance of this rule, if it is applicable
     * to the given quiz, otherwise return null.
     *
     * @param quiz $quizobj information about the quiz in question.
     * @param int $timenow the time that should be considered as 'now'.
     * @param bool $canignoretimelimits whether the current user is exempt from
     *      time limits by the mod/quiz:ignoretimelimits capability.
     * @return quiz_access_rule_base|null the rule, if applicable, else null.
     */
    public static function make (quiz $quizobj, $timenow, $canignoretimelimits) {
        $accessmanager = new access_manager($quizobj);
        // If proctoring disabled
        if (!$accessmanager->is_proctoring_enabled()) {
            return null;
        }

        return new self($quizobj, $timenow, $accessmanager);
    }

    /**
     * Add any fields that this rule requires to the quiz settings form. This
     * method is called from {@link mod_quiz_mod_form::definition()}, while the
     * security section is being built.
     *
     * @param mod_quiz_mod_form $quizform the quiz settings form that is being built.
     * @param MoodleQuickForm $mform the wrapped MoodleQuickForm.
     */
    public static function add_settings_form_fields(mod_quiz_mod_form $quizform, MoodleQuickForm $mform) {
        settings_provider::add_oqylyq_settings_fields($quizform, $mform);
    }

    /**
     * Validate the data from any form fields added using {@link add_settings_form_fields()}.
     *
     * @param array $errors the errors found so far.
     * @param array $data the submitted form data.
     * @param array $files information about any uploaded files.
     * @param mod_quiz_mod_form $quizform the quiz form object.
     * @return array $errors the updated $errors array.
     */
    public static function validate_settings_form_fields(array $errors,
                                                         array $data, $files, mod_quiz_mod_form $quizform) : array {

        $quizid = $data['instance'];
        $cmid = $data['coursemodule'];
        $context = $quizform->get_context();

        if (!settings_provider::can_configure_oqylyq($context)) {
            return $errors;
        }

        if (settings_provider::is_oqylyq_settings_locked($quizid)) {
            return $errors;
        }

        if (settings_provider::is_conflicting_permissions($context)) {
            return $errors;
        }

        $settings = settings_provider::filter_plugin_settings((object) $data);

        // Validate basic settings using persistent class.
        $quizsettings = (new quiz_settings())->from_record($settings);
        // Set non-form fields.
        $quizsettings->set('quizid', $quizid);
        $quizsettings->set('cmid', $cmid);
        $quizsettings->validate();

        // Add any errors to list.
        foreach ($quizsettings->get_errors() as $name => $error) {
            $name = settings_provider::add_prefix($name); // Re-add prefix to match form element.
            $errors[$name] = $error->out();
        }

        return $errors;
    }

    /**
     * Save any submitted settings when the quiz settings form is submitted. This
     * is called from {@link quiz_after_add_or_update()} in lib.php.
     *
     * @param object $quiz the data from the quiz form, including $quiz->id
     *      which is the id of the quiz being saved.
     */
    public static function save_settings($quiz) {
        $context = context_module::instance($quiz->coursemodule);

        $cm = get_coursemodule_from_instance('quiz', $quiz->id, $quiz->course, false, MUST_EXIST);

        $settings = settings_provider::filter_plugin_settings($quiz);
        $settings->quizid = $quiz->id;
        $settings->cmid = $cm->id;

        // Get existing settings or create new settings if none exist.
        $quizsettings = quiz_settings::get_record(['quizid' => (int) $quiz->id]);

        if (empty($quizsettings)) {
            $quizsettings = new quiz_settings(0, $settings);
        } else {
            $settings->id = $quizsettings->get('id');
            $quizsettings->from_record($settings);
        }

        // Save or delete settings.
        if ($quizsettings->get('proctoring') != settings_provider::PROCTORING_DISABLED) {
            $quizsettings->save();
        } else if ($quizsettings->get('id')) {
            $quizsettings->delete();
        }
    }

    /**
     * Delete any rule-specific settings when the quiz is deleted. This is called
     * from {@link quiz_delete_instance()} in lib.php.
     *
     * @param object $quiz the data from the database, including $quiz->id
     *      which is the id of the quiz being deleted.
     */
    public static function delete_settings($quiz) {
        $quizsettings = quiz_settings::get_by_quiz_id($quiz->id);
        // Check that there are existing settings.
        if ($quizsettings !== false) {
            $quizsettings->delete();
        }
    }

    /**
     * Return the bits of SQL needed to load all the settings from all the access
     * plugins in one DB query. The easiest way to understand what you need to do
     * here is probalby to read the code of {@link quiz_access_manager::load_settings()}.
     *
     * If you have some settings that cannot be loaded in this way, then you can
     * use the {@link get_extra_settings()} method instead, but that has
     * performance implications.
     *
     * @param int $quizid the id of the quiz we are loading settings for. This
     *     can also be accessed as quiz.id in the SQL. (quiz is a table alisas for {quiz}.)
     * @return array with three elements:
     *     1. fields: any fields to add to the select list. These should be alised
     *        if neccessary so that the field name starts the name of the plugin.
     *     2. joins: any joins (should probably be LEFT JOINS) with other tables that
     *        are needed.
     *     3. params: array of placeholder values that are needed by the SQL. You must
     *        used named placeholders, and the placeholder names should start with the
     *        plugin name, to avoid collisions.
     */
    public static function get_settings_sql($quizid) : array {
        return [
            'oqylyq.proctoring AS oqylyq_proctoring, '
            . 'oqylyq.application AS oqylyq_application, '
            . 'oqylyq.main_camera_record AS oqylyq_main_camera_record, '
            . 'oqylyq.screen_share_record AS oqylyq_screen_share_record, '
            . 'oqylyq.second_camera_record AS oqylyq_second_camera_record, '
            . 'oqylyq.photo_head_identity AS oqylyq_photo_head_identity, '
            . 'oqylyq.id_verification AS oqylyq_id_verification, '
            . 'oqylyq.display_checks AS oqylyq_display_checks, '
            . 'oqylyq.hdcp_checks AS oqylyq_hdcp_checks, '
            . 'oqylyq.content_protect AS oqylyq_content_protect, '
            . 'oqylyq.fullscreen_mode AS oqylyq_fullscreen_mode, '
            . 'oqylyq.extension_detector AS oqylyq_extension_detector, '
            . 'oqylyq.focus_detector AS oqylyq_focus_detector '
            , 'LEFT JOIN {quizaccess_oql_quizsettings} oqylyq ON oqylyq.quizid = quiz.id '
            , []
        ];
    }

    /**
     * Whether the user should be blocked from starting a new attempt or continuing
     * an attempt now.
     *
     * @return string false if access should be allowed, a message explaining the
     *      reason if access should be prevented.
     */
    public function prevent_access() {
        global $PAGE;

        if (!$this->accessmanager->is_proctoring_enabled()) {
            return false;
        }

        /* check chrome header parameters or for other browsers check hash $_GET parameter */
        if (\core_useragent::is_chrome()) {
            if (!$this->accessmanager->validate_iframe_parameters()) {
                // If the rule is active, enforce a secure view whilst taking the quiz.
                return $this->display_buttons($this->get_launch_oqylyq_button());
            }
        } elseif (!$this->accessmanager->validate_hash_keys()) {
            // If the rule is active, enforce a secure view whilst taking the quiz.
            return $this->display_buttons($this->get_launch_oqylyq_button());
        }

        return false;
    }

    /**
     * Information, such as might be shown on the quiz view page, relating to this restriction.
     * There is no obligation to return anything. If it is not appropriate to tell students
     * about this rule, then just return ''.
     *
     * @return mixed a message, or array of messages, explaining the restriction
     *         (may be '' if no message is appropriate).
     */
    public function description() : array {
        return [get_string('proctoring_required', 'quizaccess_oqylyq')];
    }

    /**
     * Prepare buttons HTML code for being displayed on the screen.
     *
     * @param string $buttonshtml Html string of the buttons.
     * @param string $class Optional CSS class (or classes as space-separated list)
     * @param array $attributes Optional other attributes as array
     *
     * @return string HTML code of the provided buttons.
     */
    private function display_buttons(string $buttonshtml, $class = '', array $attributes = null) : string {
        $html = '';

        if (!empty($buttonshtml)) {
            $html = html_writer::div($buttonshtml, $class, $attributes);
        }

        return $html;
    }

    /**
     * Get a button to launch Oqylyq Application.
     *
     * @return string A link to launch.
     */
    private function get_launch_oqylyq_button() : string {
        global $OUTPUT;

        $link = \quizaccess_oqylyq\link_generator::get_link($this->quiz, $this->accessmanager->get_quizsettings());

        return $OUTPUT->single_button($link, get_string('launch_button', 'quizaccess_oqylyq'), 'get');
    }
}
