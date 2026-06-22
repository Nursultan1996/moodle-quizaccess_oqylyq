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

use quizaccess_oqylyq\local\link_generator;
use quizaccess_oqylyq\local\quiz_settings;
use quizaccess_oqylyq\local\quiz_urls;

defined('MOODLE_INTERNAL') || die();

/**
 * Tests for the link_generator class.
 *
 * Note: get_link() calls the external Oqylyq API when there is no cached URL.
 * Only the cached-URL path and standalone methods can be tested here.
 *
 * @package    quizaccess_oqylyq
 * @copyright  2020 Ertumar LLP
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \quizaccess_oqylyq\local\link_generator
 */
class link_generator_test extends \advanced_testcase {

    /** @var mixed Saved $FULLME global. */
    private $originalfullme;

    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest(true);
        global $FULLME;
        $this->originalfullme = $FULLME;
    }

    protected function tearDown(): void {
        global $FULLME;
        $FULLME = $this->originalfullme;
        parent::tearDown();
    }

    /**
     * Test get_hash_current_url hashes $FULLME when it is set.
     */
    public function test_get_hash_current_url_with_fullme(): void {
        global $FULLME;

        $FULLME = 'https://example.com/mod/quiz/view.php?id=1';

        $hash = link_generator::get_hash_current_url();

        $this->assertSame(hash('sha256', 'https://example.com/mod/quiz/view.php?id=1'), $hash);
    }

    /**
     * Test get_hash_current_url falls back to $CFG->wwwroot when $FULLME is null.
     */
    public function test_get_hash_current_url_fallback_wwwroot(): void {
        global $CFG, $FULLME;

        $FULLME = null;

        $hash = link_generator::get_hash_current_url();

        $this->assertSame(hash('sha256', $CFG->wwwroot), $hash);
    }

    /**
     * Test get_auth_key returns a 32-character hex string (MD5).
     */
    public function test_get_auth_key_returns_md5(): void {
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);

        $key = link_generator::get_auth_key();

        $this->assertMatchesRegularExpression('/^[a-f0-9]{32}$/', $key);
    }

    /**
     * Test get_auth_key returns a non-empty string.
     */
    public function test_get_auth_key_non_empty(): void {
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);

        $key = link_generator::get_auth_key();
        $this->assertNotEmpty($key);
    }

    /**
     * Test get_link returns a cached URL when a valid quiz_urls record exists.
     */
    public function test_get_link_returns_cached_url(): void {
        global $USER;

        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);

        $course = $this->getDataGenerator()->create_course();
        $quiz = $this->getDataGenerator()->create_module('quiz', ['course' => $course->id]);

        $quizrecord = (object) [
            'id' => $quiz->id,
            'cmid' => $quiz->cmid,
            'name' => 'Test Quiz',
        ];

        $settings = $this->getDataGenerator()->get_plugin_generator('quizaccess_oqylyq')
            ->create_quiz_settings([
                'quizid' => $quiz->id,
                'cmid' => $quiz->cmid,
            ]);

        // Pre-create a cached URL.
        quiz_urls::createLink($USER, $quizrecord, 'https://cached.oqylyq.kz/session/abc', 7200);

        $result = link_generator::get_link($quizrecord, $settings);
        $this->assertSame('https://cached.oqylyq.kz/session/abc', $result);
    }
}
