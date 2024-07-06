<?php

namespace local_expnotas\form;

use moodleform;
use moodle_url;

require_once("$CFG->libdir/formslib.php");
require_once('lib.php');

class query_form extends moodleform {
    protected function definition() {
        $mform = $this->_form;
        $mform->setAttributes([
            'action' => new moodle_url('/local/expnotas/query_export.php'),
            'method' => 'post',
            'class' => 'query-export-form',
        ]);

        $mform->addElement('textarea', 'sqlquery',
        get_string('exportdestination', 'local_expnotas'), '', 'wrap="virtual" cols="80" rows="10"');
        $mform->addRule('sqlquery', get_string('required'), 'required', null, 'client');

        // Botón de envío
        $mform->addElement('submit', 'export', get_string('export', 'local_expnotas'));
    }
}
