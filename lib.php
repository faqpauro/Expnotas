<?php

defined('MOODLE_INTERNAL') || die();


function local_expnotas_get_data($courseid, $groupid) {
    global $DB;
    // Consulta para obtener las calificaciones y estado de los estudiantes del curso
    $sql = "SELECT g.id, u.id AS userid, u.firstname, u.lastname, ue.status AS suspended, g.finalgrade, g.timemodified, gi.itemname
    FROM {user} u
    JOIN {grade_grades} g ON g.userid = u.id
    JOIN {grade_items} gi ON gi.id = g.itemid
    JOIN {enrol} e ON e.courseid = gi.courseid
    JOIN {user_enrolments} ue ON ue.enrolid = e.id AND ue.userid = u.id
    ".($groupid > 0 ? "JOIN {groups_members} gm ON gm.userid = u.id AND gm.groupid = :groupid" : "")."
    WHERE gi.courseid = :courseid AND gi.itemtype = 'mod'";
    $params = ['courseid' => $courseid];
    if ($groupid > 0) {
        $params['groupid'] = $groupid;
    }
    $grades = $DB->get_records_sql($sql, $params);
    return $grades;
}

function local_expnotas_populate_data($exporter, $fields, $courseid) {
    global $DB;
    $settingsfields = str_getcsv(get_config('expnotas', 'customfields'));
    $customfieldnames = [];

    if ($customfields = $DB->get_records('user_info_field', [], 'sortorder ASC')) {
        foreach ($customfields as $field) {
            $fieldshortname = $field->shortname;
            if(in_array($fieldshortname, $settingsfields)) {
                $fieldname = format_string($field->name);
                $customfieldnames[] = [
                    'id' => $field->id,
                    'shortname' => $fieldshortname,
                    'name' => $fieldname,
                    'defaultdata' => $field->defaultdata,
                ];
            }
        }
    }

    // Encabezado
    $headerfields = [
        get_string('studentname', 'local_expnotas'),
        get_string('assignment', 'local_expnotas'),
        get_string('grade', 'local_expnotas'),
        get_string('date', 'local_expnotas'),
        get_string('teachers', 'local_expnotas'),
        get_string('status', 'local_expnotas'),
    ];

    foreach ($customfieldnames as $customfieldname) {
        $headerfields[] = $customfieldname['name'];
    }

    $exporter->add_row($headerfields);
    // Agrega los datos de cada estudiante
    foreach ($fields as $grade) {
        $status = ($grade->suspended == 1)
            ? get_string('suspended', 'local_expnotas')
            : get_string('active', 'local_expnotas');

        $graderow = [
            $grade->firstname . ' ' . $grade->lastname,
            $grade->itemname,  // Incluir el nombre de la evaluación
            $grade->finalgrade ? round($grade->finalgrade, 2) : 0,
            userdate($grade->timemodified),
            local_expnotas_get_teachers($courseid),
            $status,
        ];

        foreach ($customfieldnames as $customfield) {
            $sql = "SELECT uid.data
                    FROM {user_info_data} uid
                    WHERE uid.userid = :userid AND uid.fieldid = :customfieldid";
            $params = [
                'userid' => $grade->userid,
                'customfieldid' => $customfield['id'],
            ];
            $fielddata = $DB->get_record_sql($sql, $params);
            if($fielddata) {
                $graderow[] = $fielddata->data;
            } else {
                $graderow[] = $customfield['defaultdata'];
            }
        }
        $exporter->add_row($graderow);
    }

}

function local_expnotas_get_teachers($courseid) {
    global $DB;
    // Consulta para obtener los nombres de los profesores del curso
    $teachersql = "SELECT CONCAT(u.firstname, ' ', u.lastname) AS teacher_name
    FROM {role_assignments} ra
    JOIN {context} ctx ON ra.contextid = ctx.id AND ctx.contextlevel = 50
    JOIN {user} u ON ra.userid = u.id
    WHERE ra.roleid = 3 AND ctx.instanceid = :courseid";
    $teachers = $DB->get_records_sql($teachersql, ['courseid' => $courseid]);
    $teachernames = implode(', ', array_map(function($item) {
        return $item->teacher_name;
    }, $teachers));

    return $teachernames;
}

function local_expnotas_get_course_metadata($courseid) {
    $handler = \core_customfield\handler::get_handler('core_course', 'course');
    $datas = $handler->get_instance_data($courseid);
    $metadata = [];
    foreach ($datas as $data) {
        if (empty($data->get_value())) {
            continue;
        }
        $metadata[$data->get_field()->get('shortname')] = $data->get_value();
    }
    return $metadata;
}

function local_expnotas_prepare_file($course) {
    $exportdata = [];
    $category = core_course_category::get($course->category);
    $categoryname = $category->get_nested_name(false, '/');
    $data = new \local_expnotas\local\exporter\ods_entries_exporter();
    $groups = groups_get_all_groups($course->id, 0, 0, 'g.id, g.name', false, false);
    $sendfile = true;
    if ($groups) {
        foreach ($groups as $group) {
            if($grades = local_expnotas_get_data($course->id, $group->id)) {
                local_expnotas_populate_data($data, $grades, $course->id);
                $data->add_worksheet($group->name);
            }
        }
    } else {
        if($grades = local_expnotas_get_data($course->id, 0)) {
            local_expnotas_populate_data($data, $grades, $course->id);
            $data->add_worksheet('');
        } else {
            // probably an empty course
            $sendfile = false;
        }
    }
    if($sendfile) {
        $data->set_export_itemid($course->id);
        $data->set_export_filename($course->fullname . '.' . $data->get_export_data_file_extension());
        $data->set_export_filepath('/'.$categoryname.'/');
        $data->send_file();
    }

}

function local_expnotas_prepare_files() {
    $categories = core_course_category::make_categories_list();
    foreach ($categories as $categoryid => $categoryname) {
        $category = core_course_category::get($categoryid);
        if(!$category->has_children() && $category->get_courses_count() > 0) {
            $courses = $category->get_courses();
            foreach ($courses as $course) {
                local_expnotas_prepare_file($course);
            }
        }
    }
}

function local_expnotas_get_files() {
    $fs = get_file_storage();
    $files = $fs->get_area_files(SYSCONTEXTID, 'expnotas', 'destination', false, 'sortorder, itemid, filepath, filename', false);
    if (empty($files)) {
        return [];
    }
    $returnfiles = [];
    foreach ($files as $f) {
        $returnfiles[$f->get_filepath() . $f->get_filename()] = $f;
    }
    return $returnfiles;
}

function local_expnotas_download_file() {
    global $CFG;

    $filename = clean_filename("exportacion_calificaciones");

    $exporter = new \local_expnotas\local\exporter\zip_exporter();

    $files = local_expnotas_get_files();

    foreach ($files as $file) {
        $exporter->add_file_from_string(
            $file->get_filename(), $file->get_content(), $file->get_filepath());
    }

    $exporter->set_export_file_name($filename);
    $exporter->send_file(true);
}

function local_expnotas_send_grades($destination) {
    if ($destination !== 'download') {
        $success = false;
        try {
            $destinationname = '\local_expnotas\destination\\' . $destination;
            $destinationinstance = new $destinationname();
            $destinationinstance->send();
            $success = true;
        } catch (Exception $e) {
            // TODO: Exception handling
        }
        return $success;
    } else {
        local_expnotas_download_file();
        return true;
    }
}

function local_expnotas_log_export($courseid) {
    global $DB;
    $e = (object)[
        'courseid' => $courseid,
        'time' => time(),
    ];
    $DB->insert_record('expnotas_export_history', $e);

}

function local_expnotas_cleanup() {
    $fs = get_file_storage();
    $fs->delete_area_files(SYSCONTEXTID, 'expnotas', 'destination');
}

/**
 * Función para exportar calificaciones,
 * incluyendo los profesores del curso y el estado de suspensión del alumno a nivel de curso.
 *
 * @return bool Retorna true si el archivo se genera exitosamente, false en caso contrario.
 * @package local_expnotas
 */
function local_expnotas_export_all_grades($destinations) {
    local_expnotas_cleanup();
    local_expnotas_prepare_files();
    $success = false;
    foreach ($destinations as $destination) {
        if (local_expnotas_send_grades($destination)) {
            $success = true;
        }
    }
    if ($success) {
        local_expnotas_log_export(0);
    }
    local_expnotas_cleanup();
    return true;
}
