<?php

namespace local_expnotas\task;

defined('MOODLE_INTERNAL') || die();

class grade_export_task extends \core\task\scheduled_task {

    /**
     * Return the task's name as shown in admin screens.
     *
     * @return string
     */
    public function get_name() {
        return get_string('exporttaskname', 'local_expnotas');
    }

    /**
     * Execute the task.
     */
    public function execute() {
        global $CFG, $DB;
        if(!empty($CFG->enableportfolios)) {
            require_once($CFG->dirroot . '/local/expnotas/lib.php');
            if($destinations = str_getcsv(get_config('expnotas', 'schedulingdestinations'))) {
                local_expnotas_export_all_grades($destinations);
            }
        }
    }


}
