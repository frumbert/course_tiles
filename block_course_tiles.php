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
 * Course list block.
 *
 * @package    block_course_tiles
 * @copyright  tim@avideelearning.com
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

include_once($CFG->dirroot . '/course/lib.php');
require_once($CFG->dirroot . '/course/renderer.php');
require_once($CFG->dirroot . '/course/classes/category.php');
require_once($CFG->dirroot . '/enrol/locallib.php');
require_once($CFG->libdir . '/completionlib.php');

class block_course_tiles extends block_list {

    const COURSECAT_SHOW_COURSES_NONE = 0; /* do not show courses at all */
    const COURSECAT_SHOW_COURSES_COUNT = 5; /* do not show courses but show number of courses next to category name */
    const COURSECAT_SHOW_COURSES_COLLAPSED = 10;
    const COURSECAT_SHOW_COURSES_AUTO = 15; /* will choose between collapsed and expanded automatically */
    const COURSECAT_SHOW_COURSES_EXPANDED = 20;
    const COURSECAT_SHOW_COURSES_EXPANDED_WITH_CAT = 30;

    const COURSECAT_TYPE_CATEGORY = 0;
    const COURSECAT_TYPE_COURSE = 1;

    function init() {
        $this->title = get_string('pluginname', 'block_course_tiles');
    }

    function specialization() {
        if (isset($this->config->title)) {
            $this->title = $this->title = format_string($this->config->title, true, ['context' => $this->context]);
        } else {
            $this->title = get_string('pluginname', 'block_course_tiles');
        }
    }

    function has_config() {
        return true;
    }

    function get_content() {
        global $CFG, $OUTPUT;

        if($this->content !== NULL) {
            return $this->content;
        }

        $showcategory = false;
        if (isset($CFG->block_course_tiles_showcategories)) {
           if ( (int)$CFG->block_course_tiles_showcategories == 1){
               $showcategory = true;
           }
        }

        $this->content = new stdClass;
        $this->content->items = array();
        $this->content->icons = array();
        $this->content->footer = '';

        $icon = $OUTPUT->pix_icon('i/course', get_string('course'));

        $this->content->footer = '';
        $this->content->items[] = $this->render_course_tiles($showcategory);
        return $this->content;
    }

    /**
     * Returns the role that best describes the course list block.
     *
     * @return string
     */
    public function get_aria_role() {
        return 'navigation';
    }


	// copied from course renderer, because of namespace issues and they made the actual function that is most useful protected
    protected function render_course_tiles($showcategorymenu = false) {
        global $CFG, $PAGE;

        $category = optional_param("category", 0, PARAM_INT);

        $chelper = new \coursecat_helper();
        $chelper->set_show_courses(self::COURSECAT_SHOW_COURSES_EXPANDED)->
                set_courses_display_options(array(
                    'recursive' => true,
                    'sort' => ['startdate' => 1, 'fullname' => 1],
                    'limit' => $CFG->frontpagecourselimit,
                    'viewmoreurl' => new \moodle_url('/course/index.php'),
                    'viewmoretext' => new \lang_string('fulllistofcourses')));

        $chelper->set_attributes(array('class' => 'frontpage-course-list-all'));
        $categorydata = [];
        $categories = \core_course_category::make_categories_list();
        if ($showcategorymenu) {
            $description = '';
            if (count($categories) === 1) {
                $category = 0;
            } else {
                foreach ($categories as $key => $value) {
                    if ($category === 0) $category = $key; // select first tab
                    $cssclass = "catalogue-item category-{$key}";
                    $url = new moodle_url($this->page->url, array("category" => $key));
                    $cat = core_course_category::get($key);
                    if ((int)$cat->visible===0) $cssclass .= " dimmed";
                    if ($key === $category) {
                        $cssclass .= " current";
                        // rewrite plugin urls using the category as the context (since it was authored in the category editor)
                        $description = file_rewrite_pluginfile_urls($cat->description, 'pluginfile.php', \context_coursecat::instance($key)->id, 'coursecat', 'description', NULL);
                        // assume nocleaning - a category author already made this, so we can assume it to be trusted
                        $description = format_text($description, $cat->descriptionformat, ['noclean' => true, 'context' => \context_coursecat::instance($key)]);
                    }
                    $categorydata[] = [
                        "url" => $url->out(),
                        "name" => $value,
                        "cssclass" => $cssclass
                    ];
                }
            }
        } else {
            $category = 0;
        }

        $courses = \core_course_category::get(0)->get_courses($chelper->get_courses_display_options());
        $totalcount = \core_course_category::get(0)->get_courses_count($chelper->get_courses_display_options());
        if (!$totalcount && !$this->page->user_is_editing() && has_capability('moodle/course:create', context_system::instance())) {
            // Print link to create a new course, for the 1st available category.
            $courserenderer = $PAGE->get_renderer('core', 'course');
            return $courserenderer->add_new_course_button();
        }
        return $this->catalogue_courses($chelper, $courses, $totalcount, $category, $categorydata, $description, $showcategorymenu);
    }


	// copied from course renderer - we want to override coursecat_coursebox but it and this function were protected so we have to dupe/rename them
    protected function catalogue_courses(\coursecat_helper $chelper, $courses, $totalcount = null, $category = 0, $categorydata = [], $description = '', $showcategorymenu = false) {
        global $CFG, $OUTPUT;
        if ($totalcount === null) {
            $totalcount = count($courses);
        }
        if (!$totalcount) {
            // Courses count is cached during courses retrieval.
            return '';
        }

        if ($chelper->get_show_courses() == self::COURSECAT_SHOW_COURSES_AUTO) {
            // In 'auto' course display mode we analyse if number of courses is more or less than $CFG->courseswithsummarieslimit
            if ($totalcount <= $CFG->courseswithsummarieslimit) {
                $chelper->set_show_courses(self::COURSECAT_SHOW_COURSES_EXPANDED);
            } else {
                $chelper->set_show_courses(self::COURSECAT_SHOW_COURSES_COLLAPSED);
            }
        }

        // prepare content of paging bar if it is needed
        $paginationurl = $chelper->get_courses_display_option('paginationurl');
        $paginationallowall = $chelper->get_courses_display_option('paginationallowall');
        $morelink = $pagingbar = '';
        if ($totalcount > count($courses)) {
            // there are more results that can fit on one page
            if ($paginationurl) {
                // the option paginationurl was specified, display pagingbar
                $perpage = $chelper->get_courses_display_option('limit', $CFG->coursesperpage);
                $page = $chelper->get_courses_display_option('offset') / $perpage;
                $pagingbar = $this->paging_bar($totalcount, $page, $perpage,
                        $paginationurl->out(false, array('perpage' => $perpage)));
                if ($paginationallowall) {
                    $pagingbar .= html_writer::tag('div', html_writer::link($paginationurl->out(false, array('perpage' => 'all')),
                            get_string('showall', '', $totalcount)), array('class' => 'paging paging-showall'));
                }
            } else if ($viewmoreurl = $chelper->get_courses_display_option('viewmoreurl')) {
                // the option for 'View more' link was specified, display more link
                $viewmoretext = $chelper->get_courses_display_option('viewmoretext', new lang_string('viewmore'));
                $morelink = html_writer::tag('div', html_writer::link($viewmoreurl, $viewmoretext),
                        array('class' => 'paging paging-morelink'));
            }
        } else if (($totalcount > $CFG->coursesperpage) && $paginationurl && $paginationallowall) {
            // there are more than one page of results and we are in 'view all' mode, suggest to go back to paginated view mode
            $pagingbar = html_writer::tag('div', html_writer::link($paginationurl->out(false, array('perpage' => $CFG->coursesperpage)),
                get_string('showperpage', '', $CFG->coursesperpage)), array('class' => 'paging paging-showperpage'));
        }

        // display list of courses
        $attributes = $chelper->get_and_erase_attributes('courses');

        $coursedata = [];
        foreach ($courses as $course) {
            if (($category === 0) || ($category > 0 && $course->category === (string)$category)) {
			    $coursedata[] = $this->catalogue_coursebox($chelper, $course);
            }
        }


		// {{.}} tries to output a string; if it's not stringable then you get an exception
		// {{# somevar}} tries to eval somevar from a php point of view
		$data = [
            "showcategories" => count($categorydata) > 0,
            "description" => $description,
            "categories" => $categorydata,
			"courses" => $coursedata,
			"pagingbar" => $pagingbar,
			"morelink" => $morelink,
			"admin" => is_siteadmin(),
		];
        $content = $OUTPUT->render_from_template('block_course_tiles/course_catalogue', $data);

        return $content;
    }

	// WHY are the most useful functions in the renderer protected???
    protected function catalogue_coursebox(\coursecat_helper $chelper, $course, $additionalclasses = '') {
        if ($chelper->get_show_courses() <= self::COURSECAT_SHOW_COURSES_COUNT) {
            return null; // was ''
        }
        return $this->catalogue_coursebox_content($chelper, $course);
    }

    protected function catalogue_coursebox_content(\coursecat_helper $chelper, $course) {
        global $CFG, $PAGE, $USER;
        if ($chelper->get_show_courses() < self::COURSECAT_SHOW_COURSES_EXPANDED) {
            return '';
        }
        if ($course instanceof stdClass) {
            $course = new \core_course_list_element($course);
        }

        $enrollable = is_siteadmin();

        if (!$enrollable) {
	        $manager = new \course_enrolment_manager($PAGE, $course);
			$m = $manager->get_enrolment_instances();
	        foreach ($m as $inst) {
		        if ($inst->status !== "1" && $inst->name !== "manual") {
			        $enrollable = true;
			        break;
		        }
		    }
	    }

		$result = [];
        $result['id'] = $course->id;
        $result['name'] = $chelper->get_course_formatted_name($course);
        $result['enrollable'] = is_siteadmin() || $enrollable;

        // status text shown on top-left of tile
        $result['status'] = $this->get_default_status($course, $USER);

        $info = new completion_info($course);
        if (completion_info::is_enabled_for_site() && $info->is_enabled()) {
            $completions = $info->get_completions($USER->id);
            if ($info->is_tracked_user($USER->id)) { // enrolled in course
                $result['status'] = 'Enrolled';
            }
            if ($info->is_course_complete($USER->id)) {
                $result['status'] = 'Completed';
            }
        }

        if ($course->has_summary()) {
	        $result['summary'] = $chelper->get_course_formatted_summary($course,
                    array('overflowdiv' => true, 'noclean' => true, 'para' => false));
        }

        // display course overview files
        $contentimages = $contentfiles = '';
        foreach ($course->get_course_overviewfiles() as $file) {
            $isimage = $file->is_valid_image();
            $url = file_encode_url("$CFG->wwwroot/pluginfile.php",
                    '/'. $file->get_contextid(). '/'. $file->get_component(). '/'.
                    $file->get_filearea(). $file->get_filepath(). $file->get_filename(), !$isimage);
            if ($isimage) {
                $contentimages .= html_writer::tag('div',
                        html_writer::empty_tag('img', array('src' => $url)),
                        array('class' => 'courseimage'));
            } else {
                $image = $this->output->pix_icon(file_file_icon($file, 24), $file->get_filename(), 'moodle');
                $filename = html_writer::tag('span', $image, array('class' => 'fp-icon')).
                        html_writer::tag('span', $file->get_filename(), array('class' => 'fp-filename'));
                $contentfiles .= html_writer::tag('span',
                        html_writer::link($url, $filename),
                        array('class' => 'coursefile fp-filename-icon'));
            }
        }
        $result['image'] = $contentimages . $contentfiles;

        // display course category if necessary (for example in search results)
        if ($chelper->get_show_courses() == self::COURSECAT_SHOW_COURSES_EXPANDED_WITH_CAT) {
	        $content = '';
            if ($cat = core_course_category::get($course->category, IGNORE_MISSING)) {
                $content .= html_writer::start_tag('div', array('class' => 'coursecat'));
                $content .= get_string('category').': '.
                        html_writer::link(new moodle_url('/course/index.php', array('categoryid' => $cat->id)),
                                $cat->get_formatted_name(), array('class' => $cat->visible ? '' : 'dimmed'));
                $content .= html_writer::end_tag('div'); // .coursecat
            }
            $result['category'] = $content;
        }

        return $result;
    }

    // status is added to the tile as a data attribute, which you can pick up on stylesheets
    protected function get_default_status($course,$user) {
        global $DB;

        // if the course has a paypal enrolment that is enabled, grab the price and use that as the default status
        $row = $DB->get_record_sql("
            SELECT cost FROM {enrol}
            WHERE courseid = :id
            AND enrol = 'paypal'
            AND status = 0
            ORDER BY sortorder ASC", array('id' => $course->id), IGNORE_MULTIPLE);
        if ($row) {
            $cost = floatval($row->cost);
            if ($cost > 0) {
                return '$'.$cost;
            }
        }

        // if the course has a custom attribute called 'price', use that as the status
        // https://moodle.org/mod/forum/discuss.php?d=394717#p1687472
        foreach ($course->customfields as $field) {
            if ($field->get_field()->get('shortname') == 'price' && $field->get_value() > '') {
                return $field->get_value();
            }
        }

        // otherwise return the default status text
        return get_string('default_status_text', 'block_course_tiles');

    }

}


