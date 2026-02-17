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
 * Class for providing quiz settings, to make setting up quiz form manageable.
 *
 * To make sure there are no inconsistencies between data sets, run tests in tests/phpunit/settings_provider_test.php.
 *
 * @package    quizaccess_oqylyq
 * @author     Eduard Zaukarnaev <eduard.zaukarnaev@gmail.com>
 * @copyright  2020 Ertumar LLP
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace quizaccess_oqylyq\local;

use context_module;
use context_user;
use lang_string;
use stdClass;
use stored_file;

defined('MOODLE_INTERNAL') || die();

/**
 * Helper class for providing quiz settings, to make setting up quiz form manageable.
 *
 * @copyright  2020 Catalyst IT
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class settings_provider {

    /**
     * Proctoring isnt be used.
     */
    const PROCTORING_DISABLED = 0;

    /**
     * Proctoring should be used
     */
    const PROCTORING_ENABLED = 1;

    /**
     * Insert form element.
     *
     * @param \mod_quiz_mod_form $quizform the quiz settings form that is being built.
     * @param \MoodleQuickForm $mform the wrapped MoodleQuickForm.
     * @param \HTML_QuickForm_element $element Element to insert.
     * @param string $before Insert element before.
     */
    protected static function insert_element(\mod_quiz_mod_form $quizform,
                                             \MoodleQuickForm $mform, \HTML_QuickForm_element $element, $before = 'security') {
        $mform->insertElementBefore($element, $before);
    }

    /**
     * Remove element from the form.
     *
     * @param \mod_quiz_mod_form $quizform the quiz settings form that is being built.
     * @param \MoodleQuickForm $mform the wrapped MoodleQuickForm.
     * @param string $elementname Element name.
     */
    protected static function remove_element(\mod_quiz_mod_form $quizform, \MoodleQuickForm $mform, string  $elementname) {
        if ($mform->elementExists($elementname)) {
            $mform->removeElement($elementname);
            $mform->setDefault($elementname, null);
        }
    }

    /**
     * Add help button to the element.
     *
     * @param \mod_quiz_mod_form $quizform the quiz settings form that is being built.
     * @param \MoodleQuickForm $mform the wrapped MoodleQuickForm.
     * @param string $elementname Element name.
     */
    protected static function add_help_button(\mod_quiz_mod_form $quizform, \MoodleQuickForm $mform, string $elementname) {
        if ($mform->elementExists($elementname)) {
            $mform->addHelpButton($elementname, $elementname, 'quizaccess_oqylyq');
        }
    }

    /**
     * Set default value for the element.
     *
     * @param \mod_quiz_mod_form $quizform the quiz settings form that is being built.
     * @param \MoodleQuickForm $mform the wrapped MoodleQuickForm.
     * @param string $elementname Element name.
     * @param mixed $value Default value.
     */
    protected static function set_default(\mod_quiz_mod_form $quizform, \MoodleQuickForm $mform, string  $elementname, $value) {
        $mform->setDefault($elementname, $value);
    }

    /**
     * Set element type.
     *
     * @param \mod_quiz_mod_form $quizform the quiz settings form that is being built.
     * @param \MoodleQuickForm $mform the wrapped MoodleQuickForm.
     * @param string $elementname Element name.
     * @param string $type Type of the form element.
     */
    protected static function set_type(\mod_quiz_mod_form $quizform, \MoodleQuickForm $mform, string $elementname, string $type) {
        $mform->setType($elementname, $type);
    }

    /**
     * Freeze form element.
     *
     * @param \mod_quiz_mod_form $quizform the quiz settings form that is being built.
     * @param \MoodleQuickForm $mform the wrapped MoodleQuickForm.
     * @param string $elementname Element name.
     */
    protected static function freeze_element(\mod_quiz_mod_form $quizform, \MoodleQuickForm $mform, string $elementname) {
        if ($mform->elementExists($elementname)) {
            $mform->freeze($elementname);
        }
    }

    /**
     * Add oqylyq usage element with all available options.
     *
     * @param \mod_quiz_mod_form $quizform the quiz settings form that is being built.
     * @param \MoodleQuickForm $mform the wrapped MoodleQuickForm.
     */
    protected static function add_oqylyq_usage_options(\mod_quiz_mod_form $quizform, \MoodleQuickForm $mform) {
        $element = $mform->createElement(
            'select',
            'oqylyq_proctoring',
            get_string('oqylyq_proctoring', 'quizaccess_oqylyq'),
            self::get_proctoring_options($quizform->get_context())
        );

        self::insert_element($quizform, $mform, $element);
        self::set_type($quizform, $mform, 'oqylyq_proctoring', PARAM_INT);
        self::set_default($quizform, $mform, 'oqylyq_proctoring', self::PROCTORING_DISABLED);
        self::add_help_button($quizform, $mform, 'oqylyq_proctoring');

        if (self::is_conflicting_permissions($quizform->get_context())) {
            self::freeze_element($quizform, $mform, 'oqylyq_proctoring');
        }
    }

    /**
     * Add oqylyq config elements.
     *
     * @param \mod_quiz_mod_form $quizform the quiz settings form that is being built.
     * @param \MoodleQuickForm $mform the wrapped MoodleQuickForm.
     */
    protected static function add_oqylyq_config_elements(\mod_quiz_mod_form $quizform, \MoodleQuickForm $mform) {
        $defaults = self::get_oqylyq_config_element_defaults();
        $types = self::get_oqylyq_config_element_types();

        foreach (self::get_oqylyq_config_elements() as $name => $value) {
            $type = $options = NULL;

            if (is_array($value)) {
                $type = $value['type'];

                if (isset($value['options'])) {
                    $options = $value['options'];
                }
            } else {
                $type = $value;
            }

            if (!self::can_manage_oqylyq_config_setting($name, $quizform->get_context())) {
                $type = 'hidden';
            }

            $element = $mform->createElement($type, $name, get_string($name, 'quizaccess_oqylyq'), $options);
            self::insert_element($quizform, $mform, $element);
            unset($element); // We need to make sure each &element only references the current element in loop.

            self::add_help_button($quizform, $mform, $name);

            if (isset($defaults[$name])) {
                self::set_default($quizform, $mform, $name, $defaults[$name]);
            }

            if (isset($types[$name])) {
                self::set_type($quizform, $mform, $name, $types[$name]);
            }
        }
    }

    /**
     * Add oqylyq header element to  the form.
     *
     * @param \mod_quiz_mod_form $quizform the quiz settings form that is being built.
     * @param \MoodleQuickForm $mform the wrapped MoodleQuickForm.
     */
    protected static function add_oqylyq_header_element(\mod_quiz_mod_form $quizform, \MoodleQuickForm $mform) {
        global  $OUTPUT;

        $element = $mform->createElement('header', 'oqylyq', get_string('oqylyq', 'quizaccess_oqylyq'));
        self::insert_element($quizform, $mform, $element);

        // Display notification about locked settings.
        if (self::is_oqylyq_settings_locked($quizform->get_instance())) {
            $notify = new \core\output\notification(
                get_string('settingsfrozen', 'quizaccess_oqylyq'),
                \core\output\notification::NOTIFY_WARNING
            );

            $notifyelement = $mform->createElement('html', $OUTPUT->render($notify));
            self::insert_element($quizform, $mform, $notifyelement);
        }

        if (self::is_conflicting_permissions($quizform->get_context())) {
            $notify = new \core\output\notification(
                get_string('conflictingsettings', 'quizaccess_oqylyq'),
                \core\output\notification::NOTIFY_WARNING
            );

            $notifyelement = $mform->createElement('html', $OUTPUT->render($notify));
            self::insert_element($quizform, $mform, $notifyelement);
        }
    }

    /**
     * Add setting fields.
     *
     * @param \mod_quiz_mod_form $quizform the quiz settings form that is being built.
     * @param \MoodleQuickForm $mform the wrapped MoodleQuickForm.
     */
    public static function add_oqylyq_settings_fields(\mod_quiz_mod_form $quizform, \MoodleQuickForm $mform) {
        if (self::can_configure_oqylyq($quizform->get_context())) {
            self::add_oqylyq_header_element($quizform, $mform);
            self::add_oqylyq_usage_options($quizform, $mform);
            self::add_oqylyq_config_elements($quizform, $mform);
            self::hide_oqylyq_elements($quizform, $mform);
            self::lock_oqylyq_elements($quizform, $mform);
        }
    }

    /**
     * Hide oqylyq elements if required.
     *
     * @param \mod_quiz_mod_form $quizform the quiz settings form that is being built.
     * @param \MoodleQuickForm $mform the wrapped MoodleQuickForm.
     */
    protected static function hide_oqylyq_elements(\mod_quiz_mod_form $quizform, \MoodleQuickForm $mform) {
        foreach (self::get_quiz_hideifs() as $elname => $rules) {
            if ($mform->elementExists($elname)) {
                foreach ($rules as $hideif) {
                    $mform->disabledIf(
                        $hideif->get_element(),
                        $hideif->get_dependantname(),
                        $hideif->get_condition(),
                        $hideif->get_dependantvalue()
                    );
                }
            }
        }
    }

    /**
     * Lock oqylyq elements if required.
     *
     * @param \mod_quiz_mod_form $quizform the quiz settings form that is being built.
     * @param \MoodleQuickForm $mform the wrapped MoodleQuickForm.
     */
    protected static function lock_oqylyq_elements(\mod_quiz_mod_form $quizform, \MoodleQuickForm $mform) {
        if (self::is_oqylyq_settings_locked($quizform->get_instance()) || self::is_conflicting_permissions($quizform->get_context())) {
            // Freeze common quiz settings.
            self::freeze_element($quizform, $mform, 'oqylyq_proctoring');
            self::freeze_element($quizform, $mform, 'oqylyq_application');
            self::freeze_element($quizform, $mform, 'oqylyq_main_camera_record');
            self::freeze_element($quizform, $mform, 'oqylyq_screen_share_record');
            self::freeze_element($quizform, $mform, 'oqylyq_second_camera_record');
            self::freeze_element($quizform, $mform, 'oqylyq_photo_head_identity');
            self::freeze_element($quizform, $mform, 'oqylyq_id_verification');
            self::freeze_element($quizform, $mform, 'oqylyq_display_checks');
            self::freeze_element($quizform, $mform, 'oqylyq_hdcp_checks');
            self::freeze_element($quizform, $mform, 'oqylyq_content_protect');
            self::freeze_element($quizform, $mform, 'oqylyq_fullscreen_mode');
            self::freeze_element($quizform, $mform, 'oqylyq_focus_detector');
            self::freeze_element($quizform, $mform, 'oqylyq_extension_detector');

            $quizsettings = quiz_settings::get_record(['quizid' => (int) $quizform->get_instance()]);

            // Freeze all oqylyq specific settings.
            foreach (self::get_oqylyq_config_elements() as $element => $type) {
                self::freeze_element($quizform, $mform, $element);
            }
        }
    }

    /**
     * Get the type of element for each of the form elements in quiz settings.
     *
     * Contains all setting elements. Array key is name of 'form element'/'database column (excluding prefix)'.
     *
     * @return array All quiz form elements to be added and their types.
     */
    public static function get_oqylyq_config_elements() : array {
        return [
            'oqylyq_application' => ['type' => 'select', 'options' => ['browser' => 'Browser', 'tray' => 'TrayApp', 'desktop' => 'Desktop Application']],
            'oqylyq_main_camera_record' => 'selectyesno',
            'oqylyq_screen_share_record' => 'selectyesno',
            'oqylyq_second_camera_record' => 'selectyesno',
            'oqylyq_photo_head_identity' => 'selectyesno',
            'oqylyq_id_verification' => 'selectyesno',
            'oqylyq_display_checks' => 'selectyesno',
            'oqylyq_hdcp_checks' => 'selectyesno',
            'oqylyq_content_protect' => 'selectyesno',
            'oqylyq_extension_detector' => 'selectyesno',
            'oqylyq_fullscreen_mode' => 'selectyesno',
            'oqylyq_focus_detector' => 'selectyesno',
        ];
    }


    /**
     * Get the types of the quiz settings elements.
     * @return array List of types for the setting elements.
     */
    public static function get_oqylyq_config_element_types() : array {
        return [
            'oqylyq_application' => PARAM_ALPHANUMEXT,
            'oqylyq_main_camera_record' => PARAM_BOOL,
            'oqylyq_screen_share_record' => PARAM_BOOL,
            'oqylyq_second_camera_record' => PARAM_BOOL,
            'oqylyq_photo_head_identity' => PARAM_BOOL,
            'oqylyq_id_verification' => PARAM_BOOL,
            'oqylyq_display_checks' => PARAM_BOOL,
            'oqylyq_hdcp_checks' => PARAM_BOOL,
            'oqylyq_content_protect' => PARAM_BOOL,
            'oqylyq_extension_detector' => PARAM_BOOL,
            'oqylyq_fullscreen_mode' => PARAM_BOOL,
            'oqylyq_focus_detector' => PARAM_BOOL,
        ];
    }

    /**
     * Check that we have conflicting permissions.
     *
     * In Some point we can have settings save by the person who use specific
     * type of oqylyq usage (e.g. use templates). But then another person who can't
     * use template (but still can update other settings) edit the same quiz. This is
     * conflict of permissions and we'd like to build the settings form having this in
     * mind.
     *
     * @param \context $context Context used with capability checking.
     *
     * @return bool
     */
    public static function is_conflicting_permissions(\context $context) {
        if ($context instanceof \context_course) {
            return false;
        }

        $settings = quiz_settings::get_record(['cmid' => (int) $context->instanceid]);

        if (empty($settings)) {
            return false;
        }

        return false;
    }

    /**
     * Returns a list of all options of oqylyq usage.
     *
     * @param \context $context Context used with capability checking selection options.
     * @return array
     */
    public static function get_proctoring_options(\context $context) : array {
        return [
            self::PROCTORING_DISABLED => get_string('no'),
            self::PROCTORING_ENABLED => get_string('yes')
        ];
    }

    /**
     * Get the default values of the quiz settings.
     *
     * Array key is name of 'form element'/'database column (excluding prefix)'.
     *
     * @return array List of settings and their defaults.
     */
    public static function get_oqylyq_config_element_defaults() : array {
        return [
            'oqylyq_application' => 'browser',
            'oqylyq_main_camera_record' => 1,
            'oqylyq_screen_share_record' => 1,
            'oqylyq_second_camera_record' => 0,
            'oqylyq_photo_head_identity' => 1,
            'oqylyq_id_verification' => 0,
            'oqylyq_display_checks' => 1,
            'oqylyq_hdcp_checks' => 0,
            'oqylyq_content_protect' => 0,
            'oqylyq_extension_detector' => 1,
            'oqylyq_fullscreen_mode' => 1,
            'oqylyq_focus_detector' => 1,
        ];
    }

    /**
     * Check if the current user can configure Oqylyq.
     *
     * @param \context $context Context to check access in.
     * @return bool
     */
    public static function can_configure_oqylyq(\context $context) : bool {
        return has_capability('quizaccess/oqylyq:manage_oqylyq_proctoring', $context);
    }

    /**
     * Check if the current user can manage provided oqylyq setting.
     *
     * @param string $settingname Name of the setting.
     * @param \context $context Context to check access in.
     * @return bool
     */
    public static function can_manage_oqylyq_config_setting(string $settingname, \context $context) : bool {
        $capsttocheck = [];

        foreach (self::get_oqylyq_settings_map() as $type => $settings) {
            $capsttocheck = self::build_config_capabilities_to_check($settingname, $settings);
            if (!empty($capsttocheck)) {
                break;
            }
        }

        foreach ($capsttocheck as $capability) {
            // Capability must exist.
            if (!$capinfo = get_capability_info($capability)) {
                throw new \coding_exception("Capability '{$capability}' was not found! This has to be fixed in code.");
            }
        }

        return has_all_capabilities($capsttocheck, $context);
    }

    /**
     * Helper method to build a list of capabilities to check.
     *
     * @param string $settingname Given setting name to build caps for.
     * @param array $settings A list of settings to go through.
     * @return array
     */
    protected static function build_config_capabilities_to_check(string $settingname, array $settings) : array {
        $capsttocheck = [];

        foreach ($settings as $setting => $children) {
            if ($setting == $settingname) {
                $capsttocheck[$setting] = self::build_setting_capability_name($setting);
                break; // Found what we need exit the loop.
            }

            // Recursively check all children.
            $capsttocheck = self::build_config_capabilities_to_check($settingname, $children);
            if (!empty($capsttocheck)) {
                // Matching child found, add the parent capability to the list of caps to check.
                $capsttocheck[$setting] = self::build_setting_capability_name($setting);
                break; // Found what we need exit the loop.
            }
        }

        return $capsttocheck;
    }

    /**
     * Helper method to return a map of all settings.
     *
     * @return array
     */
    public static function get_oqylyq_settings_map() : array {
        return [
            self::PROCTORING_DISABLED => [

            ],
            self::PROCTORING_ENABLED => [
                'oqylyq_application' => [],
                'oqylyq_main_camera_record' => [],
                'oqylyq_screen_share_record' => [],
                'oqylyq_second_camera_record' => [],
                'oqylyq_photo_head_identity' => [],
                'oqylyq_id_verification' => [],
                'oqylyq_display_checks' => [],
                'oqylyq_hdcp_checks' => [],
                'oqylyq_content_protect' => [],
                'oqylyq_extension_detector' => [],
                'oqylyq_fullscreen_mode' => [],
                'oqylyq_focus_detector' => [],
            ]
        ];
    }

    /**
     * Get allowed settings for provided oqylyq usage type.
     *
     * @param int $enabledproctoring oqylyq usage type.
     * @return array
     */
    private static function get_allowed_settings(int $enabledproctoring) : array {
        $result = [];
        $map = self::get_oqylyq_settings_map();

        if (!key_exists($enabledproctoring, $map)) {
            return $result;
        }

        return self::build_allowed_settings($map[$enabledproctoring]);
    }

    /**
     * Recursive method to build a list of allowed settings.
     *
     * @param array $settings A list of settings from settings map.
     * @return array
     */
    private static function build_allowed_settings(array $settings) : array {
        $result = [];

        foreach ($settings as $name => $children) {
            $result[] = $name;
            foreach ($children as $childname => $child) {
                $result[] = $childname;
                $result = array_merge($result, self::build_allowed_settings($child));
            }
        }

        return $result;
    }

    /**
     * Get the conditions that an element should be hid in the form. Expects matching using 'eq'.
     *
     * Array key is name of 'form element'/'database column (excluding prefix)'.
     * Values are instances of hideif_rule class.
     *
     * @return array List of rules per element.
     */
    public static function get_quiz_hideifs() : array {
        $hideifs = [];

        // We are building rules based on the settings map, that means children will be dependant on parent.
        // In most cases it's all pretty standard.
        // However it could be some specific cases for some fields, which will be overridden later.
        foreach (self::get_oqylyq_settings_map() as $type => $settings) {
            foreach ($settings as $setting => $children) {
                $hideifs[$setting][] = new hideif_rule($setting, 'oqylyq_proctoring', 'noteq', $type);

                foreach ($children as $childname => $child) {
                    $hideifs[$childname][] = new hideif_rule($childname, 'oqylyq_proctoring', 'noteq', $type);
                    $hideifs[$childname][] = new hideif_rule($childname, $setting, 'eq', 0);
                }
            }
        }

        return $hideifs;
    }

    /**
     * Build a capability name for the provided oqylyq setting.
     *
     * @param string $settingname Name of the setting.
     * @return string
     */
    public static function build_setting_capability_name(string $settingname) : string {
        if (!key_exists($settingname, self::get_oqylyq_config_elements())) {
            throw new \coding_exception('Incorrect Oqylyq quiz setting ' . $settingname);
        }

        return 'quizaccess/oqylyq:manage_' . $settingname;
    }

    /**
     * Check if settings is locked.
     *
     * @param int $quizid Quiz ID.
     * @return bool
     */
    public static function is_oqylyq_settings_locked($quizid) : bool {
        /* force */
        return false;

        if (empty($quizid)) {
            return false;
        }

        return quiz_has_attempts($quizid);
    }

    /**
     * Filter a standard class by prefix.
     *
     * @param stdClass $settings Quiz settings object.
     * @return stdClass Filtered object.
     */
    private static function filter_by_prefix(\stdClass $settings): stdClass {
        $newsettings = new \stdClass();
        foreach ($settings as $name => $setting) {
            // Only add it, if not there.
            if (strpos($name, "oqylyq_") === 0) {
                $newsettings->$name = $setting; // Add new key.
            }
        }
        return $newsettings;
    }

    /**
     * Filter settings based on the setting map. Set value of not allowed settings to null.
     *
     * @param stdClass $settings Quiz settings.
     * @return \stdClass
     */
    private static function filter_by_settings_map(stdClass $settings) : stdClass {
        if (!isset($settings->oqylyq_proctoring)) {
            return $settings;
        }

        $newsettings = new \stdClass();
        $newsettings->oqylyq_proctoring = $settings->oqylyq_proctoring;
        $allowedsettings = self::get_allowed_settings((int)$newsettings->oqylyq_proctoring);
        unset($settings->oqylyq_proctoring);

        foreach ($settings as $name => $value) {
            if (!in_array($name, $allowedsettings)) {
                $newsettings->$name = null;
            } else {
                $newsettings->$name = $value;
            }
        }

        return $newsettings;
    }

    /**
     * Filter quiz settings for this plugin only.
     *
     * @param stdClass $settings Quiz settings.
     * @return stdClass Filtered settings.
     */
    public static function filter_plugin_settings(stdClass $settings) : stdClass {
        $settings = self::filter_by_prefix($settings);
        $settings = self::filter_by_settings_map($settings);

        return self::strip_all_prefixes($settings);
    }

    /**
     * Strip the oqylyq_ prefix from each setting key.
     *
     * @param \stdClass $settings Object containing settings.
     * @return \stdClass The modified settings object.
     */
    private static function strip_all_prefixes(\stdClass $settings): stdClass {
        $newsettings = new \stdClass();
        foreach ($settings as $name => $setting) {
            $newname = preg_replace("/^oqylyq_/", "", $name);
            $newsettings->$newname = $setting; // Add new key.
        }
        return $newsettings;
    }

    /**
     * Add prefix to string.
     *
     * @param string $name String to add prefix to.
     * @return string String with prefix.
     */
    public static function add_prefix(string $name): string {
        if (strpos($name, 'oqylyq_') !== 0) {
            $name = 'oqylyq_' . $name;
        }
        return $name;
    }
}
