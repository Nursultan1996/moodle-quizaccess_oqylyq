<?php
// This file is part of Moodle - http://moodle.org/.
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License...

namespace quizaccess_oqylyq\privacy;

defined('MOODLE_INTERNAL') || die();

use core_privacy\local\metadata\collection;
use core_privacy\local\request\writer;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\userlist;

/**
 * Privacy provider for quizaccess_oqylyq plugin.
 *
 * @package   quizaccess_oqylyq
 */
class provider implements
    \core_privacy\local\metadata\provider,
    \core_privacy\local\request\plugin\provider,
    \core_privacy\local\request\core_userlist_provider {

    /**
     * Describe stored personal data in database tables AND external API.
     *
     * @param collection $collection
     * @return collection
     */
    public static function get_metadata(collection $collection) : collection {

        // quizaccess_oql_quizsettings - stores who modified quiz settings
        $collection->add_database_table(
            'quizaccess_oql_quizsettings',
            [
                'usermodified' => 'privacy:metadata:quizaccess_oql_quizsettings:usermodified',
                'timecreated' => 'privacy:metadata:quizaccess_oql_quizsettings:timecreated',
                'timemodified' => 'privacy:metadata:quizaccess_oql_quizsettings:timemodified'
            ],
            'privacy:metadata:quizaccess_oql_quizsettings'
        );

        // quizaccess_oql_quizurls - stores generated URLs per user
        $collection->add_database_table(
            'quizaccess_oql_quizurls',
            [
                'userid' => 'privacy:metadata:quizaccess_oql_quizurls:userid',
                'usermodified' => 'privacy:metadata:quizaccess_oql_quizurls:usermodified',
                'url' => 'privacy:metadata:quizaccess_oql_quizurls:url',
                'timecreated' => 'privacy:metadata:quizaccess_oql_quizurls:timecreated',
                'timemodified' => 'privacy:metadata:quizaccess_oql_quizurls:timemodified'
            ],
            'privacy:metadata:quizaccess_oql_quizurls'
        );

        // External API - data sent to Oqylyq service with privacy policy link
        $collection->add_external_location_link(
            'oqylyq_external',
            [
                'userid' => 'privacy:metadata:oqylyq_external:userid',
                'firstname' => 'privacy:metadata:oqylyq_external:firstname',
                'lastname' => 'privacy:metadata:oqylyq_external:lastname',
                'email' => 'privacy:metadata:oqylyq_external:email',
                'password' => 'privacy:metadata:oqylyq_external:password',
                'quizname' => 'privacy:metadata:oqylyq_external:quizname',
            ],
            'privacy:metadata:oqylyq_external',
            'https://trustexam.ai/privacy'
        );

        return $collection;
    }

    /**
     * Get the list of contexts that contain user information for the specified user.
     *
     * @param int $userid The user to search.
     * @return contextlist The contextlist containing the list of contexts used in this plugin.
     */
    public static function get_contexts_for_userid(int $userid) : contextlist {
        $contextlist = new contextlist();

        // Get contexts where user modified quiz settings.
        $sql = "
            SELECT ctx.id
              FROM {context} ctx
              JOIN {course_modules} cm ON cm.id = ctx.instanceid AND ctx.contextlevel = :contextlevel1
              JOIN {quizaccess_oql_quizsettings} qs ON qs.cmid = cm.id
             WHERE qs.usermodified = :userid1
        ";
        $contextlist->add_from_sql($sql, [
            'contextlevel1' => CONTEXT_MODULE,
            'userid1' => $userid
        ]);

        // Get contexts where user has generated quiz URLs.
        $sql2 = "
            SELECT ctx.id
              FROM {context} ctx
              JOIN {course_modules} cm ON cm.id = ctx.instanceid AND ctx.contextlevel = :contextlevel2
              JOIN {quizaccess_oql_quizurls} qu ON qu.cmid = cm.id
             WHERE qu.userid = :userid2 OR qu.usermodified = :userid3
        ";
        $contextlist->add_from_sql($sql2, [
            'contextlevel2' => CONTEXT_MODULE,
            'userid2' => $userid,
            'userid3' => $userid
        ]);

        return $contextlist;
    }

    /**
     * Export all user data for the specified user, in the specified contexts.
     *
     * @param approved_contextlist $contextlist The approved contexts to export information for.
     */
    public static function export_user_data(approved_contextlist $contextlist) {
        global $DB;

        if (empty($contextlist->count())) {
            return;
        }

        $user = $contextlist->get_user();
        $userid = $user->id;

        list($contextsql, $contextparams) = $DB->get_in_or_equal($contextlist->get_contextids(), SQL_PARAMS_NAMED);

        // Export quiz settings where user was the modifier.
        $sql = "SELECT qs.*
                  FROM {quizaccess_oql_quizsettings} qs
                  JOIN {course_modules} cm ON cm.id = qs.cmid
                  JOIN {context} ctx ON ctx.instanceid = cm.id AND ctx.contextlevel = :contextlevel1
                 WHERE ctx.id {$contextsql}
                   AND qs.usermodified = :userid1";

        $params = $contextparams + [
            'contextlevel1' => CONTEXT_MODULE,
            'userid1' => $userid
        ];

        $settings = $DB->get_recordset_sql($sql, $params);
        foreach ($settings as $setting) {
            $context = \context_module::instance($setting->cmid);
            $data = (object)[
                'quizid' => $setting->quizid,
                'proctoring_enabled' => $setting->proctoring,
                'application' => $setting->application,
                'timecreated' => \core_privacy\local\request\transform::datetime($setting->timecreated),
                'timemodified' => \core_privacy\local\request\transform::datetime($setting->timemodified),
            ];
            writer::with_context($context)->export_data(
                [get_string('privacy:metadata:quizaccess_oql_quizsettings', 'quizaccess_oqylyq')],
                $data
            );
        }
        $settings->close();

        // Export quiz URLs for this user.
        $sql = "SELECT qu.*
                  FROM {quizaccess_oql_quizurls} qu
                  JOIN {course_modules} cm ON cm.id = qu.cmid
                  JOIN {context} ctx ON ctx.instanceid = cm.id AND ctx.contextlevel = :contextlevel2
                 WHERE ctx.id {$contextsql}
                   AND (qu.userid = :userid2 OR qu.usermodified = :userid3)";

        $params = $contextparams + [
            'contextlevel2' => CONTEXT_MODULE,
            'userid2' => $userid,
            'userid3' => $userid
        ];

        $urls = $DB->get_recordset_sql($sql, $params);
        foreach ($urls as $url) {
            $context = \context_module::instance($url->cmid);
            $data = (object)[
                'quizid' => $url->quizid,
                'url' => $url->url,
                'lifetime' => $url->lifetime,
                'timecreated' => \core_privacy\local\request\transform::datetime($url->timecreated),
                'timemodified' => \core_privacy\local\request\transform::datetime($url->timemodified),
            ];
            writer::with_context($context)->export_data(
                [get_string('privacy:metadata:quizaccess_oql_quizurls', 'quizaccess_oqylyq')],
                $data
            );
        }
        $urls->close();
    }

    /**
     * Delete all user data for the specified user, in the specified contexts.
     *
     * @param approved_contextlist $contextlist The approved contexts and user information to delete information for.
     */
    public static function delete_data_for_user(approved_contextlist $contextlist) {
        global $DB;

        if (empty($contextlist->count())) {
            return;
        }

        $userid = $contextlist->get_user()->id;

        foreach ($contextlist->get_contexts() as $context) {
            if ($context->contextlevel != CONTEXT_MODULE) {
                continue;
            }

            $cmid = $context->instanceid;

            // Delete quiz settings modified by this user.
            $DB->delete_records('quizaccess_oql_quizsettings', [
                'cmid' => $cmid,
                'usermodified' => $userid
            ]);

            // Delete quiz URLs for this user.
            $DB->delete_records('quizaccess_oql_quizurls', [
                'cmid' => $cmid,
                'userid' => $userid
            ]);

            // Also delete URLs modified by this user.
            $DB->delete_records('quizaccess_oql_quizurls', [
                'cmid' => $cmid,
                'usermodified' => $userid
            ]);
        }
    }

    /**
     * Delete all data for all users in the specified context.
     *
     * @param \context $context The specific context to delete data for.
     */
    public static function delete_data_for_all_users_in_context(\context $context) {
        global $DB;

        if ($context->contextlevel != CONTEXT_MODULE) {
            return;
        }

        $cmid = $context->instanceid;

        $DB->delete_records('quizaccess_oql_quizsettings', ['cmid' => $cmid]);
        $DB->delete_records('quizaccess_oql_quizurls', ['cmid' => $cmid]);
    }

    /**
     * Get the list of users who have data within a context.
     *
     * @param userlist $userlist The userlist containing the list of users who have data in this context/plugin combination.
     */
    public static function get_users_in_context(userlist $userlist) {
        $context = $userlist->get_context();

        if ($context->contextlevel != CONTEXT_MODULE) {
            return;
        }

        $cmid = $context->instanceid;

        // Users who modified quiz settings.
        $sql = "SELECT usermodified
                  FROM {quizaccess_oql_quizsettings}
                 WHERE cmid = :cmid";
        $userlist->add_from_sql('usermodified', $sql, ['cmid' => $cmid]);

        // Users who own quiz URLs.
        $sql = "SELECT userid
                  FROM {quizaccess_oql_quizurls}
                 WHERE cmid = :cmid";
        $userlist->add_from_sql('userid', $sql, ['cmid' => $cmid]);

        // Users who modified quiz URLs.
        $sql = "SELECT usermodified
                  FROM {quizaccess_oql_quizurls}
                 WHERE cmid = :cmid";
        $userlist->add_from_sql('usermodified', $sql, ['cmid' => $cmid]);
    }

    /**
     * Delete multiple users within a single context.
     *
     * @param approved_userlist $userlist The approved context and user information to delete information for.
     */
    public static function delete_data_for_users(approved_userlist $userlist) {
        global $DB;

        $context = $userlist->get_context();

        if ($context->contextlevel != CONTEXT_MODULE) {
            return;
        }

        $cmid = $context->instanceid;
        $userids = $userlist->get_userids();

        if (empty($userids)) {
            return;
        }

        list($usersql, $userparams) = $DB->get_in_or_equal($userids, SQL_PARAMS_NAMED);

        // Delete quiz settings.
        $select = "cmid = :cmid AND usermodified {$usersql}";
        $params = ['cmid' => $cmid] + $userparams;
        $DB->delete_records_select('quizaccess_oql_quizsettings', $select, $params);

        // Delete quiz URLs (both userid and usermodified).
        // We need to use OR condition, so we need separate SQL for each field.
        $select = "cmid = :cmid AND userid {$usersql}";
        $params = ['cmid' => $cmid] + $userparams;
        $DB->delete_records_select('quizaccess_oql_quizurls', $select, $params);

        // Also delete quiz URLs modified by these users.
        $select = "cmid = :cmid AND usermodified {$usersql}";
        $params = ['cmid' => $cmid] + $userparams;
        $DB->delete_records_select('quizaccess_oql_quizurls', $select, $params);
    }
}

