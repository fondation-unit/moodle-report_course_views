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
 * View file.
 *
 * @package   report_visits
 * @copyright 2025 Fondation UNIT
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Pierre Duverneix
 */

require(__DIR__ . '/../../config.php');
require(__DIR__ . '/locallib.php');

global $DB, $PAGE;

$systemcontext = context_system::instance();

$PAGE->set_url('/report/visits/view.php');
$PAGE->set_context($systemcontext);
$PAGE->set_title(get_string('pluginname', 'report_visits'));
$PAGE->set_heading(get_string('pluginname', 'report_visits'));
$PAGE->set_pagelayout('admin');

$pluginurl = new \moodle_url('/report/visits/view.php');
$page = optional_param('page', 0, PARAM_INT);
$tab = optional_param('t', 1, PARAM_INT);
$tabs = [];
$tabs[] = new tabobject(1, new moodle_url($pluginurl, ['t' => 1]), get_string('course_report', 'report_visits'));

echo $OUTPUT->header();

$perpage = get_config('report_visits', 'perpage');
$report_visits = new \ReportVisits($DB, $page, $perpage);
$output = $PAGE->get_renderer('report_visits');
$pagingbar = $report_visits->create_pagingbar("course");

echo $OUTPUT->tabtree($tabs, $tab);

// Display the tab view.
if ($tab == 1) {
    $records = $report_visits->query_course_visits("course");
    $renderable = new \report_visits\output\course_report($records, $pagingbar);
    echo $output->render($renderable);
}

echo $OUTPUT->footer();
