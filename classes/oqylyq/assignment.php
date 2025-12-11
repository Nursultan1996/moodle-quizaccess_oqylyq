<?php

/**
* Class for providing oqylyq assignment.
*
* To make sure there are no inconsistencies between data sets, run tests in tests/phpunit/settings_provider_test.php.
*
* @package    quizaccess_oqylyq
* @author     Eduard Zaukarnaev <eduard.zaukarnaev@gmail.com>
* @copyright  2020 Ertumar LLP
* @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
*/

namespace quizaccess_oqylyq\oqylyq;

class assignment implements icommand {
    protected $assignment = [];

    public function __construct(array $data = []) {
        $this->assignment = $data;
    }

    public function getRequestUrl () : string {
        return '/assignments';
    }

    public function getRequestData () : array {
        return $this->assignment;
    }

    public function getRequestQuery() : array {
        return [];
    }

    public function getRequestHeaders() : array {
        return [];
    }

    public function getRequestMethod () : string {
        return 'POST';
    }
}
