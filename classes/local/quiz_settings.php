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

namespace quizaccess_oqylyq\local;

use lang_string;

defined('MOODLE_INTERNAL') || die();

/**
 * Entity model representing quiz settings for the plugin.
 *
 * Standalone replacement for \core\persistent (Moodle 3.2+) to support Moodle 3.1.
 *
 * @package    quizaccess_oqylyq
 * @author     Eduard Zaukarnaev <eduard.zaukarnaev@gmail.com>
 * @copyright  2020 Ertumar LLP
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class quiz_settings {
    /** Table name. */
    const TABLE = 'quizaccess_oql_quizsettings';

    /** @var \stdClass Internal data store. */
    protected $data;

    /** @var array Validation errors keyed by property name. */
    protected $errors = [];

    /**
     * Constructor.
     *
     * @param int $id If > 0, load record from DB by id.
     * @param \stdClass $record Optional record to populate from.
     */
    public function __construct($id = 0, \stdClass $record = null) {
        $this->data = new \stdClass();
        $this->data->id = 0;

        if ($id > 0) {
            global $DB;
            $row = $DB->get_record(self::TABLE, ['id' => $id], '*', MUST_EXIST);
            $this->data = $row;
        } else if ($record !== null) {
            $this->from_record($record);
        }
    }

    /**
     * Return the definition of the properties of this model.
     *
     * @return array
     */
    protected static function define_properties() {
        return [
            'quizid' => [
                'type' => PARAM_INT,
            ],
            'cmid' => [
                'type' => PARAM_INT,
            ],
            'proctoring' => [
                'type' => PARAM_INT,
                'default' => 1,
                'null' => NULL_ALLOWED
            ],
            'application' => [
                'type' => PARAM_ALPHANUMEXT,
                'default' => 'browser',
                'null' => NULL_ALLOWED
            ],
            'main_camera_record' => [
                'type' => PARAM_INT,
                'default' => 1,
                'null' => NULL_ALLOWED
            ],
            'second_camera_record' => [
                'type' => PARAM_INT,
                'default' => 1,
                'null' => NULL_ALLOWED
            ],
            'screen_share_record' => [
                'type' => PARAM_INT,
                'default' => 1,
                'null' => NULL_ALLOWED
            ],
            'photo_head_identity' => [
                'type' => PARAM_INT,
                'default' => 1,
                'null' => NULL_ALLOWED
            ],
            'id_verification' => [
                'type' => PARAM_INT,
                'default' => 0,
                'null' => NULL_ALLOWED
            ],
            'display_checks' => [
                'type' => PARAM_INT,
                'default' => 1,
                'null' => NULL_ALLOWED
            ],
            'hdcp_checks' => [
                'type' => PARAM_INT,
                'default' => 1,
                'null' => NULL_ALLOWED
            ],
            'content_protect' => [
                'type' => PARAM_INT,
                'default' => 1,
                'null' => NULL_ALLOWED
            ],
            'fullscreen_mode' => [
                'type' => PARAM_INT,
                'default' => 0,
                'null' => NULL_ALLOWED
            ],
            'focus_detector' => [
                'type' => PARAM_INT,
                'default' => 0,
                'null' => NULL_ALLOWED
            ],
            'extension_detector' => [
                'type' => PARAM_INT,
                'default' => 0,
                'null' => NULL_ALLOWED
            ]
        ];
    }

    /**
     * Get a property value.
     *
     * @param string $name Property name.
     * @return mixed
     */
    public function get($name) {
        if ($name === 'id') {
            return isset($this->data->id) ? $this->data->id : 0;
        }
        if (isset($this->data->$name)) {
            return $this->data->$name;
        }
        $properties = static::define_properties();
        if (isset($properties[$name]['default'])) {
            return $properties[$name]['default'];
        }
        return null;
    }

    /**
     * Set a property value.
     *
     * @param string $name Property name.
     * @param mixed $value Property value.
     * @return $this
     */
    public function set($name, $value) {
        $this->data->$name = $value;
        return $this;
    }

    /**
     * Populate properties from a record object.
     *
     * @param \stdClass $record
     * @return $this
     */
    public function from_record(\stdClass $record) {
        foreach ($record as $key => $value) {
            $this->data->$key = $value;
        }
        return $this;
    }

    /**
     * Get a single record matching the given conditions.
     *
     * @param array $conditions Key-value pairs for WHERE clause.
     * @return static|false An instance or false if not found.
     */
    public static function get_record(array $conditions) {
        global $DB;
        $record = $DB->get_record(static::TABLE, $conditions);
        if ($record === false) {
            return false;
        }
        $instance = new static();
        $instance->data = $record;
        return $instance;
    }

    /**
     * Get multiple records matching the given conditions.
     *
     * @param array $conditions Key-value pairs for WHERE clause.
     * @return static[]
     */
    public static function get_records(array $conditions) {
        global $DB;
        $records = $DB->get_records(static::TABLE, $conditions);
        $instances = [];
        foreach ($records as $record) {
            $instance = new static();
            $instance->data = $record;
            $instances[] = $instance;
        }
        return $instances;
    }

    /**
     * Save (insert or update) the record.
     *
     * @return $this
     */
    public function save() {
        global $DB, $USER;
        $now = time();
        $this->data->timemodified = $now;
        $this->data->usermodified = isset($USER->id) ? $USER->id : 0;

        if (!empty($this->data->id)) {
            $DB->update_record(static::TABLE, $this->data);
        } else {
            $this->data->timecreated = $now;
            $this->data->id = $DB->insert_record(static::TABLE, $this->data);
        }
        return $this;
    }

    /**
     * Create (insert) the record.
     *
     * @return $this
     */
    public function create() {
        global $DB, $USER;
        $now = time();
        $this->data->timecreated = $now;
        $this->data->timemodified = $now;
        $this->data->usermodified = isset($USER->id) ? $USER->id : 0;
        $this->data->id = $DB->insert_record(static::TABLE, $this->data);
        return $this;
    }

    /**
     * Delete the record.
     *
     * @return void
     */
    public function delete() {
        global $DB;
        if (!empty($this->data->id)) {
            $DB->delete_records(static::TABLE, ['id' => $this->data->id]);
        }
    }

    /**
     * Validate the record properties.
     *
     * @return bool True if valid.
     */
    public function validate() {
        $this->errors = [];
        $properties = static::define_properties();
        foreach ($properties as $name => $config) {
            $value = isset($this->data->$name) ? $this->data->$name : null;
            $nullallowed = isset($config['null']) && $config['null'] === NULL_ALLOWED;
            if ($value === null && !$nullallowed && !isset($config['default'])) {
                $this->errors[$name] = new lang_string('errornotempty', 'core_form');
            }
        }
        return empty($this->errors);
    }

    /**
     * Get validation errors.
     *
     * @return array Errors keyed by property name.
     */
    public function get_errors() {
        return $this->errors;
    }

    /**
     * Get settings by quiz id.
     *
     * @param int $quizid
     * @return static|false
     */
    public static function get_by_quiz_id($quizid) {
        return static::get_record([
            'quizid' => $quizid
        ]);
    }
}
