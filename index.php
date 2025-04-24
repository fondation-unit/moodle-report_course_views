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
 * Index file.
 *
 * @package   report_course_views
 * @copyright 2025 Fondation UNIT <contact@unit.eu>
 * @license   https://opensource.org/license/mit MIT
 */

require(__DIR__ . '/../../config.php');
require(__DIR__ . '/locallib.php');

global $DB, $PAGE;

$selectedyear = optional_param('y', date('Y'), PARAM_INT);
$systemcontext = \context_system::instance();

$PAGE->set_url('/report/course_views/index.php');
$PAGE->set_context($systemcontext);
$PAGE->set_title(get_string('pluginname', 'report_course_views'));
$PAGE->set_heading(get_string('pluginname', 'report_course_views'));
$PAGE->set_pagelayout('admin');

$pluginurl = new \moodle_url('/report/course_views/index.php');
$page = optional_param('page', 0, PARAM_INT);
$tab = optional_param('t', 1, PARAM_INT);

$tabs = [];
$tabs[] = new \tabobject(1, new \moodle_url($pluginurl, ['t' => 1]), get_string('course_report', 'report_course_views'));
$perpage = get_config('report_course_views', 'perpage');
$report_course_views = new \ReportCourseViews($DB, $selectedyear, $page, $perpage);
$output = $PAGE->get_renderer('report_course_views');
$pagingbar = $report_course_views->create_pagingbar('course');

echo $OUTPUT->header();
echo $OUTPUT->tabtree($tabs, $tab);

// Define available years for selection
$current_year = date('Y');
$years = range($current_year - 5, $current_year);

// Form to select year
$url = new \moodle_url('/report/course_views/index.php');
$options = array_combine($years, $years);
$select = new \single_select($url, 'y', $options, $selectedyear, null, 'yearselect');
$select->set_label(get_string('year', 'report_course_views'), array('class' => 'pe-2'));

echo $OUTPUT->render($select);

// Display the tab view.
if ($tab == 1) {
    $records = $report_course_views->query_course_views("course");
    $renderable = new \report_course_views\output\course_report($records, $pagingbar);
    echo $output->render($renderable);
}

echo $OUTPUT->footer();
