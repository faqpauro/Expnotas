<?php
defined('MOODLE_INTERNAL') || die();

$capabilities = [

    // Definición de la capacidad para exportar calificaciones
    'local/expnotas:export' => [
        'captype' => 'write',
        'contextlevel' => CONTEXT_SYSTEM, // Puedes cambiar esto según necesidad, p.ej., CONTEXT_COURSE
        'archetypes' => [
            'manager' => CAP_ALLOW,
            'editingteacher' => CAP_ALLOW,
            'teacher' => CAP_ALLOW,
        ],
    ],
    'local/expnotas:viewpages' => [

        'captype' => 'read',
        'contextlevel' => CONTEXT_SYSTEM,
        'legacy' => [
            'guest' => CAP_PREVENT,
            'student' => CAP_ALLOW,
            'teacher' => CAP_ALLOW,
            'editingteacher' => CAP_ALLOW,
            'coursecreator' => CAP_ALLOW,
            'manager' => CAP_ALLOW,
        ],
    ],

    'local/expnotas:managepages' => [

        'captype' => 'read',
        'contextlevel' => CONTEXT_SYSTEM,
        'legacy' => [
            'guest' => CAP_PREVENT,
            'student' => CAP_PREVENT,
            'teacher' => CAP_PREVENT,
            'editingteacher' => CAP_ALLOW,
            'coursecreator' => CAP_ALLOW,
            'manager' => CAP_ALLOW,
        ],
    ],
 ];
