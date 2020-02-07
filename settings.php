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
 * CustomCourseMenu Block Helper - Settings
 *
 * @package    block_courses_by_role
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;
if ($ADMIN->fulltree) {

	$choices = $DB->get_records_menu("role", null, '', 'shortname,shortname s2');


    $settings->add(new admin_setting_configtext('block_courses_by_role/title',
       get_string('title', 'block_courses_by_role'),
        '',
        get_string('pluginname', 'block_courses_by_role'),
        PARAM_RAW_TRIMMED));

    $settings->add(new admin_setting_configmultiselect('block_courses_by_role/roles',
        get_string('roles', 'block_courses_by_role'),
        get_string('roles_desc', 'block_courses_by_role'),
        ["student","teacher"], $choices));

    $settings->add(new admin_setting_configtextarea('block_courses_by_role/labels',
        get_string('labels', 'block_courses_by_role'),
        get_string('labels_desc', 'block_courses_by_role'),
        "",
        PARAM_RAW));
}