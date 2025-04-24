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
 * Settings and links
 *
 * @package   report_course_views
 * @copyright 2025 Fondation UNIT <contact@unit.eu>
 * @license   https://opensource.org/license/mit MIT
 */

defined('MOODLE_INTERNAL') || die;

$hassiteconfig = has_capability('moodle/site:config', context_system::instance());

if ($hassiteconfig) {
    // Add the report's view page.
    $ADMIN->add('reports', new admin_externalpage(
        'reportcourseviews_index', // Unique identifier
        get_string('pluginname', 'report_course_views'),
        $CFG->wwwroot . "/report/course_views/index.php",
        'report/course_views:index'
    ));

    // Add the plugin's specific settings page.
    $settings->add(new admin_setting_heading(
        'report_course_views/pluginname',
        '',
        new lang_string('settings', 'report_course_views')
    ));

    $settings->add(new admin_setting_configtext(
        'report_course_views/perpage',
        new lang_string('perpage', 'report_course_views'),
        new lang_string('perpage_desc', 'report_course_views'),
        10,
        PARAM_INT
    ));
}
