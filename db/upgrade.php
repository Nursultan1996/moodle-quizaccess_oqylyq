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
 * Upgrade steps for the quizaccess_oqylyq plugin.
 *
 * @package    quizaccess_oqylyq
 * @author     Eduard Zaukarnaev <eduard.zaukarnaev@gmail.com>
 * @copyright  2020 Ertumar LLP
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Execute quizaccess_oqylyq upgrade from the given old version.
 *
 * @param int $oldversion the version we are upgrading from.
 * @return bool always true.
 */
function xmldb_quizaccess_oqylyq_upgrade($oldversion) {
    global $DB;

    $dbman = $DB->get_manager();

    if ($oldversion < 2027063000) {

        // Rename tables to follow the Frankenstyle naming convention
        // (plugintype_pluginname_tablename) and keep within the 28 character limit.
        if ($dbman->table_exists('quizaccess_oql_quizsettings')) {
            $table = new xmldb_table('quizaccess_oql_quizsettings');
            $dbman->rename_table($table, 'quizaccess_oqylyq_settings');
        }

        if ($dbman->table_exists('quizaccess_oql_quizurls')) {
            $table = new xmldb_table('quizaccess_oql_quizurls');
            $dbman->rename_table($table, 'quizaccess_oqylyq_urls');
        }

        // Oqylyq savepoint reached.
        upgrade_plugin_savepoint(true, 2027063000, 'quizaccess', 'oqylyq');
    }

    return true;
}
