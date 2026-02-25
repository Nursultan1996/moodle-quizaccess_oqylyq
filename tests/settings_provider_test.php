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

namespace quizaccess_oqylyq;

use quizaccess_oqylyq\local\settings_provider;
use quizaccess_oqylyq\local\hideif_rule;

defined('MOODLE_INTERNAL') || die();

/**
 * Tests for the settings_provider class.
 *
 * @package    quizaccess_oqylyq
 * @copyright  2020 Ertumar LLP
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \quizaccess_oqylyq\local\settings_provider
 */
class settings_provider_test extends \advanced_testcase {

    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest(true);
    }

    /**
     * Test that constants have expected values.
     */
    public function test_constants(): void {
        $this->assertSame(0, settings_provider::PROCTORING_DISABLED);
        $this->assertSame(1, settings_provider::PROCTORING_ENABLED);
    }

    /**
     * Test that get_oqylyq_config_elements returns 12 elements.
     */
    public function test_get_config_elements(): void {
        $elements = settings_provider::get_oqylyq_config_elements();

        $this->assertCount(12, $elements);
        $this->assertArrayHasKey('oqylyq_application', $elements);
        $this->assertArrayHasKey('oqylyq_main_camera_record', $elements);
        $this->assertArrayHasKey('oqylyq_screen_share_record', $elements);
        $this->assertArrayHasKey('oqylyq_second_camera_record', $elements);
        $this->assertArrayHasKey('oqylyq_photo_head_identity', $elements);
        $this->assertArrayHasKey('oqylyq_id_verification', $elements);
        $this->assertArrayHasKey('oqylyq_display_checks', $elements);
        $this->assertArrayHasKey('oqylyq_hdcp_checks', $elements);
        $this->assertArrayHasKey('oqylyq_content_protect', $elements);
        $this->assertArrayHasKey('oqylyq_extension_detector', $elements);
        $this->assertArrayHasKey('oqylyq_fullscreen_mode', $elements);
        $this->assertArrayHasKey('oqylyq_focus_detector', $elements);
    }

    /**
     * Test that get_oqylyq_config_element_defaults returns 12 defaults with expected values.
     */
    public function test_get_config_element_defaults(): void {
        $defaults = settings_provider::get_oqylyq_config_element_defaults();

        $this->assertCount(12, $defaults);
        $this->assertSame('browser', $defaults['oqylyq_application']);
        $this->assertEquals(1, $defaults['oqylyq_main_camera_record']);
        $this->assertEquals(1, $defaults['oqylyq_screen_share_record']);
        $this->assertEquals(0, $defaults['oqylyq_second_camera_record']);
        $this->assertEquals(1, $defaults['oqylyq_photo_head_identity']);
        $this->assertEquals(0, $defaults['oqylyq_id_verification']);
        $this->assertEquals(1, $defaults['oqylyq_display_checks']);
        $this->assertEquals(0, $defaults['oqylyq_hdcp_checks']);
        $this->assertEquals(0, $defaults['oqylyq_content_protect']);
        $this->assertEquals(1, $defaults['oqylyq_extension_detector']);
        $this->assertEquals(1, $defaults['oqylyq_fullscreen_mode']);
        $this->assertEquals(1, $defaults['oqylyq_focus_detector']);
    }

    /**
     * Test that get_oqylyq_config_element_types returns 12 types.
     */
    public function test_get_config_element_types(): void {
        $types = settings_provider::get_oqylyq_config_element_types();

        $this->assertCount(12, $types);
        $this->assertEquals(PARAM_ALPHANUMEXT, $types['oqylyq_application']);

        // All others should be PARAM_BOOL.
        $booltypes = $types;
        unset($booltypes['oqylyq_application']);
        foreach ($booltypes as $name => $type) {
            $this->assertEquals(PARAM_BOOL, $type, "Type for '{$name}' should be PARAM_BOOL.");
        }
    }

    /**
     * Test can_configure_oqylyq returns true for admin.
     */
    public function test_can_configure_oqylyq_admin(): void {
        $this->setAdminUser();

        $course = $this->getDataGenerator()->create_course();
        $quiz = $this->getDataGenerator()->create_module('quiz', ['course' => $course->id]);
        $context = \context_module::instance($quiz->cmid);

        $this->assertTrue(settings_provider::can_configure_oqylyq($context));
    }

    /**
     * Test can_configure_oqylyq returns false for student.
     */
    public function test_can_configure_oqylyq_student(): void {
        $course = $this->getDataGenerator()->create_course();
        $student = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($student->id, $course->id, 'student');
        $this->setUser($student);

        $quiz = $this->getDataGenerator()->create_module('quiz', ['course' => $course->id]);
        $context = \context_module::instance($quiz->cmid);

        $this->assertFalse(settings_provider::can_configure_oqylyq($context));
    }

    /**
     * Test can_manage_oqylyq_config_setting returns true for user with capability.
     */
    public function test_can_manage_config_setting_with_cap(): void {
        $this->setAdminUser();

        $course = $this->getDataGenerator()->create_course();
        $quiz = $this->getDataGenerator()->create_module('quiz', ['course' => $course->id]);
        $context = \context_module::instance($quiz->cmid);

        $this->assertTrue(
            settings_provider::can_manage_oqylyq_config_setting('oqylyq_application', $context)
        );
    }

    /**
     * Test can_manage_oqylyq_config_setting returns false for user without capability.
     */
    public function test_can_manage_config_setting_without_cap(): void {
        $course = $this->getDataGenerator()->create_course();
        $student = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($student->id, $course->id, 'student');
        $this->setUser($student);

        $quiz = $this->getDataGenerator()->create_module('quiz', ['course' => $course->id]);
        $context = \context_module::instance($quiz->cmid);

        $this->assertFalse(
            settings_provider::can_manage_oqylyq_config_setting('oqylyq_application', $context)
        );
    }

    /**
     * Test build_setting_capability_name with a valid setting name.
     */
    public function test_build_capability_name_valid(): void {
        $result = settings_provider::build_setting_capability_name('oqylyq_application');
        $this->assertSame('quizaccess/oqylyq:manage_oqylyq_application', $result);
    }

    /**
     * Test build_setting_capability_name throws coding_exception for invalid setting.
     */
    public function test_build_capability_name_invalid(): void {
        $this->expectException(\coding_exception::class);
        settings_provider::build_setting_capability_name('invalid_setting');
    }

    /**
     * Test get_oqylyq_settings_map structure.
     */
    public function test_get_settings_map_structure(): void {
        $map = settings_provider::get_oqylyq_settings_map();

        $this->assertArrayHasKey(0, $map);
        $this->assertArrayHasKey(1, $map);
        $this->assertEmpty($map[0]);
        $this->assertCount(12, $map[1]);
    }

    /**
     * Test get_proctoring_options returns expected array.
     */
    public function test_get_proctoring_options(): void {
        $course = $this->getDataGenerator()->create_course();
        $quiz = $this->getDataGenerator()->create_module('quiz', ['course' => $course->id]);
        $context = \context_module::instance($quiz->cmid);

        $options = settings_provider::get_proctoring_options($context);

        $this->assertArrayHasKey(0, $options);
        $this->assertArrayHasKey(1, $options);
        $this->assertSame(get_string('no'), $options[0]);
        $this->assertSame(get_string('yes'), $options[1]);
    }

    /**
     * Test filter_plugin_settings strips the oqylyq_ prefix.
     */
    public function test_filter_plugin_settings_strips_prefix(): void {
        $settings = (object) [
            'oqylyq_proctoring' => 1,
            'oqylyq_application' => 'browser',
        ];

        $result = settings_provider::filter_plugin_settings($settings);

        $this->assertTrue(property_exists($result, 'proctoring'));
        $this->assertTrue(property_exists($result, 'application'));
        $this->assertFalse(property_exists($result, 'oqylyq_proctoring'));
    }

    /**
     * Test filter_plugin_settings removes non-plugin fields.
     */
    public function test_filter_plugin_settings_removes_non_plugin(): void {
        $settings = (object) [
            'oqylyq_proctoring' => 1,
            'oqylyq_application' => 'browser',
            'name' => 'Test Quiz',
            'course' => 1,
        ];

        $result = settings_provider::filter_plugin_settings($settings);

        $this->assertFalse(property_exists($result, 'name'));
        $this->assertFalse(property_exists($result, 'course'));
    }

    /**
     * Test filter_plugin_settings nullifies config values when proctoring is disabled.
     */
    public function test_filter_plugin_settings_nullifies_when_disabled(): void {
        $settings = (object) [
            'oqylyq_proctoring' => 0,
            'oqylyq_application' => 'browser',
            'oqylyq_main_camera_record' => 1,
        ];

        $result = settings_provider::filter_plugin_settings($settings);

        $this->assertEquals(0, $result->proctoring);
        $this->assertNull($result->application);
        $this->assertNull($result->main_camera_record);
    }

    /**
     * Test filter_plugin_settings retains values when proctoring is enabled.
     */
    public function test_filter_plugin_settings_keeps_when_enabled(): void {
        $settings = (object) [
            'oqylyq_proctoring' => 1,
            'oqylyq_application' => 'tray',
            'oqylyq_main_camera_record' => 1,
        ];

        $result = settings_provider::filter_plugin_settings($settings);

        $this->assertEquals(1, $result->proctoring);
        $this->assertSame('tray', $result->application);
        $this->assertEquals(1, $result->main_camera_record);
    }

    /**
     * Test add_prefix adds oqylyq_ prefix to a setting name.
     */
    public function test_add_prefix(): void {
        $this->assertSame('oqylyq_proctoring', settings_provider::add_prefix('proctoring'));
    }

    /**
     * Test add_prefix does not double-add when prefix already exists.
     */
    public function test_add_prefix_no_double(): void {
        $this->assertSame('oqylyq_proctoring', settings_provider::add_prefix('oqylyq_proctoring'));
    }

    /**
     * Test is_oqylyq_settings_locked always returns false.
     *
     * Documents the bypass on line 592 of settings_provider.php.
     */
    public function test_is_settings_locked_returns_false(): void {
        $this->assertFalse(settings_provider::is_oqylyq_settings_locked(1));
        $this->assertFalse(settings_provider::is_oqylyq_settings_locked(0));
        $this->assertFalse(settings_provider::is_oqylyq_settings_locked(999));
    }

    /**
     * Test is_conflicting_permissions always returns false.
     *
     * Documents the always-false return on line 373 of settings_provider.php.
     */
    public function test_is_conflicting_permissions_returns_false(): void {
        $course = $this->getDataGenerator()->create_course();
        $quiz = $this->getDataGenerator()->create_module('quiz', ['course' => $course->id]);
        $context = \context_module::instance($quiz->cmid);

        $this->assertFalse(settings_provider::is_conflicting_permissions($context));
    }

    /**
     * Test get_quiz_hideifs structure: each PROCTORING_ENABLED setting has hideif rules.
     */
    public function test_get_quiz_hideifs_structure(): void {
        $hideifs = settings_provider::get_quiz_hideifs();

        $this->assertIsArray($hideifs);

        $map = settings_provider::get_oqylyq_settings_map();
        foreach ($map[settings_provider::PROCTORING_ENABLED] as $setting => $children) {
            $this->assertArrayHasKey($setting, $hideifs, "Hideif rules should exist for '{$setting}'.");
            $this->assertNotEmpty($hideifs[$setting]);
            foreach ($hideifs[$setting] as $rule) {
                $this->assertInstanceOf(hideif_rule::class, $rule);
            }
        }
    }

    /**
     * Test get_quiz_hideifs: children depend on oqylyq_proctoring with 'noteq'.
     */
    public function test_get_quiz_hideifs_dependencies(): void {
        $hideifs = settings_provider::get_quiz_hideifs();

        $map = settings_provider::get_oqylyq_settings_map();
        foreach ($map[settings_provider::PROCTORING_ENABLED] as $setting => $children) {
            $rules = $hideifs[$setting];
            $hasproctoring = false;

            foreach ($rules as $rule) {
                if ($rule->get_dependantname() === 'oqylyq_proctoring' && $rule->get_condition() === 'noteq') {
                    $hasproctoring = true;
                    break;
                }
            }

            $this->assertTrue($hasproctoring, "Setting '{$setting}' should have a 'noteq' dependency on 'oqylyq_proctoring'.");
        }
    }
}
