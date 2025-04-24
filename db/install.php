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
 * Post installation and migration code.
 *
 * Contains code that are run during the installation of report/course_views
 *
 * @package   report_course_views
 * @copyright 2025 Fondation UNIT <contact@unit.eu>
 * @license   https://opensource.org/license/mit MIT
 */

defined('MOODLE_INTERNAL') || die;

/**
 * @global moodle_database $DB
 * 
 * @return void
 */
function xmldb_report_course_views_install() {
    global $DB;
}

/**
 * Custom uninstallation procedure.
 * 
 * @return bool
 */
function xmldb_report_course_views_uninstall() {
    return true;
}
