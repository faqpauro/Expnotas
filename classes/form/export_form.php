<?php

namespace local_expnotas\form;

use moodleform;
use moodle_url;

require_once("$CFG->libdir/formslib.php");
require_once('lib.php');

class export_form extends moodleform {
    protected function definition() {
        $mform = $this->_form;
        $mform->setAttributes([
            'action' => new moodle_url('/local/expnotas/index.php'),
            'method' => 'post',
            'class' => 'my-export-form',
        ]);

        $mform->addElement('select', 'destination',
        get_string(
            'exportdestination', 'local_expnotas'),
            \local_expnotas\destination::get_form_destinations()
        );
        $mform->addRule('destination', get_string('required'), 'required', null, 'client');

        // Botón de envío
        $mform->addElement('submit', 'export', get_string('export', 'local_expnotas'));
    }
}
