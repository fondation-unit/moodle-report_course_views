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
 * @copyright 2025 Fondation UNIT
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Pierre Duverneix
 */

namespace report_visits\task;

require_once(__DIR__ . '/../../../../config.php');
require_once(__DIR__ . '/../../locallib.php');

use core\message\message;
use moodle_url;

class scheduled_report extends \core\task\scheduled_task {
    /**
     * Return the task's name as shown in admin screens.
     *
     * @return string
     */
    public function get_name() {
        return get_string('scheduled_report', 'report_visits');
    }

    /**
     * Execute the task.
     */
    public function execute() {
        global $DB;

        $report_visits = new \ReportVisits($DB);
        $last_schedule = $DB->get_record_sql('SELECT `timestamp` FROM {report_visits_schedules} ORDER BY id DESC LIMIT 1;');
        $startdate = ($last_schedule && $last_schedule->timestamp) ? $last_schedule->timestamp : 0;
        $enddate = time();
        $component = "course";
        $records = $report_visits->generate_course_report($startdate, $enddate);

        // Create a new schedule record.
        $schedule = new \stdClass();
        $schedule->component = $component;
        $schedule->status = 1;
        $schedule->timestamp = time();
        $schedule_id = $DB->insert_record('report_visits_schedules', $schedule, true);

        foreach ($records as $record) {
            $existing = $DB->get_record('report_visits', ['component' => $component, 'component_id' => $record->id]);
            if ($existing) {
                // Update the existing record.
                $existing->score = intval($existing->score) + intval($record->score);
                $existing->timestamp = time();
                $existing->schedule_id = $schedule_id;

                $DB->update_record('report_visits', $existing);
            } else {
                // Create a new record.
                $obj = new \stdClass();
                $obj->component = $component;
                $obj->score = $record->score;
                $obj->timestamp = time();
                $obj->component_id = $record->id;
                $obj->schedule_id = $schedule_id;

                $DB->insert_record('report_visits', $obj);
            }
        }
    }
}
