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
 * report_visits local library.
 *
 * @package   report_visits
 * @copyright 2025 Fondation UNIT
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Pierre Duverneix
 */

defined('MOODLE_INTERNAL') || die;

use paging_bar;

class ReportVisits {
    /**
     * @var moodle_database Moodle's database connector.
     */
    protected $db;

    /**
     * @var int The current page.
     */
    protected $page;

    /**
     * @var int The perpage setting value.
     */
    protected $perpage;

    /**
     * Class constructor.
     *
     * @param moodle_database $db
     * @param int $page
     * @param int $perpage
     */
    public function __construct($db, $page = 0, $perpage = 10) {
        $this->db = $db;
        $this->page = $page;
        $this->perpage = $perpage;
    }

    /**
     * Debug printing function.
     * 
     * @return void
     */
    public static function print_records_debug(array $records) {
        print "<pre>";
        print_r($records);
        print "</pre>";
    }

    /**
     * Initiate a course visits report.
     * 
     * @return function
     */ 
    public function query_course_visits(string $component) {
        $component_ids = $this->db->get_fieldset('report_visits', 'component_id', ['component' => $component]);
        $records = $this->query_course_infos($component_ids);

        return $this->format_course_records($records);
    }

    /**
     * Create a new course schedule record, then query the logs for the given timestamps.
     * 
     * @return void
     */ 
    public function generate_course_report(string $component, int $startdate, int $enddate) {
        // Create a new schedule record.
        $schedule = new \stdClass();
        $schedule->component = $component;
        $schedule->status = 1;
        $schedule->timestamp = time();
        $schedule_id = $this->db->insert_record('report_visits_schedules', $schedule, true);

        // Query the course logs.
        $records = $this->query_course_records($startdate, $enddate);

        foreach ($records as $record) {
            $existing = $this->db->get_record('report_visits', ['component' => $component, 'component_id' => $record->id]);
            if ($existing) {
                // Update the existing record.
                $existing->score = intval($existing->score) + intval($record->score);
                $existing->timestamp = time();
                $existing->schedule_id = $schedule_id;

                $this->db->update_record('report_visits', $existing);
            } else {
                // Create a new record.
                $obj = new \stdClass();
                $obj->component = $component;
                $obj->score = $record->score;
                $obj->timestamp = time();
                $obj->component_id = $record->id;
                $obj->schedule_id = $schedule_id;

                $this->db->insert_record('report_visits', $obj);
            }
        }
    }

    /**
     * Retrieve course records for the given course IDs.
     * 
     * @return \stdClass|null
     */
    public function query_total_course_infos() {
        $component_ids = $this->db->get_fieldset('report_visits', 'component_id', ['component' => 'course']);

        // Validate the component IDs.
        if (empty($component_ids)) {
            $obj = new \stdClass();
            $obj->total = 0;
            return $obj;
        }

        list($in_sql, $params) = $this->db->get_in_or_equal($component_ids, SQL_PARAMS_NAMED);

        $sql = "SELECT COUNT(id) as total
                FROM {report_visits}
                WHERE component_id $in_sql
                LIMIT 1";

        return $this->db->get_record_sql($sql, $params);
    }

    /**
     * Retrieve course records for the given course IDs.
     * 
     * @return array
     */
    private function query_course_infos(array $course_ids) {
        // Validate the course IDs.
        if (empty($course_ids)) {
            return [];
        }

        // Create the placeholder param for each course ID.
        list($in_sql, $params) = $this->db->get_in_or_equal($course_ids, SQL_PARAMS_NAMED);

        $sql = "SELECT c.id,
                    c.fullname,
                    cc.id AS category_id,
                    cc.name AS category,
                    rv.score AS score
                FROM {logstore_standard_log} log
                INNER JOIN {course} c ON c.id = log.courseid
                INNER JOIN {course_categories} cc ON c.category = cc.id
                INNER JOIN {report_visits} rv ON rv.component_id = c.id
                WHERE (log.courseid > 0 
                    OR (log.action LIKE 'viewed'))
                AND c.id $in_sql
                GROUP BY c.id
                ORDER BY score DESC";

        $offset = intval($this->page) * intval($this->perpage);
        return $this->db->get_records_sql($sql, $params, $offset, $this->perpage);
    }

    /**
     * Retrieve the logs corresponding to course views between the given timestamps.
     * 
     * @return array
     */
    private function query_course_records(int $startdate, int $enddate) {
        $sql = "SELECT c.id,
                    c.fullname,
                    cc.name AS category,
                    COUNT(log.courseid) AS score
                FROM {logstore_standard_log} log
                INNER JOIN {course} c ON c.id = log.courseid
                INNER JOIN {course_categories} cc ON c.category = cc.id
                WHERE (log.courseid > 0 
                    OR (log.action LIKE 'viewed'))
                AND log.timecreated BETWEEN :startdate AND :enddate
                GROUP BY c.id, c.fullname, cc.name
                ORDER BY score DESC";

        return $this->db->get_records_sql($sql, [
            'startdate' => $startdate,
            'enddate' => $enddate
        ]);
    }

    /**
     * Format the course records for the template.
     * We need links to the course and its category.
     * 
     * @return array
     */
    private function format_course_records(array $records) {
      foreach ($records as $record) {
            // Create an URL to the course.
            $courseurl = new \moodle_url('/course/view.php', array('id' => $record->id));
            $record->course_url = $courseurl->out(false);
            // Create an URL to the course category.
            $categoryurl = new \moodle_url('/course/index.php', array('categoryid' => $record->category_id));
            $record->category_url = $categoryurl->out(false);
        }

        return array_values($records);
    }

    /**
     * Create a paging_bar object for the template.
     * 
     * @return paging_bar
     */
    public function create_pagingbar($component) {
        global $CFG;

        $records = $this->query_total_course_infos($component);
        $baseurl = "$CFG->wwwroot/report/visits/view.php";
        $pagingbar = new paging_bar($records->total, $this->page, $this->perpage, $baseurl);

        return $pagingbar;
    }
}
