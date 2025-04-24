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
 * Scheduled report task.
 *
 * @package   report_visits
 * @copyright 2025 Fondation UNIT <contact@unit.eu>
 * @license   https://opensource.org/license/mit MIT
 */

namespace report_visits\task;

require_once(__DIR__ . '/../../../../config.php');
require_once(__DIR__ . '/../../locallib.php');

use core\message\message;
use moodle_url;

class scheduled_course_report extends \core\task\scheduled_task {
    /**
     * Return the task's name as shown in admin screens.
     *
     * @return string
     */
    public function get_name() {
        return get_string('scheduled_course_report', 'report_visits');
    }

    /**
     * Execute the task.
     */
    public function execute() {
        global $DB;

        $report_visits = new \ReportVisits($DB);
        // Use the most recent schedule timestamp as startdate or 0 in case of a fresh install.
        $last_schedule = $DB->get_record_sql('SELECT `timestamp` FROM {report_visits_schedules} ORDER BY id DESC LIMIT 1;');
        $startdate = ($last_schedule && $last_schedule->timestamp) ? $last_schedule->timestamp : 0;
        $enddate = time();
        $component = "course";

        $report_visits->generate_course_report($component, $startdate, $enddate);
    }
}
