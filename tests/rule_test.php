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
use quizaccess_oqylyq\local\settings_provider;

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/../rule.php');

/**
 * Tests for the main quizaccess_oqylyq rule class.
 *
 * @package    quizaccess_oqylyq
 * @copyright  2020 Ertumar LLP
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \quizaccess_oqylyq
 */
class rule_test extends \advanced_testcase {

    /** @var array Saved $_SERVER superglobal. */
    private $originalserver;

    /** @var array Saved $_COOKIE superglobal. */
    private $originalcookie;

    /** @var array Saved $_REQUEST superglobal. */
    private $originalrequest;

    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest(true);
        $this->originalserver = $_SERVER;
        $this->originalcookie = $_COOKIE;
        $this->originalrequest = $_REQUEST;
    }

    protected function tearDown(): void {
        $_SERVER = $this->originalserver;
        $_COOKIE = $this->originalcookie;
        $_REQUEST = $this->originalrequest;
        parent::tearDown();
    }

    /**
     * Create a quiz object suitable for the rule class.
     *
     * @param array $quizoptions Additional quiz options.
     * @return object The quiz object.
     */
    private function create_quiz_object(array $quizoptions = []): object {
        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $quiz = $generator->create_module('quiz', array_merge(
            ['course' => $course->id],
            $quizoptions
        ));

        if (class_exists('\mod_quiz\quiz_settings')) {
            return \mod_quiz\quiz_settings::create($quiz->id);
        }

        return \quiz::create($quiz->id);
    }

    // -------------------------------------------------------
    // make() factory tests.
    // -------------------------------------------------------

    /**
     * Test make returns null when no quiz_settings record exists.
     */
    public function test_make_returns_null_no_settings(): void {
        $quizobj = $this->create_quiz_object();
        $result = \quizaccess_oqylyq::make($quizobj, time(), false);
        $this->assertNull($result);
    }

    /**
     * Test make returns null when proctoring is disabled.
     */
    public function test_make_returns_null_proctoring_disabled(): void {
        $quizobj = $this->create_quiz_object();

        $this->getDataGenerator()->get_plugin_generator('quizaccess_oqylyq')
            ->create_quiz_settings([
                'quizid' => $quizobj->get_quizid(),
                'cmid' => $quizobj->get_cmid(),
                'proctoring' => 0,
            ]);

        $result = \quizaccess_oqylyq::make($quizobj, time(), false);
        $this->assertNull($result);
    }

    /**
     * Test make returns a quizaccess_oqylyq instance when proctoring is enabled.
     */
    public function test_make_returns_instance_proctoring_enabled(): void {
        $quizobj = $this->create_quiz_object();

        $this->getDataGenerator()->get_plugin_generator('quizaccess_oqylyq')
            ->create_quiz_settings([
                'quizid' => $quizobj->get_quizid(),
                'cmid' => $quizobj->get_cmid(),
                'proctoring' => 1,
            ]);

        $result = \quizaccess_oqylyq::make($quizobj, time(), false);
        $this->assertInstanceOf(\quizaccess_oqylyq::class, $result);
    }

    // -------------------------------------------------------
    // description() test.
    // -------------------------------------------------------

    /**
     * Test description returns an array with proctoring_required lang string.
     */
    public function test_description(): void {
        $quizobj = $this->create_quiz_object();

        $this->getDataGenerator()->get_plugin_generator('quizaccess_oqylyq')
            ->create_quiz_settings([
                'quizid' => $quizobj->get_quizid(),
                'cmid' => $quizobj->get_cmid(),
                'proctoring' => 1,
            ]);

        $rule = \quizaccess_oqylyq::make($quizobj, time(), false);
        $desc = $rule->description();

        $this->assertIsArray($desc);
        $this->assertCount(1, $desc);
        $this->assertSame(
            get_string('proctoring_required', 'quizaccess_oqylyq'),
            $desc[0]
        );
    }

    // -------------------------------------------------------
    // get_settings_sql() tests.
    // -------------------------------------------------------

    /**
     * Test get_settings_sql returns an array with 3 elements.
     */
    public function test_get_settings_sql_structure(): void {
        $result = \quizaccess_oqylyq::get_settings_sql(1);
        $this->assertIsArray($result);
        $this->assertCount(3, $result);
    }

    /**
     * Test get_settings_sql fields contain all 13 oqylyq-prefixed aliases.
     */
    public function test_get_settings_sql_fields(): void {
        $result = \quizaccess_oqylyq::get_settings_sql(1);
        $fields = $result[0];

        $expectedaliases = [
            'oqylyq_proctoring', 'oqylyq_application',
            'oqylyq_main_camera_record', 'oqylyq_screen_share_record',
            'oqylyq_second_camera_record', 'oqylyq_photo_head_identity',
            'oqylyq_id_verification', 'oqylyq_display_checks',
            'oqylyq_hdcp_checks', 'oqylyq_content_protect',
            'oqylyq_fullscreen_mode', 'oqylyq_extension_detector',
            'oqylyq_focus_detector',
        ];

        foreach ($expectedaliases as $alias) {
            $this->assertStringContainsString($alias, $fields, "Fields should contain alias '{$alias}'.");
        }
    }

    /**
     * Test get_settings_sql join is a LEFT JOIN on quizaccess_oqylyq_settings.
     */
    public function test_get_settings_sql_join(): void {
        $result = \quizaccess_oqylyq::get_settings_sql(1);
        $join = $result[1];

        $this->assertStringContainsString('LEFT JOIN', $join);
        $this->assertStringContainsString('quizaccess_oqylyq_settings', $join);
    }

    // -------------------------------------------------------
    // save_settings() tests.
    // -------------------------------------------------------

    /**
     * Test save_settings creates a record when proctoring is enabled.
     */
    public function test_save_settings_creates_record(): void {
        $this->setAdminUser();

        $course = $this->getDataGenerator()->create_course();
        $quiz = $this->getDataGenerator()->create_module('quiz', ['course' => $course->id]);

        $quizdata = (object) [
            'id' => $quiz->id,
            'course' => $course->id,
            'coursemodule' => $quiz->cmid,
            'oqylyq_proctoring' => 1,
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

        \quizaccess_oqylyq::save_settings($quizdata);

        $settings = quiz_settings::get_by_quiz_id($quiz->id);
        $this->assertNotFalse($settings);
        $this->assertEquals(1, $settings->get('proctoring'));
        $this->assertSame('browser', $settings->get('application'));
    }

    /**
     * Test save_settings updates an existing record.
     */
    public function test_save_settings_updates_record(): void {
        $this->setAdminUser();

        $course = $this->getDataGenerator()->create_course();
        $quiz = $this->getDataGenerator()->create_module('quiz', ['course' => $course->id]);

        $this->getDataGenerator()->get_plugin_generator('quizaccess_oqylyq')
            ->create_quiz_settings([
                'quizid' => $quiz->id,
                'cmid' => $quiz->cmid,
                'proctoring' => 1,
                'application' => 'browser',
            ]);

        $quizdata = (object) [
            'id' => $quiz->id,
            'course' => $course->id,
            'coursemodule' => $quiz->cmid,
            'oqylyq_proctoring' => 1,
            'oqylyq_application' => 'tray',
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

        \quizaccess_oqylyq::save_settings($quizdata);

        $settings = quiz_settings::get_by_quiz_id($quiz->id);
        $this->assertSame('tray', $settings->get('application'));
    }

    /**
     * Test save_settings deletes the record when proctoring is set to disabled.
     */
    public function test_save_settings_deletes_when_disabled(): void {
        $this->setAdminUser();

        $course = $this->getDataGenerator()->create_course();
        $quiz = $this->getDataGenerator()->create_module('quiz', ['course' => $course->id]);

        $this->getDataGenerator()->get_plugin_generator('quizaccess_oqylyq')
            ->create_quiz_settings([
                'quizid' => $quiz->id,
                'cmid' => $quiz->cmid,
                'proctoring' => 1,
            ]);

        $quizdata = (object) [
            'id' => $quiz->id,
            'course' => $course->id,
            'coursemodule' => $quiz->cmid,
            'oqylyq_proctoring' => 0,
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

        \quizaccess_oqylyq::save_settings($quizdata);

        $result = quiz_settings::get_by_quiz_id($quiz->id);
        $this->assertFalse($result);
    }

    /**
     * Test save_settings does not create a record when proctoring is disabled and no record exists.
     */
    public function test_save_settings_no_create_when_disabled(): void {
        $this->setAdminUser();

        $course = $this->getDataGenerator()->create_course();
        $quiz = $this->getDataGenerator()->create_module('quiz', ['course' => $course->id]);

        $quizdata = (object) [
            'id' => $quiz->id,
            'course' => $course->id,
            'coursemodule' => $quiz->cmid,
            'oqylyq_proctoring' => 0,
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

        \quizaccess_oqylyq::save_settings($quizdata);

        $result = quiz_settings::get_by_quiz_id($quiz->id);
        $this->assertFalse($result);
    }

    // -------------------------------------------------------
    // delete_settings() tests.
    // -------------------------------------------------------

    /**
     * Test delete_settings removes an existing record.
     */
    public function test_delete_settings_removes_record(): void {
        $course = $this->getDataGenerator()->create_course();
        $quiz = $this->getDataGenerator()->create_module('quiz', ['course' => $course->id]);

        $this->getDataGenerator()->get_plugin_generator('quizaccess_oqylyq')
            ->create_quiz_settings([
                'quizid' => $quiz->id,
                'cmid' => $quiz->cmid,
            ]);

        \quizaccess_oqylyq::delete_settings((object) ['id' => $quiz->id]);

        $result = quiz_settings::get_by_quiz_id($quiz->id);
        $this->assertFalse($result);
    }

    /**
     * Test delete_settings does not error when no settings exist.
     */
    public function test_delete_settings_no_error_when_missing(): void {
        \quizaccess_oqylyq::delete_settings((object) ['id' => 999999]);
        // No exception = success.
        $this->assertTrue(true);
    }

    // -------------------------------------------------------
    // prevent_access() tests.
    // -------------------------------------------------------

    /**
     * Test prevent_access returns false when proctoring is disabled.
     */
    public function test_prevent_access_disabled(): void {
        $quizobj = $this->create_quiz_object();

        $this->getDataGenerator()->get_plugin_generator('quizaccess_oqylyq')
            ->create_quiz_settings([
                'quizid' => $quizobj->get_quizid(),
                'cmid' => $quizobj->get_cmid(),
                'proctoring' => 0,
            ]);

        $rule = \quizaccess_oqylyq::make($quizobj, time(), false);

        // make() returns null when disabled, so prevent_access is never called.
        $this->assertNull($rule);
    }

    /**
     * Test prevent_access returns false (allowed) for Chrome UA with iframe header.
     */
    public function test_prevent_access_chrome_with_iframe(): void {
        $quizobj = $this->create_quiz_object();

        $this->getDataGenerator()->get_plugin_generator('quizaccess_oqylyq')
            ->create_quiz_settings([
                'quizid' => $quizobj->get_quizid(),
                'cmid' => $quizobj->get_cmid(),
                'proctoring' => 1,
            ]);

        // Set Chrome UA.
        $_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36';
        \core_useragent::instance(true);

        // Set iframe header.
        $_SERVER['HTTP_SEC_FETCH_DEST'] = 'iframe';

        $rule = \quizaccess_oqylyq::make($quizobj, time(), false);
        $this->assertNotNull($rule);

        $result = $rule->prevent_access();
        $this->assertFalse($result);
    }

    /**
     * Test prevent_access returns HTML string (blocked) for Chrome UA without iframe.
     */
    public function test_prevent_access_chrome_without_iframe(): void {
        global $PAGE;
        $PAGE->set_url('/mod/quiz/view.php');

        $quizobj = $this->create_quiz_object();

        $this->getDataGenerator()->get_plugin_generator('quizaccess_oqylyq')
            ->create_quiz_settings([
                'quizid' => $quizobj->get_quizid(),
                'cmid' => $quizobj->get_cmid(),
                'proctoring' => 1,
            ]);

        // Pre-create a cached URL so get_link doesn't call the external API.
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);

        $quizrecord = (object) [
            'id' => $quizobj->get_quizid(),
            'cmid' => $quizobj->get_cmid(),
            'name' => 'Test',
        ];
        \quizaccess_oqylyq\local\quiz_urls::createLink(
            $user, $quizrecord, 'https://test.oqylyq.kz/session/test', 7200
        );

        // Set Chrome UA.
        $_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36';
        \core_useragent::instance(true);

        // No iframe header.
        unset($_SERVER['HTTP_SEC_FETCH_DEST']);

        $rule = \quizaccess_oqylyq::make($quizobj, time(), false);
        $this->assertNotNull($rule);

        $result = $rule->prevent_access();
        $this->assertIsString($result);
        $this->assertNotEmpty($result);
    }

    /**
     * Test prevent_access returns false (allowed) for non-Chrome with hash parameter.
     *
     * Documents that check_key() always returns true (bypass).
     */
    public function test_prevent_access_non_chrome_with_hash(): void {
        $quizobj = $this->create_quiz_object();

        $this->getDataGenerator()->get_plugin_generator('quizaccess_oqylyq')
            ->create_quiz_settings([
                'quizid' => $quizobj->get_quizid(),
                'cmid' => $quizobj->get_cmid(),
                'proctoring' => 1,
            ]);

        // Set non-Chrome UA (Firefox).
        $_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:120.0) Gecko/20100101 Firefox/120.0';
        \core_useragent::instance(true);

        // Set hash parameter.
        $_REQUEST['hash'] = 'testhash123';
        unset($_COOKIE['proctoring_oqylyq_hash']);
        unset($_SERVER['DOCUMENT_URI']);

        $rule = \quizaccess_oqylyq::make($quizobj, time(), false);
        $this->assertNotNull($rule);

        $result = $rule->prevent_access();
        $this->assertFalse($result);
    }

    /**
     * Test prevent_access returns HTML string (blocked) for non-Chrome without hash.
     */
    public function test_prevent_access_non_chrome_without_hash(): void {
        global $PAGE;
        $PAGE->set_url('/mod/quiz/view.php');

        $quizobj = $this->create_quiz_object();

        $this->getDataGenerator()->get_plugin_generator('quizaccess_oqylyq')
            ->create_quiz_settings([
                'quizid' => $quizobj->get_quizid(),
                'cmid' => $quizobj->get_cmid(),
                'proctoring' => 1,
            ]);

        // Pre-create a cached URL so get_link doesn't call the external API.
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);

        $quizrecord = (object) [
            'id' => $quizobj->get_quizid(),
            'cmid' => $quizobj->get_cmid(),
            'name' => 'Test',
        ];
        \quizaccess_oqylyq\local\quiz_urls::createLink(
            $user, $quizrecord, 'https://test.oqylyq.kz/session/test2', 7200
        );

        // Set non-Chrome UA (Firefox).
        $_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:120.0) Gecko/20100101 Firefox/120.0';
        \core_useragent::instance(true);

        // No hash, no cookie, no allowed path.
        unset($_REQUEST['hash']);
        unset($_COOKIE['proctoring_oqylyq_hash']);
        unset($_SERVER['DOCUMENT_URI']);

        $rule = \quizaccess_oqylyq::make($quizobj, time(), false);
        $this->assertNotNull($rule);

        $result = $rule->prevent_access();
        $this->assertIsString($result);
        $this->assertNotEmpty($result);
    }
}
