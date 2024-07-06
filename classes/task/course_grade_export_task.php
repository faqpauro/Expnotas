<?php

namespace local_expnotas\task;

defined('MOODLE_INTERNAL') || die();

class course_grade_export_task extends \core\task\scheduled_task {

    /**
     * Return the task's name as shown in admin screens.
     *
     * @return string
     */
    public function get_name() {
        return get_string('courseexporttaskname', 'local_expnotas');
    }

    /**
     * Execute the task.
     */
    public function execute() {
        global $CFG, $DB;
        if($destinations = str_getcsv(get_config('expnotas', 'schedulingdestinations'))) {
            require_once($CFG->dirroot . '/local/expnotas/lib.php');
            $courses = get_courses();
            local_expnotas_cleanup();
            foreach($courses as $course) {
                $metadata = local_expnotas_get_course_metadata($course->id);
                if (!empty($metadata['autoexportenable'])
                && !empty($metadata['autoexportfrequency'])) {
                    $exportfrequency = intval($metadata['autoexportfrequency']);
                    if($metadata['autoexportenable']) {
                        $now = time();
                        $exportdate = array_shift($DB->get_records(
                        'expnotas_export_history',
                        [
                        'courseid' => $course->id,
                        ],
                        'time DESC',
                        'time',
                        0,
                        1,
                        ));

                        if (!isset($exportdate) || (($now - $exportdate->time) > ($exportfrequency * 60))) {
                              local_expnotas_prepare_file($course);
                              $success = false;
                            foreach($destinations as $destination) {
                                if(local_expnotas_send_grades($destination)) {
                                    $success = true;
                                }
                            }
                            if ($success) {
                                local_expnotas_log_export($course->id);
                            }
                            local_expnotas_cleanup();
                        }

                    }
                }

            }
        }
    }


}
