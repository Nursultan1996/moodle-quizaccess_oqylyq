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

use quizaccess_oqylyq\local\quiz_settings;

defined('MOODLE_INTERNAL') || die();

/**
 * Tests for the quiz_settings persistent model.
 *
 * @package    quizaccess_oqylyq
 * @copyright  2020 Ertumar LLP
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \quizaccess_oqylyq\local\quiz_settings
 */
class quiz_settings_test extends \advanced_testcase {

    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest(true);
    }

    /**
     * Test that the TABLE constant has the expected value.
     */
    public function test_table_constant(): void {
        $this->assertSame('quizaccess_oqylyq_settings', quiz_settings::TABLE);
    }

    /**
     * Test that define_properties includes all 15 expected fields.
     */
    public function test_define_properties_has_all_fields(): void {
        $settings = new quiz_settings();
        $properties = $settings->properties_definition();

        $expectedfields = [
            'quizid', 'cmid', 'proctoring', 'application',
            'main_camera_record', 'second_camera_record', 'screen_share_record',
            'photo_head_identity', 'id_verification', 'display_checks',
            'hdcp_checks', 'content_protect', 'fullscreen_mode',
            'focus_detector', 'extension_detector',
        ];

        foreach ($expectedfields as $field) {
            $this->assertArrayHasKey($field, $properties, "Property '{$field}' should exist.");
        }
    }

    /**
     * Test creating a persistent and reading it back from the database.
     */
    public function test_create_and_read(): void {
        $generator = $this->getDataGenerator()->get_plugin_generator('quizaccess_oqylyq');
        $settings = $generator->create_quiz_settings([
            'quizid' => 101,
            'cmid' => 201,
            'proctoring' => 1,
            'application' => 'browser',
        ]);

        $read = new quiz_settings($settings->get('id'));

        $this->assertEquals($settings->get('quizid'), $read->get('quizid'));
        $this->assertEquals($settings->get('cmid'), $read->get('cmid'));
        $this->assertEquals($settings->get('proctoring'), $read->get('proctoring'));
        $this->assertEquals($settings->get('application'), $read->get('application'));
    }

    /**
     * Test get_by_quiz_id returns the correct record.
     */
    public function test_get_by_quiz_id_found(): void {
        $generator = $this->getDataGenerator()->get_plugin_generator('quizaccess_oqylyq');
        $settings = $generator->create_quiz_settings([
            'quizid' => 301,
            'cmid' => 401,
        ]);

        $found = quiz_settings::get_by_quiz_id(301);

        $this->assertNotFalse($found);
        $this->assertEquals(301, $found->get('quizid'));
        $this->assertEquals(401, $found->get('cmid'));
    }

    /**
     * Test get_by_quiz_id returns false for a nonexistent quiz.
     */
    public function test_get_by_quiz_id_not_found(): void {
        $result = quiz_settings::get_by_quiz_id(999999);
        $this->assertFalse($result);
    }

    /**
     * Test updating settings via set() and save().
     */
    public function test_update_settings(): void {
        $generator = $this->getDataGenerator()->get_plugin_generator('quizaccess_oqylyq');
        $settings = $generator->create_quiz_settings([
            'quizid' => 501,
            'cmid' => 601,
            'application' => 'browser',
        ]);

        $settings->set('application', 'tray');
        $settings->save();

        $updated = quiz_settings::get_by_quiz_id(501);
        $this->assertSame('tray', $updated->get('application'));
    }

    /**
     * Test deleting settings.
     */
    public function test_delete_settings(): void {
        $generator = $this->getDataGenerator()->get_plugin_generator('quizaccess_oqylyq');
        $settings = $generator->create_quiz_settings([
            'quizid' => 701,
            'cmid' => 801,
        ]);

        $settings->delete();

        $result = quiz_settings::get_by_quiz_id(701);
        $this->assertFalse($result);
    }

    /**
     * Test that default values match the expected persistent property defaults.
     */
    public function test_default_values(): void {
        $settings = new quiz_settings();

        $this->assertEquals(1, $settings->get('proctoring'));
        $this->assertSame('browser', $settings->get('application'));
        $this->assertEquals(0, $settings->get('id_verification'));
        $this->assertEquals(0, $settings->get('fullscreen_mode'));
        $this->assertEquals(0, $settings->get('focus_detector'));
        $this->assertEquals(0, $settings->get('extension_detector'));
        $this->assertEquals(1, $settings->get('main_camera_record'));
        $this->assertEquals(1, $settings->get('second_camera_record'));
        $this->assertEquals(1, $settings->get('screen_share_record'));
        $this->assertEquals(1, $settings->get('photo_head_identity'));
        $this->assertEquals(1, $settings->get('display_checks'));
        $this->assertEquals(1, $settings->get('hdcp_checks'));
        $this->assertEquals(1, $settings->get('content_protect'));
    }

    /**
     * Test that nullable fields can be set to null without validation error.
     */
    public function test_null_allowed_fields(): void {
        $generator = $this->getDataGenerator()->get_plugin_generator('quizaccess_oqylyq');
        $settings = $generator->create_quiz_settings([
            'quizid' => 901,
            'cmid' => 1001,
        ]);

        $settings->set('proctoring', null);
        $settings->set('application', null);
        $settings->set('main_camera_record', null);
        $settings->save();

        $updated = quiz_settings::get_by_quiz_id(901);
        $this->assertNull($updated->get('proctoring'));
        $this->assertNull($updated->get('application'));
        $this->assertNull($updated->get('main_camera_record'));
    }
}
