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
 * courses_by_role block
 *
 * @package    block_courses_by_role
 * @copyright  2018 tim@avide.com.au
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

class block_courses_by_role extends block_base {
    function init() {
        $this->config = get_config('block_courses_by_role');
        $this->title = format_string(get_string('pluginname', 'block_courses_by_role'));
        $labels = isset($this->config->labels) ? explode("\n",$this->config->labels) : [];
        $roles = isset($this->config->roles) ? $this->config->roles : 'teacher,student';
        foreach ($labels as $label) {
            list($key,$value) = explode(':',$label);
            $this->rolenames[$key] = $value;
        }
        $this->roles = explode(',',$roles);
    }

    function specialization() {
        $this->title = isset($this->config->title) ? format_string($this->config->title) : format_string("not set then " . get_string('pluginname', 'block_courses_by_role'));
    }

    public function has_config() {
        return true;
    }

    function get_content() {

        global $CFG, $DB, $USER;

        if (empty($this->instance)) {
            $this->content = '';
            return $this->content;
        }

        $this->content = new stdClass();
        $this->content->text = 'enrolled courses';

        // cache the list of courses I can see
        $courses = $this->enrol_get_my_courses();
        // get the list of roles that I am assigned in
        list($cond, $para) = $DB->get_in_or_equal($this->roles);
        $sql = "SELECT ra.id, r.shortname, ra.contextid
                FROM mdl_role_assignments ra
                INNER JOIN mdl_role r ON r.id = ra.roleid
                WHERE ra.userid = ? AND r.shortname
                $cond
                ORDER BY r.`sortorder`";
        $myroles = $DB->get_records_sql($sql, array_merge([$USER->id], $para));

        // this could be rendered a tree, but I'm keeping it uncomplicated
        $lastheading = ""; $open = false;
        foreach ($myroles as $role) {
            if ($role->shortname !== $lastheading) {
                if ($open === true) $ar[] = html_writer::end_tag("ul");
                $ar[] = html_writer::tag("p", html_writer::tag("b", isset($this->rolenames[$role->shortname]) ? $this->rolenames[$role->shortname] : $role->shortname));
                $ar[] = html_writer::start_tag("ul");
                $open = true;
            }
            foreach ($courses as $course) {
                if ($course->ctxid === $role->contextid) {
                    $url = new moodle_url("/course/view.php", array("id" => $course->id));
                    $link = html_writer::link($url, $course->fullname);
                    $ar[] = html_writer::tag("li", $link);
                }
            }
            $lastheading = $role->shortname;
        }
        $ar[] = html_writer::end_tag("ul");
        $this->content->text = implode(PHP_EOL,$ar);
        return $this->content;
    }

    function applicable_formats() {
        return array('all' => true, 'my' => true, 'tag' => false);
    }

    /**
     * This is effectively the same as the enrollib.php version with the same name, BUT I specifically need the context id returned, which is unset by that routine! Argh!
     */
    private function enrol_get_my_courses($fields = null, $sort = 'visible DESC,q.name,sortorder ASC',
                              $limit = 0, $courseids = []) {
        global $DB, $USER;

        // Guest account does not have any courses
        if (isguestuser() or !isloggedin()) {
            return(array());
        }

        $basefields = array('id', 'category', 'sortorder', 'fullname', 'visible');

        if (empty($fields)) {
            $fields = $basefields;
        } else if (is_string($fields)) {
            // turn the fields from a string to an array
            $fields = explode(',', $fields);
            $fields = array_map('trim', $fields);
            $fields = array_unique(array_merge($basefields, $fields));
        } else if (is_array($fields)) {
            $fields = array_unique(array_merge($basefields, $fields));
        } else {
            throw new coding_exception('Invalid $fileds parameter in enrol_get_my_courses()');
        }
        if (in_array('*', $fields)) {
            $fields = array('*');
        }

        $orderby = "";
        $sort    = trim($sort);
        if (!empty($sort)) {
            $rawsorts = explode(',', $sort);
            $sorts = [];
            foreach ($rawsorts as $rawsort) {
                $rawsort = trim($rawsort);
                if (strpos($rawsort, '.') === false) {
                    list($alias,$fld) = ['c', $rawsort];
                } else {
                    list($alias,$fld) = explode('.',$rawsort);
                }
                $sorts[] = "{$alias}.{$fld}";
            }
            $sort = implode(', ', $sorts);
            $orderby = "ORDER BY $sort";
        }

        $wheres = array("c.id <> :siteid");
        $params = array('siteid'=>SITEID);

        if (isset($USER->loginascontext) and $USER->loginascontext->contextlevel == CONTEXT_COURSE) {
            // list _only_ this course - anything else is asking for trouble...
            $wheres[] = "courseid = :loginas";
            $params['loginas'] = $USER->loginascontext->instanceid;
        }

        $coursefields = 'c.' .join(',c.', $fields);
        $ccselect = ', q.name categoryname';
        $ccselect .= ', ' . context_helper::get_preload_record_columns_sql('ctx');
        $ccjoin = "LEFT JOIN {context} ctx ON (ctx.instanceid = c.id AND ctx.contextlevel = :contextlevel)";
        $params['contextlevel'] = CONTEXT_COURSE;
        $wheres = implode(" AND ", $wheres);

        if (!empty($courseids)) {
            list($courseidssql, $courseidsparams) = $DB->get_in_or_equal($courseids, SQL_PARAMS_NAMED);
            $wheres = sprintf("%s AND c.id %s", $wheres, $courseidssql);
            $params = array_merge($params, $courseidsparams);
        }

        //note: we can not use DISTINCT + text fields due to Oracle and MS limitations, that is why we have the subselect there
        $sql = "SELECT $coursefields $ccselect
                  FROM {course} c
                  INNER JOIN {course_categories} q ON c.category = q.id
                  JOIN (SELECT DISTINCT e.courseid
                          FROM {enrol} e
                          JOIN {user_enrolments} ue ON (ue.enrolid = e.id AND ue.userid = :userid)
                         WHERE ue.status = :active AND e.status = :enabled AND ue.timestart < :now1 AND (ue.timeend = 0 OR ue.timeend > :now2)
                       ) en ON (en.courseid = c.id)
               $ccjoin
                 WHERE $wheres
              $orderby";
        $params['userid']  = $USER->id;
        $params['active']  = ENROL_USER_ACTIVE;
        $params['enabled'] = ENROL_INSTANCE_ENABLED;
        $params['now1']    = round(time(), -2); // improves db caching
        $params['now2']    = $params['now1'];
var_dump($sql);
        $courses = $DB->get_records_sql($sql, $params, 0, $limit);
        // preload contexts and check visibility
        foreach ($courses as $id=>$course) {
            if (!$course->visible) {
                if (!$context = context_course::instance($id, IGNORE_MISSING)) {
                    unset($courses[$id]);
                    continue;
                }
                if (!has_capability('moodle/course:viewhiddencourses', $context)) {
                    unset($courses[$id]);
                    continue;
                }
            }
            $courses[$id] = $course;
        }

        //wow! Is that really all? :-D
        return $courses;
    }

}
