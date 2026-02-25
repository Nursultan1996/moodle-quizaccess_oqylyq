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

use quizaccess_oqylyq\local\hideif_rule;

defined('MOODLE_INTERNAL') || die();

/**
 * Tests for the hideif_rule class.
 *
 * @package    quizaccess_oqylyq
 * @copyright  2020 Ertumar LLP
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \quizaccess_oqylyq\local\hideif_rule
 */
class hideif_rule_test extends \basic_testcase {

    /**
     * Test that the constructor stores values and all four getters return them correctly.
     */
    public function test_constructor_and_getters(): void {
        $rule = new hideif_rule('oqylyq_application', 'oqylyq_proctoring', 'noteq', '1');

        $this->assertSame('oqylyq_application', $rule->get_element());
        $this->assertSame('oqylyq_proctoring', $rule->get_dependantname());
        $this->assertSame('noteq', $rule->get_condition());
        $this->assertSame('1', $rule->get_dependantvalue());
    }

    /**
     * Test that the 'eq' condition is stored correctly.
     */
    public function test_eq_condition(): void {
        $rule = new hideif_rule('oqylyq_main_camera_record', 'oqylyq_application', 'eq', '0');

        $this->assertSame('eq', $rule->get_condition());
        $this->assertSame('oqylyq_main_camera_record', $rule->get_element());
        $this->assertSame('oqylyq_application', $rule->get_dependantname());
        $this->assertSame('0', $rule->get_dependantvalue());
    }

    /**
     * Test that the 'noteq' condition is stored correctly.
     */
    public function test_noteq_condition(): void {
        $rule = new hideif_rule('oqylyq_screen_share_record', 'oqylyq_proctoring', 'noteq', '1');

        $this->assertSame('noteq', $rule->get_condition());
        $this->assertSame('oqylyq_screen_share_record', $rule->get_element());
        $this->assertSame('oqylyq_proctoring', $rule->get_dependantname());
        $this->assertSame('1', $rule->get_dependantvalue());
    }
}
