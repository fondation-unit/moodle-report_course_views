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
 * @package   report_visits
 * @copyright 2025 Fondation UNIT
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Pierre Duverneix
 */

defined('MOODLE_INTERNAL') || die;

$hassiteconfig = has_capability('moodle/site:config', context_system::instance());

if ($hassiteconfig) {
    // Add the report's view page.
    $ADMIN->add('reports', new admin_externalpage(
        'reportvisits_view', // Unique identifier
        get_string('pluginname', 'report_visits'),
        $CFG->wwwroot . "/report/visits/view.php",
        'report/visits:view'
    ));

    // Add the plugin's specific settings page.
    $settings->add(new admin_setting_heading('report_visits/pluginname', '',
        new lang_string('settings', 'report_visits')));

    $settings->add(new admin_setting_configtext(
        'report_visits/perpage',
        new lang_string('perpage', 'report_visits'),
        new lang_string('perpage_desc', 'report_visits'),
        10,
        PARAM_INT
    ));
}
