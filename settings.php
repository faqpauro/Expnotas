<?php
/*  DOCUMENTATION
    .............

    $hassiteconfig which can be used as a quick way to check for the moodle/site:config permission. This variable is set by
    the top-level admin tree population scripts.

    $ADMIN->add('root', new admin_category();
    Add admin settings for the plugin with a root admin category as Slack variable.

    $ADMIN->add('slack', new admin_externalpage();
    Add new external pages for your Slack plugin.
*/

defined('MOODLE_INTERNAL') || die;

if ($hassiteconfig) {
    $ADMIN->add('root', new admin_category('expnotas', get_string('pluginfullname', 'local_expnotas')));
    $ADMIN->add('expnotas', new admin_externalpage('expnotassettings', get_string('exportparams', 'local_expnotas'), new moodle_url('/local/expnotas/index.php')));
    $ADMIN->add('expnotas', new admin_externalpage('exportquery', get_string('exportquery', 'local_expnotas'), new moodle_url('/local/expnotas/query_export.php')));


    $mainsettingspage = new admin_settingpage('managelocalexpnotas', get_string('exportsettings', 'local_expnotas'));
    if ($ADMIN->fulltree) {
        require_once("$CFG->dirroot/local/expnotas/lib.php");
        if ($destinationlist = local_expnotas\destination::get_destinations()) {
            $mainsettingspage->add(new admin_setting_heading('expnotas/scheduling', get_string('scheduledexportdestination', 'local_expnotas'), get_string('scheduledexportdestination', 'local_expnotas')));
            $mainsettingspage->add(new admin_setting_configcheckbox('expnotas/enablescheduling', get_string('enablescheduling', 'local_expnotas'), get_string('enablescheduling', 'local_expnotas'), 0));
            $mainsettingspage->add(new admin_setting_configtext('expnotas/customfields', 'Specific custom fields', 'separated by commas', ''));
            $frequency = new admin_setting_configduration('expnotas/exportfrequency', get_string('exportfrequency', 'local_expnotas'), get_string('exportfrequency', 'local_expnotas'), MINSECS, MINSECS);
            $frequency->set_min_duration(MINSECS);
            $destinations = new admin_setting_configmulticheckbox('expnotas/schedulingdestinations', get_string('scheduledexportdestination', 'local_expnotas'), get_string('scheduledexportdestination', 'local_expnotas'), [], $destinationlist);
            $schedulingenabled = get_config('expnotas', 'enablescheduling');
            if($schedulingenabled) {
                $mainsettingspage->add($frequency);
                $mainsettingspage->add($destinations);
            }
        }
    }

    $ADMIN->add('expnotas', $mainsettingspage);

}
