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

use quizaccess_oqylyq\local\access_manager;
use quizaccess_oqylyq\local\quiz_settings;
use quizaccess_oqylyq\local\settings_provider;

defined('MOODLE_INTERNAL') || die();

/**
 * Tests for the access_manager class.
 *
 * @package    quizaccess_oqylyq
 * @copyright  2020 Ertumar LLP
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \quizaccess_oqylyq\local\access_manager
 */
class access_manager_test extends \advanced_testcase {

    /** @var array Saved $_SERVER superglobal. */
    private $originalserver;

    /** @var array Saved $_COOKIE superglobal. */
    private $originalcookie;

    /** @var array Saved $_REQUEST superglobal. */
    private $originalrequest;

    /** @var array Saved $_GET superglobal. */
    private $originalget;

    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest(true);
        $this->originalserver = $_SERVER;
        $this->originalcookie = $_COOKIE;
        $this->originalrequest = $_REQUEST;
        $this->originalget = $_GET;
    }

    protected function tearDown(): void {
        $_SERVER = $this->originalserver;
        $_COOKIE = $this->originalcookie;
        $_REQUEST = $this->originalrequest;
        $_GET = $this->originalget;
        parent::tearDown();
    }

    /**
     * Create a quiz object suitable for the access_manager constructor.
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
    // Proctoring enabled tests.
    // -------------------------------------------------------

    /**
     * Test is_proctoring_enabled returns 0 when no quiz_settings record exists.
     */
    public function test_is_proctoring_enabled_no_settings(): void {
        $quizobj = $this->create_quiz_object();
        $manager = new access_manager($quizobj);

        $this->assertEquals(settings_provider::PROCTORING_DISABLED, $manager->is_proctoring_enabled());
    }

    /**
     * Test is_proctoring_enabled returns 1 when proctoring is enabled.
     */
    public function test_is_proctoring_enabled_when_enabled(): void {
        $quizobj = $this->create_quiz_object();

        $this->getDataGenerator()->get_plugin_generator('quizaccess_oqylyq')
            ->create_quiz_settings([
                'quizid' => $quizobj->get_quizid(),
                'cmid' => $quizobj->get_cmid(),
                'proctoring' => 1,
            ]);

        $manager = new access_manager($quizobj);
        $this->assertEquals(1, $manager->is_proctoring_enabled());
    }

    /**
     * Test is_proctoring_enabled returns 0 when proctoring is disabled.
     */
    public function test_is_proctoring_enabled_when_disabled(): void {
        $quizobj = $this->create_quiz_object();

        $this->getDataGenerator()->get_plugin_generator('quizaccess_oqylyq')
            ->create_quiz_settings([
                'quizid' => $quizobj->get_quizid(),
                'cmid' => $quizobj->get_cmid(),
                'proctoring' => 0,
            ]);

        $manager = new access_manager($quizobj);
        $this->assertEquals(0, $manager->is_proctoring_enabled());
    }

    // -------------------------------------------------------
    // Iframe validation tests.
    // -------------------------------------------------------

    /**
     * Test validate_iframe_parameters returns true when HTTP_SEC_FETCH_DEST is 'iframe'.
     */
    public function test_validate_iframe_with_header(): void {
        $quizobj = $this->create_quiz_object();
        $manager = new access_manager($quizobj);

        $_SERVER['HTTP_SEC_FETCH_DEST'] = 'iframe';
        $this->assertTrue($manager->validate_iframe_parameters());
    }

    /**
     * Test validate_iframe_parameters returns false without the header.
     */
    public function test_validate_iframe_without_header(): void {
        $quizobj = $this->create_quiz_object();
        $manager = new access_manager($quizobj);

        unset($_SERVER['HTTP_SEC_FETCH_DEST']);
        $this->assertFalse($manager->validate_iframe_parameters());
    }

    /**
     * Test validate_iframe_parameters returns false with wrong header value.
     */
    public function test_validate_iframe_wrong_value(): void {
        $quizobj = $this->create_quiz_object();
        $manager = new access_manager($quizobj);

        $_SERVER['HTTP_SEC_FETCH_DEST'] = 'document';
        $this->assertFalse($manager->validate_iframe_parameters());
    }

    // -------------------------------------------------------
    // Hash validation tests.
    // -------------------------------------------------------

    /**
     * Test validate_hash_keys returns true for an allowed path.
     */
    public function test_validate_hash_allowed_path(): void {
        $quizobj = $this->create_quiz_object();
        $manager = new access_manager($quizobj);

        $_SERVER['DOCUMENT_URI'] = '/mod/quiz/attempt.php';
        unset($_COOKIE['proctoring_oqylyq_hash']);
        unset($_REQUEST['hash']);

        $this->assertTrue($manager->validate_hash_keys());
    }

    /**
     * Test validate_hash_keys rejects an invalid hash stored in the cookie.
     */
    public function test_validate_hash_with_invalid_cookie(): void {
        $quizobj = $this->create_quiz_object();
        $manager = new access_manager($quizobj);

        unset($_SERVER['DOCUMENT_URI']);
        $_COOKIE['proctoring_oqylyq_hash'] = 'somehash';
        unset($_GET['hash'], $_REQUEST['hash']);

        $this->assertFalse($manager->validate_hash_keys());
    }

    /**
     * Test validate_hash_keys accepts a valid hash stored in the cookie.
     */
    public function test_validate_hash_with_valid_cookie(): void {
        global $CFG;
        $quizobj = $this->create_quiz_object();
        $manager = new access_manager($quizobj);

        unset($_SERVER['DOCUMENT_URI']);
        $_COOKIE['proctoring_oqylyq_hash'] = hash('sha256', $CFG->wwwroot);
        unset($_GET['hash'], $_REQUEST['hash']);

        $this->assertTrue($manager->validate_hash_keys());
    }

    /**
     * Test validate_hash_keys rejects an invalid hash supplied in the request.
     */
    public function test_validate_hash_with_invalid_get_param(): void {
        $quizobj = $this->create_quiz_object();
        $manager = new access_manager($quizobj);

        unset($_SERVER['DOCUMENT_URI']);
        unset($_COOKIE['proctoring_oqylyq_hash']);
        $_GET['hash'] = 'testhash';

        $this->assertFalse($manager->validate_hash_keys());
    }

    /**
     * Test validate_hash_keys accepts a valid hash supplied in the request.
     */
    public function test_validate_hash_with_valid_get_param(): void {
        global $CFG;
        $quizobj = $this->create_quiz_object();
        $manager = new access_manager($quizobj);

        unset($_SERVER['DOCUMENT_URI']);
        unset($_COOKIE['proctoring_oqylyq_hash']);
        $_GET['hash'] = hash('sha256', $CFG->wwwroot);

        $this->assertTrue($manager->validate_hash_keys());
    }

    /**
     * Test validate_hash_keys returns false when no cookie, no GET param, and no allowed path.
     */
    public function test_validate_hash_no_hash(): void {
        $quizobj = $this->create_quiz_object();
        $manager = new access_manager($quizobj);

        unset($_SERVER['DOCUMENT_URI']);
        unset($_COOKIE['proctoring_oqylyq_hash']);
        unset($_GET['hash'], $_REQUEST['hash']);

        $this->assertFalse($manager->validate_hash_keys());
    }

    // -------------------------------------------------------
    // Received hash key tests.
    // -------------------------------------------------------

    /**
     * Test get_received_hash_key returns null when no hash in request.
     */
    public function test_get_received_hash_key_null(): void {
        $quizobj = $this->create_quiz_object();
        $manager = new access_manager($quizobj);

        unset($_GET['hash'], $_REQUEST['hash']);
        $this->assertNull($manager->get_received_hash_key());
    }

    /**
     * Test get_received_hash_key cleans the supplied value as PARAM_ALPHANUMEXT.
     */
    public function test_get_received_hash_key_cleaned(): void {
        $quizobj = $this->create_quiz_object();
        $manager = new access_manager($quizobj);

        $_GET['hash'] = '  abc  ';
        $this->assertSame('abc', $manager->get_received_hash_key());
    }

    // -------------------------------------------------------
    // Getter tests.
    // -------------------------------------------------------

    /**
     * Test get_quiz returns the quiz object.
     */
    public function test_get_quiz_returns_quiz(): void {
        $quizobj = $this->create_quiz_object();
        $manager = new access_manager($quizobj);

        $this->assertSame($quizobj, $manager->get_quiz());
    }

    /**
     * Test get_quizsettings returns a quiz_settings instance.
     */
    public function test_get_quizsettings_returns_settings(): void {
        $quizobj = $this->create_quiz_object();

        $this->getDataGenerator()->get_plugin_generator('quizaccess_oqylyq')
            ->create_quiz_settings([
                'quizid' => $quizobj->get_quizid(),
                'cmid' => $quizobj->get_cmid(),
            ]);

        $manager = new access_manager($quizobj);
        $settings = $manager->get_quizsettings();

        $this->assertInstanceOf(quiz_settings::class, $settings);
        $this->assertEquals($quizobj->get_quizid(), $settings->get('quizid'));
    }
}
