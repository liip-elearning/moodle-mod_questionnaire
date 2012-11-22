<?php //$Id$

function xmldb_questionnaire_upgrade($oldversion) {
    global $CFG, $DB;

    $dbman = $DB->get_manager(); /// loads ddl manager and xmldb classes

    $result = true;

    // v2.2.0 release upgrade line
    if ($oldversion < 2010110101) {
        cli_error('Version of questionnaire must be upgraded to latest version of 2.2 before upgrading to 2.3.');
    }

    // v2.3.0 release upgrade line
    if ($oldversion < 2012110702) {
        // Changing precision of field name on table questionnaire_survey to (255).

        // First drop the index.
        $table = new xmldb_table('questionnaire_survey');
        $index = new xmldb_index('name');
        $index->set_attributes(XMLDB_INDEX_NOTUNIQUE, array('name'));
        $dbman->drop_index($table, $index);

        // Launch change of precision for field name.
        $field = new xmldb_field('name', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null, 'id');
        $dbman->change_field_precision($table, $field);

        // Add back in the index.
        $dbman->add_index($table, $index);

        /// skip logic feature JR proof of concept
        /// Define field dependquestion to be added to questionnaire_question table
        $table = new xmldb_table('questionnaire_question');
        $field = new xmldb_field('dependquestion', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', 'deleted');

        /// Conditionally launch add field
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $table = new xmldb_table('questionnaire_question');
        $field = new xmldb_field('dependchoice', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', 'dependquestion');

        /// Conditionally launch add field
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // savepoint reached
        upgrade_mod_savepoint(true, 2012110702,  'questionnaire');
    }
    return $result;
}
