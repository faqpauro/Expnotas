<?php
require_once('../../config.php');
require_once('lib.php');
require_once($CFG->libdir.'/adminlib.php');

admin_externalpage_setup('expnotassettings');

require_login();

$title = get_string('exporttitle', 'local_expnotas');
$heading = get_string('exportheading', 'local_expnotas');

$PAGE->set_url('/local/expnotas/index.php');
$PAGE->set_context(context_system::instance());
$PAGE->set_title($title);

$form = new \local_expnotas\form\export_form();

// Obtener los datos del formulario una sola vez y usarlos consistentemente
$data = $form->get_data();

if ($form->is_cancelled()) {
    redirect(new moodle_url('/index.php'));
} else if ($fromform = $form->get_data()) {
    $destination = [];
    $destination[] = optional_param('destination', 0, PARAM_ALPHANUM);
    if (local_expnotas_export_all_grades($destination)) {
        echo $OUTPUT->notification(get_string('exportsuccess', 'local_expnotas'), 'notifysuccess');
    } else {
        echo $OUTPUT->notification(get_string('exporterror', 'local_expnotas'), 'notifyproblem');
    }
} else {
    // TODO: Exception handling
    // 'No data received from form or form not submitted via POST.';
}

$output = $PAGE->get_renderer('local_expnotas');

echo $output->header();
echo $output->heading($heading);

$form->display();

echo $output->footer();
