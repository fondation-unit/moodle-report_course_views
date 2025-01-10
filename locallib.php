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

class ReportVisits {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    public static function print_records_debug($records) {
        print "<pre>";
        print_r($records);
        print "</pre>";
    }

    public function query_visits($component) {
        $component_ids = $this->db->get_fieldset('report_visits', 'component_id', ['component' => $component]);
        $records = $this->query_course_infos($component_ids);

        return $this->format_course_records($records);
    }

    public function generate_course_report($startdate, $enddate) {
        $records = $this->query_course_records($startdate, $enddate);
        return $this->format_course_records($records);
    }

    private function query_course_infos($course_ids) {
        // Validate the course IDs.
        if (empty($course_ids)) {
            return [];
        }
        // Create the placeholder param for each course ID.
        list($in_sql, $params) = $this->db->get_in_or_equal($course_ids, SQL_PARAMS_NAMED);

        $sql = "SELECT c.id, c.fullname, cc.name AS `category`, COUNT(log.courseid) AS `score`
                FROM {logstore_standard_log} AS `log`
                INNER JOIN {course} AS `c` ON c.id = log.courseid
                INNER JOIN {course_categories} AS `cc` ON c.category = cc.id
                WHERE log.courseid > 0
                AND c.id $in_sql
                GROUP BY log.courseid
                ORDER BY `score` DESC";

        return $this->db->get_records_sql($sql, $params);
    }

    private function query_course_records($startdate, $enddate) {
        $sql = "SELECT c.id, c.fullname, cc.name AS `category`, COUNT(log.courseid) AS `score`
                FROM {logstore_standard_log} AS `log`
                INNER JOIN {course} AS `c` ON c.id = log.courseid
                INNER JOIN {course_categories} AS `cc` ON c.category = cc.id
                WHERE log.courseid > 0
                AND (log.timecreated BETWEEN :startdate AND :enddate)
                GROUP BY log.courseid
                ORDER BY `score` DESC";

        return $this->db->get_records_sql($sql, [
            'startdate' => $startdate,
            'enddate' => $enddate
        ]);
    }

    private function format_course_records($records) {
        foreach ($records as $record) {
            $url = new \moodle_url('/course/view.php', array('id' => $record->id));
            $record->course_url = $url->out(false);
        }

        return array_values($records);
    }
}
