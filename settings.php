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
 * Course list block settings
 *
 * @package    block_course_tiles
 * @copyright  tim@avideelearning.com
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

if ($ADMIN->fulltree) {
    $options = array('all'=>get_string('allcourses', 'block_course_tiles'), 'own'=>get_string('owncourses', 'block_course_tiles'));

    $settings->add(new admin_setting_configselect('block_course_tiles_adminview', get_string('adminview', 'block_course_tiles'),
                       get_string('configadminview', 'block_course_tiles'), 'all', $options));

    $settings->add(new admin_setting_configcheckbox('block_course_tiles_hideallcourseslink', get_string('hideallcourseslink', 'block_course_tiles'),
                       get_string('confighideallcourseslink', 'block_course_tiles'), 0));
}


