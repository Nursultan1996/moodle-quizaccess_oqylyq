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

use quizaccess_oqylyq\privacy\provider;
use quizaccess_oqylyq\local\quiz_settings;
use quizaccess_oqylyq\local\quiz_urls;
use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\userlist;
use core_privacy\local\request\writer;

defined('MOODLE_INTERNAL') || die();

/**
 * Tests for the privacy provider.
 *
 * @package    quizaccess_oqylyq
 * @copyright  2020 Ertumar LLP
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \quizaccess_oqylyq\privacy\provider
 */
class privacy_provider_test extends \core_privacy\tests\provider_testcase {

    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest(true);
    }

    /**
     * Create test data: course, quiz, user, quiz_settings, quiz_urls.
     *
     * @return array [$course, $quiz, $user, $settings, $urlrecord, $context]
     */
    private function create_test_data(): array {
        $generator = $this->getDataGenerator();

        $course = $generator->create_course();
        $quiz = $generator->create_module('quiz', ['course' => $course->id]);
        $user = $generator->create_user();

        $this->setUser($user);

        $plugingenerator = $generator->get_plugin_generator('quizaccess_oqylyq');
        $settings = $plugingenerator->create_quiz_settings([
            'quizid' => $quiz->id,
            'cmid' => $quiz->cmid,
            'proctoring' => 1,
        ]);

        $urlrecord = $plugingenerator->create_quiz_url([
            'quizid' => $quiz->id,
            'cmid' => $quiz->cmid,
            'userid' => $user->id,
            'url' => 'https://test.oqylyq.kz/session/privacy',
        ]);

        $context = \context_module::instance($quiz->cmid);

        return [$course, $quiz, $user, $settings, $urlrecord, $context];
    }

    // -------------------------------------------------------
    // Metadata tests.
    // -------------------------------------------------------

    /**
     * Test get_metadata declares both DB tables and an external location.
     */
    public function test_get_metadata(): void {
        $collection = new collection('quizaccess_oqylyq');
        $collection = provider::get_metadata($collection);

        $items = $collection->get_collection();
        $this->assertNotEmpty($items);

        $types = [];
        foreach ($items as $item) {
            $types[] = get_class($item);
        }

        $this->assertContains('core_privacy\local\metadata\types\database_table', $types);
        $this->assertContains('core_privacy\local\metadata\types\external_location', $types);
    }

    // -------------------------------------------------------
    // get_contexts_for_userid tests.
    // -------------------------------------------------------

    /**
     * Test get_contexts_for_userid returns the quiz module context for a user with data.
     */
    public function test_get_contexts_for_userid(): void {
        list(, , $user, , , $context) = $this->create_test_data();

        $contextlist = provider::get_contexts_for_userid($user->id);
        $contextids = $contextlist->get_contextids();

        $this->assertContains($context->id, $contextids);
    }

    /**
     * Test get_contexts_for_userid returns empty for a different user.
     */
    public function test_get_contexts_for_userid_empty(): void {
        $this->create_test_data();

        $otheruser = $this->getDataGenerator()->create_user();
        $contextlist = provider::get_contexts_for_userid($otheruser->id);

        $this->assertEmpty($contextlist->get_contextids());
    }

    // -------------------------------------------------------
    // export_user_data tests.
    // -------------------------------------------------------

    /**
     * Test export_user_data exports quiz settings for the user.
     */
    public function test_export_user_data_settings(): void {
        list(, , $user, , , $context) = $this->create_test_data();

        $contextlist = new approved_contextlist($user, 'quizaccess_oqylyq', [$context->id]);
        provider::export_user_data($contextlist);

        $data = writer::with_context($context)->get_data(
            [get_string('privacy:metadata:quizaccess_oql_quizsettings', 'quizaccess_oqylyq')]
        );

        $this->assertNotEmpty($data);
        $this->assertEquals(1, $data->proctoring_enabled);
    }

    /**
     * Test export_user_data exports quiz URLs for the user.
     */
    public function test_export_user_data_urls(): void {
        list(, , $user, , , $context) = $this->create_test_data();

        $contextlist = new approved_contextlist($user, 'quizaccess_oqylyq', [$context->id]);
        provider::export_user_data($contextlist);

        $data = writer::with_context($context)->get_data(
            [get_string('privacy:metadata:quizaccess_oql_quizurls', 'quizaccess_oqylyq')]
        );

        $this->assertNotEmpty($data);
        $this->assertSame('https://test.oqylyq.kz/session/privacy', $data->url);
    }

    // -------------------------------------------------------
    // delete_data_for_user tests.
    // -------------------------------------------------------

    /**
     * Test delete_data_for_user removes settings and URLs for the specific user.
     */
    public function test_delete_data_for_user(): void {
        global $DB;

        list(, $quiz, $user, , , $context) = $this->create_test_data();

        $contextlist = new approved_contextlist($user, 'quizaccess_oqylyq', [$context->id]);
        provider::delete_data_for_user($contextlist);

        $this->assertEquals(0, $DB->count_records('quizaccess_oql_quizsettings', [
            'cmid' => $quiz->cmid,
            'usermodified' => $user->id,
        ]));

        $this->assertEquals(0, $DB->count_records('quizaccess_oql_quizurls', [
            'cmid' => $quiz->cmid,
            'userid' => $user->id,
        ]));
    }

    // -------------------------------------------------------
    // delete_data_for_all_users_in_context tests.
    // -------------------------------------------------------

    /**
     * Test delete_data_for_all_users_in_context removes all data in context.
     */
    public function test_delete_data_for_all_in_context(): void {
        global $DB;

        list(, $quiz, , , , $context) = $this->create_test_data();

        provider::delete_data_for_all_users_in_context($context);

        $this->assertEquals(0, $DB->count_records('quizaccess_oql_quizsettings', ['cmid' => $quiz->cmid]));
        $this->assertEquals(0, $DB->count_records('quizaccess_oql_quizurls', ['cmid' => $quiz->cmid]));
    }

    /**
     * Test delete_data_for_all_users_in_context skips non-module contexts.
     */
    public function test_delete_data_for_all_skips_non_module(): void {
        global $DB;

        list(, $quiz, , , , ) = $this->create_test_data();
        $systemcontext = \context_system::instance();

        $settingscount = $DB->count_records('quizaccess_oql_quizsettings', ['cmid' => $quiz->cmid]);

        provider::delete_data_for_all_users_in_context($systemcontext);

        // Data should still be there.
        $this->assertEquals($settingscount, $DB->count_records('quizaccess_oql_quizsettings', ['cmid' => $quiz->cmid]));
    }

    // -------------------------------------------------------
    // get_users_in_context tests.
    // -------------------------------------------------------

    /**
     * Test get_users_in_context returns the correct users.
     */
    public function test_get_users_in_context(): void {
        list(, , $user, , , $context) = $this->create_test_data();

        $userlist = new userlist($context, 'quizaccess_oqylyq');
        provider::get_users_in_context($userlist);

        $userids = $userlist->get_userids();
        $this->assertContains($user->id, $userids);
    }

    // -------------------------------------------------------
    // delete_data_for_users tests.
    // -------------------------------------------------------

    /**
     * Test delete_data_for_users removes data for targeted users.
     */
    public function test_delete_data_for_users(): void {
        global $DB;

        list(, $quiz, $user, , , $context) = $this->create_test_data();

        $userlist = new approved_userlist($context, 'quizaccess_oqylyq', [$user->id]);
        provider::delete_data_for_users($userlist);

        $this->assertEquals(0, $DB->count_records('quizaccess_oql_quizsettings', [
            'cmid' => $quiz->cmid,
            'usermodified' => $user->id,
        ]));

        $this->assertEquals(0, $DB->count_records('quizaccess_oql_quizurls', [
            'cmid' => $quiz->cmid,
            'userid' => $user->id,
        ]));
    }
}
