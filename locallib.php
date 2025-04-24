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
 * @copyright 2025 Fondation UNIT <contact@unit.eu>
 * @license   https://opensource.org/license/mit MIT
 */

defined('MOODLE_INTERNAL') || die;

class ReportVisits {
    /** @var \moodle_database Moodle database connector. */
    protected $db;

    /** @var int The selected year. */
    protected $selectedyear;

    /** @var int The current page. */
    protected $page;

    /** @var int The perpage setting value. */
    protected $perpage;

    /** @var \cache_application Cache instance. */
    private \cache_application $cache;

    /**
     * Class constructor.
     *
     * @param \moodle_database $db
     * @param int $page
     * @param int $perpage
     */
    public function __construct($db, $selectedyear = null, $page = 0, $perpage = 10) {
        $this->db = $db;
        $this->selectedyear = intval($selectedyear);
        $this->page = $page;
        $this->perpage = $perpage;
        $this->cache = \cache::make('report_visits', 'course_visits');
    }

    /**
     * Initiate a course visits report.
     * 
     * @return function
     */
    public function query_course_visits(string $component) {
        // Cache the component IDs.
        $cache_key = "course_visits_" . md5($component);
        $component_ids = isset($cache_key) ? $this->cache->get($cache_key) : null;

        if (!is_array($component_ids)) {
            $component_ids = $this->db->get_fieldset('report_visits', 'component_id', ['component' => $component]);
            $this->cache->set($cache_key, $component_ids, 3600); // Cache duration.
        } else {
            // Ensure all cached elements are integers.
            $component_ids = array_map('intval', array_filter($component_ids, 'is_numeric'));
        }

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
        // Prepare an array to store the new records that need to be created.
        $newrecords = [];

        foreach ($records as $record) {
            // Retrieve any existing record.
            $existingrecord = $this->db->get_record('report_visits', [
                'component' => $component,
                'component_id' => $record->id,
                'year' => $record->year
            ]);

            if ($existingrecord) {
                // Update the existing record.
                $existingrecord->total = intval($existingrecord->total) + intval($record->total);
                $existingrecord->timestamp = time();
                $existingrecord->schedule_id = $schedule_id;
                $this->db->update_record('report_visits', $existingrecord);
            } else {
                // Create a new record object.
                $obj = new \stdClass();
                $obj->component = $component;
                $obj->total = $record->total;
                $obj->timestamp = time();
                $obj->year = $record->year;
                $obj->component_id = $record->id;
                $obj->schedule_id = $schedule_id;
                $newrecords[] = $obj;
            }
        }

        // Insert multiple records into the table.
        $this->db->insert_records('report_visits', $newrecords);
    }

    /**
     * Retrieve course records for the given course IDs.
     * 
     * @return \stdClass
     */
    public function count_course_records($component) {
        $component_ids = $this->db->get_fieldset('report_visits', 'component_id', ['component' => $component]);
        // Validate the component IDs.
        if (empty($component_ids)) {
            return 0;
        }

        list($in_sql, $params) = $this->db->get_in_or_equal($component_ids, SQL_PARAMS_NAMED);
        $params['year'] = $this->selectedyear; // Add the selected year as a parameter.
        $sql = "SELECT COUNT(*) FROM {report_visits} WHERE year = :year AND component_id $in_sql";

        return $this->db->count_records_sql($sql, $params);
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
        $params['y'] = $this->selectedyear; // Add the selected year as a parameter.

        $sql = "SELECT c.id,
                    c.fullname,
                    cc.id AS category_id,
                    cc.name AS category,
                    rv.total AS total
                FROM {course} c
                INNER JOIN {course_categories} cc ON c.category = cc.id
                INNER JOIN {report_visits} rv ON rv.component_id = c.id
                WHERE EXISTS (
                    SELECT 1 
                    FROM {logstore_standard_log} log 
                    WHERE log.courseid = c.id 
                    AND (log.courseid > 0 OR log.action = 'viewed')
                    LIMIT 1
                )
                AND c.id $in_sql
                AND year = :y
                GROUP BY c.id, c.fullname, cc.id, cc.name, rv.total
                ORDER BY rv.total DESC";

        $offset = intval($this->page) * intval($this->perpage);
        return $this->db->get_records_sql($sql, $params, $offset, $this->perpage);
    }

    /**
     * Retrieve the logs corresponding to course views between the given timestamps.
     * 
     * @return array
     */
    private function query_course_records(int $startdate, int $enddate) {
        // Different date extraction syntax based on database type.
        if ($this->db->get_dbfamily() === 'postgres') {
            $year = "EXTRACT(YEAR FROM to_timestamp(log.timecreated))";
        } else {
            $year = "YEAR(FROM_UNIXTIME(log.timecreated))";
        }

        $sql = "SELECT
                    CONCAT(c.id, '-', {$year}) AS uniqueid,
                    c.id,
                    c.fullname,
                    cc.name AS category,
                    {$year} as year,
                    COUNT(log.courseid) AS total
                FROM {logstore_standard_log} log
                INNER JOIN {course} c ON c.id = log.courseid
                INNER JOIN {course_categories} cc ON c.category = cc.id
                WHERE (
                    log.courseid > 0 
                    OR (log.action LIKE 'viewed')
                )
                AND log.timecreated BETWEEN :startdate AND :enddate
                GROUP BY
                    c.id,
                    c.fullname,
                    cc.name,
                    {$year}
                ORDER BY
                    year DESC,
                    total DESC";

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
        $course_url_base = new \moodle_url('/course/view.php');
        $category_url_base = new \moodle_url('/course/index.php');

        foreach ($records as $record) {
            $course_url_base->param('id', $record->id);
            $category_url_base->param('categoryid', $record->category_id);

            $record->course_url = $course_url_base->out(false);
            $record->category_url = $category_url_base->out(false);
        }

        return array_values($records);
    }

    /**
     * Create a paging_bar object for the template.
     * 
     * @return \paging_bar
     */
    public function create_pagingbar($component) {
        global $CFG;

        $recordscount = $this->count_course_records($component);
        $baseurl = "$CFG->wwwroot/report/visits/view.php?y=" . urlencode($this->selectedyear);
        $pagingbar = new \paging_bar($recordscount, $this->page, $this->perpage, $baseurl);

        return $pagingbar;
    }
}
