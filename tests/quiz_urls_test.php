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

use quizaccess_oqylyq\local\quiz_urls;

defined('MOODLE_INTERNAL') || die();

/**
 * Tests for the quiz_urls persistent model.
 *
 * @package    quizaccess_oqylyq
 * @copyright  2020 Ertumar LLP
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \quizaccess_oqylyq\local\quiz_urls
 */
class quiz_urls_test extends \advanced_testcase {

    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest(true);
    }

    /**
     * Test that the TABLE constant has the expected value.
     */
    public function test_table_constant(): void {
        $this->assertSame('quizaccess_oqylyq_urls', quiz_urls::TABLE);
    }

    /**
     * Test that define_properties includes all 5 expected fields.
     */
    public function test_define_properties(): void {
        $url = new quiz_urls();
        $properties = $url->properties_definition();

        $expectedfields = ['quizid', 'cmid', 'userid', 'url', 'lifetime'];

        foreach ($expectedfields as $field) {
            $this->assertArrayHasKey($field, $properties, "Property '{$field}' should exist.");
        }
    }

    /**
     * Test that createLink saves a record to the database.
     */
    public function test_create_link_saves_to_db(): void {
        $user = $this->getDataGenerator()->create_user();
        $quiz = (object) ['id' => 100, 'cmid' => 200];

        $link = quiz_urls::createLink($user, $quiz, 'https://example.com/session/1', 3600);

        $this->assertInstanceOf(quiz_urls::class, $link);
        $this->assertEquals($user->id, $link->get('userid'));
        $this->assertEquals(100, $link->get('quizid'));
        $this->assertEquals(200, $link->get('cmid'));
        $this->assertSame('https://example.com/session/1', $link->get('url'));
        $this->assertEquals(3600, $link->get('lifetime'));
    }

    /**
     * Test that createLink deletes old URLs for the same user before creating a new one.
     */
    public function test_create_link_deletes_old_urls(): void {
        global $DB;

        $user = $this->getDataGenerator()->create_user();
        $quiz = (object) ['id' => 100, 'cmid' => 200];

        quiz_urls::createLink($user, $quiz, 'https://example.com/session/old', 3600);
        $this->assertEquals(1, $DB->count_records('quizaccess_oqylyq_urls', ['userid' => $user->id]));

        quiz_urls::createLink($user, $quiz, 'https://example.com/session/new', 3600);
        $this->assertEquals(1, $DB->count_records('quizaccess_oqylyq_urls', ['userid' => $user->id]));

        $records = $DB->get_records('quizaccess_oqylyq_urls', ['userid' => $user->id]);
        $record = reset($records);
        $this->assertSame('https://example.com/session/new', $record->url);
    }

    /**
     * Test that createLink returns a quiz_urls instance.
     */
    public function test_create_link_returns_instance(): void {
        $user = $this->getDataGenerator()->create_user();
        $quiz = (object) ['id' => 100, 'cmid' => 200];

        $result = quiz_urls::createLink($user, $quiz, 'https://example.com/session/1', 3600);
        $this->assertInstanceOf(quiz_urls::class, $result);
    }

    /**
     * Test that checkExists returns a valid link when it has not expired.
     */
    public function test_check_exists_valid_link(): void {
        $user = $this->getDataGenerator()->create_user();
        $quiz = (object) ['id' => 100, 'cmid' => 200];

        quiz_urls::createLink($user, $quiz, 'https://example.com/session/valid', 7200);

        $result = quiz_urls::checkExists($user, $quiz);

        $this->assertNotNull($result);
        $this->assertSame('https://example.com/session/valid', $result->url);
    }

    /**
     * Test that checkExists returns null for an expired link.
     */
    public function test_check_exists_expired_link(): void {
        global $DB;

        $user = $this->getDataGenerator()->create_user();
        $quiz = (object) ['id' => 100, 'cmid' => 200];

        $link = quiz_urls::createLink($user, $quiz, 'https://example.com/session/expired', 1);

        // Force timecreated to be far in the past so the link is expired.
        $DB->set_field('quizaccess_oqylyq_urls', 'timecreated', time() - 3600, ['id' => $link->get('id')]);

        $result = quiz_urls::checkExists($user, $quiz);
        $this->assertNull($result);
    }

    /**
     * Test that checkExists returns null when no link exists for the user/quiz combo.
     */
    public function test_check_exists_no_link(): void {
        $user = $this->getDataGenerator()->create_user();
        $quiz = (object) ['id' => 999, 'cmid' => 888];

        $result = quiz_urls::checkExists($user, $quiz);
        $this->assertNull($result);
    }

    /**
     * Test that checkExists with lifetime=0 never expires (the code guards with lifetime > 0).
     */
    public function test_check_exists_zero_lifetime_never_expires(): void {
        global $DB;

        $user = $this->getDataGenerator()->create_user();
        $quiz = (object) ['id' => 100, 'cmid' => 200];

        $link = quiz_urls::createLink($user, $quiz, 'https://example.com/session/forever', 0);

        // Set timecreated far in the past - should still be valid since lifetime=0.
        $DB->set_field('quizaccess_oqylyq_urls', 'timecreated', 1000, ['id' => $link->get('id')]);

        $result = quiz_urls::checkExists($user, $quiz);
        $this->assertNotNull($result);
        $this->assertSame('https://example.com/session/forever', $result->url);
    }
}
