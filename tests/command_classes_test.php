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

use quizaccess_oqylyq\local\command_interface;
use quizaccess_oqylyq\local\session;
use quizaccess_oqylyq\local\student;
use quizaccess_oqylyq\local\group;
use quizaccess_oqylyq\local\assignment;

defined('MOODLE_INTERNAL') || die();

/**
 * Tests for the command classes: session, student, group, assignment.
 *
 * @package    quizaccess_oqylyq
 * @copyright  2020 Ertumar LLP
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \quizaccess_oqylyq\local\session
 * @covers     \quizaccess_oqylyq\local\student
 * @covers     \quizaccess_oqylyq\local\group
 * @covers     \quizaccess_oqylyq\local\assignment
 */
class command_classes_test extends \basic_testcase {

    // -------------------------------------------------------
    // Session tests.
    // -------------------------------------------------------

    /**
     * Test that session implements the command_interface.
     */
    public function test_session_implements_interface(): void {
        $session = new session();
        $this->assertInstanceOf(command_interface::class, $session);
    }

    /**
     * Test that session returns the correct request URL.
     */
    public function test_session_url(): void {
        $session = new session();
        $this->assertSame('/external-session/assignment.json', $session->get_request_url());
    }

    /**
     * Test that session returns POST as the HTTP method.
     */
    public function test_session_method(): void {
        $session = new session();
        $this->assertSame('POST', $session->get_request_method());
    }

    /**
     * Test that session stores and returns the constructor data.
     */
    public function test_session_data_from_constructor(): void {
        $data = ['student' => ['id' => 1], 'group' => ['name' => 'Test']];
        $session = new session($data);
        $this->assertSame($data, $session->get_request_data());
    }

    /**
     * Test that session returns empty query and headers by default.
     */
    public function test_session_empty_defaults(): void {
        $session = new session();
        $this->assertSame([], $session->get_request_query());
        $this->assertSame([], $session->get_request_headers());
    }

    // -------------------------------------------------------
    // Student tests.
    // -------------------------------------------------------

    /**
     * Test that student implements the command_interface.
     */
    public function test_student_implements_interface(): void {
        $student = new student();
        $this->assertInstanceOf(command_interface::class, $student);
    }

    /**
     * Test that student returns the correct request URL.
     */
    public function test_student_url(): void {
        $student = new student();
        $this->assertSame('/students', $student->get_request_url());
    }

    /**
     * Test that student returns POST as the HTTP method.
     */
    public function test_student_method(): void {
        $student = new student();
        $this->assertSame('POST', $student->get_request_method());
    }

    /**
     * Test that the student constructor ignores $data and always sets user to empty array.
     *
     * This documents a known bug: student.php line 45 sets $this->user = [] regardless of input.
     */
    public function test_student_constructor_ignores_data(): void {
        $data = ['firstname' => 'Test', 'lastname' => 'User'];
        $student = new student($data);

        // Bug: constructor ignores $data, always returns empty array.
        $this->assertSame([], $student->get_request_data());
    }

    /**
     * Test that student returns empty query and headers.
     */
    public function test_student_empty_defaults(): void {
        $student = new student();
        $this->assertSame([], $student->get_request_query());
        $this->assertSame([], $student->get_request_headers());
    }

    // -------------------------------------------------------
    // Group tests.
    // -------------------------------------------------------

    /**
     * Test that group implements the command_interface.
     */
    public function test_group_implements_interface(): void {
        $group = new group();
        $this->assertInstanceOf(command_interface::class, $group);
    }

    /**
     * Test that group returns the correct request URL.
     */
    public function test_group_url(): void {
        $group = new group();
        $this->assertSame('/groups', $group->get_request_url());
    }

    /**
     * Test that group returns POST as the HTTP method.
     */
    public function test_group_method(): void {
        $group = new group();
        $this->assertSame('POST', $group->get_request_method());
    }

    /**
     * Test that group stores and returns the constructor data.
     */
    public function test_group_data_from_constructor(): void {
        $data = ['name' => 'Test Group', 'external_id' => 42];
        $group = new group($data);
        $this->assertSame($data, $group->get_request_data());
    }

    /**
     * Test that group returns empty query and headers.
     */
    public function test_group_empty_defaults(): void {
        $group = new group();
        $this->assertSame([], $group->get_request_query());
        $this->assertSame([], $group->get_request_headers());
    }

    // -------------------------------------------------------
    // Assignment tests.
    // -------------------------------------------------------

    /**
     * Test that assignment implements the command_interface.
     */
    public function test_assignment_implements_interface(): void {
        $assignment = new assignment();
        $this->assertInstanceOf(command_interface::class, $assignment);
    }

    /**
     * Test that assignment returns the correct request URL.
     */
    public function test_assignment_url(): void {
        $assignment = new assignment();
        $this->assertSame('/assignments', $assignment->get_request_url());
    }

    /**
     * Test that assignment returns POST as the HTTP method.
     */
    public function test_assignment_method(): void {
        $assignment = new assignment();
        $this->assertSame('POST', $assignment->get_request_method());
    }

    /**
     * Test that assignment stores and returns the constructor data.
     */
    public function test_assignment_data_from_constructor(): void {
        $data = ['name' => 'Test Assignment', 'type' => 'external'];
        $assignment = new assignment($data);
        $this->assertSame($data, $assignment->get_request_data());
    }

    /**
     * Test that assignment returns empty query and headers.
     */
    public function test_assignment_empty_defaults(): void {
        $assignment = new assignment();
        $this->assertSame([], $assignment->get_request_query());
        $this->assertSame([], $assignment->get_request_headers());
    }
}
