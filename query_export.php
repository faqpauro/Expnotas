<?php
require_once('../../config.php');
require_once($CFG->libdir.'/adminlib.php');

admin_externalpage_setup('exportquery');

require_login();
require_capability('moodle/site:config', context_system::instance());


$title = get_string('exporttitle', 'local_expnotas');
$heading = get_string('exportheadingquery', 'local_expnotas');

$PAGE->set_url('/local/expnotas/query_export.php');
$PAGE->set_title($title);

$form = new \local_expnotas\form\query_form();

if ($form->is_cancelled()) {
    redirect(new moodle_url('/query_export.php'));
} else if ($fromform = $form->get_data()) {
    $sql = required_param('sqlquery', PARAM_RAW);  // Asegúrate de validar y limpiar adecuadamente esta entrada.
    export_query_result($sql);
} else {
    // TODO: Exception handling
    // 'No data received from form or form not submitted via POST.';
}

$output = $PAGE->get_renderer('local_expnotas');

echo $output->header();
echo $output->heading($heading);

$form->display();

echo $output->footer();

function export_query_result($sql) {
    global $DB, $CFG;
    require_once($CFG->libdir . '/csvlib.class.php');

    $filename = "custom_query_export.csv";
    $csvexport = new csv_export_writer();
    $csvexport->set_filename($filename);

    if ($records = $DB->get_records_sql($sql)) {
        foreach ($records as $record) {
            $csvexport->add_data(get_object_vars($record));
        }
    }

    $csvexport->download_file();
    exit;
}

