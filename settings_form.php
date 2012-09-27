<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
* print the form to add or edit a questionnaire-instance
*
* @author Mike Churchward
* @license http://www.gnu.org/copyleft/gpl.html GNU Public License
* @package questionnaire
*/

require_once ($CFG->dirroot.'/course/moodleform_mod.php');
// JR removed this require_once to solve course forced language pb in settings_form.php
//require_once($CFG->dirroot.'/mod/questionnaire/lib.php');

class questionnaire_settings_form extends moodleform {

    function definition() {
        global $CFG, $COURSE, $questionnaire, $QUESTIONNAIRE_REALMS;

        $mform    =& $this->_form;

        //-------------------------------------------------------------------------------
        $mform->addElement('header', 'contenthdr', get_string('contentoptions', 'questionnaire'));

        $mform->addElement('select', 'realm', get_string('realm', 'questionnaire'), $QUESTIONNAIRE_REALMS);
        $mform->setDefault('realm', $questionnaire->survey->realm);
        $mform->addHelpButton('realm', 'realm', 'questionnaire');

        $mform->addElement('text', 'title', get_string('title', 'questionnaire'), array('size'=>'60'));
        $mform->setDefault('title', $questionnaire->survey->title);
        $mform->setType('title', PARAM_TEXT);
        $mform->addRule('title', null, 'required', null, 'client');
        $mform->addHelpButton('title', 'title', 'questionnaire');

        $mform->addElement('text', 'subtitle', get_string('subtitle', 'questionnaire'), array('size'=>'60'));
        $mform->setDefault('subtitle', $questionnaire->survey->subtitle);
        $mform->setType('subtitle', PARAM_TEXT);
        $mform->addHelpButton('subtitle', 'subtitle', 'questionnaire');

        $editoroptions = array('maxfiles' => EDITOR_UNLIMITED_FILES, 'trusttext'=>true);
        $mform->addElement('editor', 'info', get_string('additionalinfo', 'questionnaire'), null, $editoroptions);
        $mform->setDefault('info', $questionnaire->survey->info);
        $mform->setType('info', PARAM_RAW);
        $mform->addHelpButton('info', 'additionalinfo', 'questionnaire');

        //-------------------------------------------------------------------------------
        $mform->addElement('header', 'submithdr', get_string('submitoptions', 'questionnaire'));

        $mform->addElement('text', 'thanks_page', get_string('url', 'questionnaire'), array('size'=>'60'));
        $mform->setType('thanks_page', PARAM_TEXT);
        $mform->setDefault('thanks_page', $questionnaire->survey->thanks_page);
        $mform->addHelpButton('thanks_page', 'url', 'questionnaire');

        $mform->addElement('static', 'confmes', get_string('confalts', 'questionnaire'));
        $mform->addHelpButton('confmes', 'confpage', 'questionnaire');

        // use Table: mdl_questionnaire_survey field theme for type of data analysis
        // TODO ultimately this field should be renamed to analysis to make things clearer        
        $analysis = array ('none' => get_string('none'),
                'totalscore' => get_string('totalscore', 'questionnaire'),
                'subscores' => get_string('subscores', 'questionnaire'));
        if (!empty($questionnaire->survey->theme)) {
            $selected = $questionnaire->survey->theme;
        } else {
            $selected = get_string('none');
        }
        $mform->addElement('select', 'theme', get_string('analysis', 'questionnaire'), $analysis);
        $mform->setType('theme', PARAM_TEXT);
        $mform->setDefault('theme', $selected);
        $mform->addHelpButton('theme', 'analysis', 'questionnaire');
        
        $mform->addElement('text', 'thank_head', get_string('headingtext', 'questionnaire'), array('size'=>'30'));
        $mform->setType('thank_head', PARAM_TEXT);
        $mform->setDefault('thank_head', $questionnaire->survey->thank_head);

        $mform->addElement('html', '<div class="qoptcontainer">');
        
        $options = array('wrap' => 'virtual', 'class' => 'qopts');
        //$mform->addElement('textarea', 'thank_body', get_string('bodytext', 'questionnaire'), $options);
        $mform->addElement('textarea', 'thank_body', get_string('bodytext', 'questionnaire'), 'wrap="virtual" rows="20" cols="80"');
        
        $mform->setType('thank_body', PARAM_RAW);
        $mform->addElement('html', '</div>');        

        $mform->addElement('text', 'email', get_string('email', 'questionnaire'), array('size'=>'75'));
        $mform->setType('email', PARAM_TEXT);
        $mform->setDefault('email', $questionnaire->survey->email);
        $mform->addHelpButton('email', 'sendemail', 'questionnaire');

        //-------------------------------------------------------------------------------
        // Hidden fields
        $mform->addElement('hidden', 'id', 0);
        $mform->addElement('hidden', 'sid', 0);
        $mform->addElement('hidden', 'name', '');
        $mform->addElement('hidden', 'owner', '');

        //-------------------------------------------------------------------------------
        // buttons
        $mform->addElement('submit', 'submitbutton', get_string('savesettings', 'questionnaire'));
    }

    function validation($data, $files){
        global $CFG, $questionnaire;
        // check validity of data analysis expressions used for $totalscore and $subscores
        $errors = parent::validation($data, '');
        $errors['thank_body'] = '';
        $questions = $questionnaire->questions;
        
        // establish an array of valid question numbers, including sub-questions for rate question type
        $q = array();
        $i=0;
        foreach($questions as $question) {
            $qtype = $question->type_id;
            if ($qtype >= QUESPAGEBREAK) {
                $i++;
            } else {
                $qpos = $question->position - $i;
                // radio button or droplist type
                if ($qtype != 8) {
                    $q[$qpos] = $qpos;
                } else { // rate question type
                    $n = 1;
                    $nbsubquestions = 0;
                    foreach($question->choices as $choice) {
                        if (isset($choice->value) && $choice->value != 'NULL') {
                             $q[$qpos][$n] = $choice->value;
                             $n++;
                        } else {
                            $nbsubquestions++;
    }
                    }
                    $q[$qpos]['nbsubquestions'] = $nbsubquestions;
                }
            }
        }
        // check validity of the various expressions entered in thank body field
        $analysis = explode("\n", $data['thank_body']);
        $analysistype = $data['theme'];         
        switch($analysistype) {
            // we only add up all the choice values to get a general total score for the whole questionnaire
            case('totalscore'):
                array_unshift($analysis, "totalscore dummy placeholder");
                $i = 0;
                foreach ($analysis as $linestring) {
                    // first line is dummy line
                    if ($i === 0) {
                        $i++;
                        continue;
                    }
                    $linenumber = $i; // human-friendly line numbering starting at 1
                    if($i % 3 == 0) { // lines #3,6,9, etc. this is an conditional expression
                        //check we do not have an empty line (i.e. 2 empty lines in succession)
                        if (ord($linestring) == 13) {
                            $errors['thank_body'] .= get_string('analysiserror01','questionnaire', $linenumber).'<br />';
                            break;
                        }
                        // remove ALL blank spaces in expression
                        $linestring = preg_replace('/\s+/', '', $linestring);
                        $linestring = preg_replace('/and/i', '&&', $linestring);
                        // check that linestring matches a condition
                        $pattern = '/^if\(\$totalscore(percent)?\b(==|(>|<)(\=)?)\d{1,3}(&&\$totalscore(percent)?\b(>|<)(\=)?\d{1,3})?\)$/';
                        $condition = preg_match($pattern, $linestring);
                        if (!$condition) {
                            $a = new stdClass();
                            $a->linenumber = $linenumber;
                            $a->linestring = $linestring;
                            $errors['thank_body'] .= get_string('analysiserror07','questionnaire', $a).'<br />';
                            break;
                        }
                    }  elseif (ord($linestring) != 13) {
                        // this is a message
                        // adding slashes just in case the message text contains double quotes
                        $linestring = preg_replace('/"/', '\"', $linestring);
                        //check legal variables                        
                        $result = $this->check_legalvariables ($linestring, 'message', $linenumber, $analysistype);
                        if ($result != '' ) {
                            $errors['thank_body'].= $result;
                            break;
                        }        
                    }
                    $i++;
                }
                break;
            case('subscores'):
                $i = 0;
                foreach ($analysis as $linestring) {
                    if ($linestring == '') {
                        $i++;
                        continue;
                    }
                    $linenumber = $i + 1;
                    $a = new stdClass();
                    $a->linenumber = $linenumber;
                    $a->linestring = $linestring;
                    if($i % 3 == 0) { // lines #0,3,6,9, etc. this is an expression
                        //check we do not have an empty line (i.e. 2 empty lines in succession)
                        if (ord($linestring) == 13) {
                            $errors['thank_body'] .= get_string('analysiserror01','questionnaire', $linenumber).'<br />';
                            break;
                        }
                        // remove ALL blank spaces in expression
                        $linestring = preg_replace('/\s+/', '', $linestring);

                        // check that linestring matches either a calculation or a condition
                        $condition = preg_match('/^if *\( *\$(inverse)?subscore(percent)?\b.*/', $linestring);
                        $calculation = preg_match('/^\$subscore\b *=.*$/', $linestring);

                        if (!$condition && !$calculation) {
                            $errors['thank_body'] .= get_string('analysiserror02','questionnaire', $linenumber).'<br />';
                            break;
                        }
                        if ($calculation) {
                            // check that question numbers are prefixed with a $ sign
                            if (preg_match_all('/[^$](q\d{1,3}(\.\d{1,2})*)/', $linestring, $matches)) {
                                $output = implode(", ", $matches[1]);
                                $a->output = $output;
                                $errors['thank_body'] .= get_string('analysiserror08','questionnaire', $a).'<br />';
                                break;
                            }
                            // check correct calculation expression                           
                            $pattern = '/^\$subscore\=\$q\d{1,3}(\.\d{1,2})?(\+\$q\d{1,3}(\.\d{1,2})?)*$/';
                            if (!preg_match($pattern, $linestring) ) {
                                $errors['thank_body'] .= get_string('analysiserror03','questionnaire', $a).'<br />';
                                break;
                            }
                            // check that questions (and sub-questions) exist in this questionnaire
                            $errmsg = '';
                            $r = preg_match_all("/(subscore ?= ?)(.*)$/", $linestring, $matches);
                            $result = $matches[2][0];
                            $subscore = preg_replace('/(\$q)(\d{1,2})(\+)?/', '$2$3', $result);
                            $subscore = preg_replace("/\+/", ',', $subscore);
                            $subscore = explode(',',$subscore);
                            
                            foreach ($subscore as $score) {
                                $score = explode('.',$score);
                                $i0 = $score[0];
                                if (!array_key_exists($i0, $q)) {
                                    $a->questionnb = $i0;
                                    $a->questionntype = get_string('questionnum','questionnaire');
                                    $errmsg .= get_string('analysiserror04','questionnaire', $a).'---<br />';                                    
                                } else {
                                    $nbsubquestions = $q[$i0]['nbsubquestions'];
                                    if (isset($score[1])) {
                                        $i1 = $score[1];
                                        if ($i1 > $nbsubquestions ) {
                                            $a->questionnb = "$i0.$i1";
                                            $a->questiontype = get_string('subquestionnum','questionnaire');
                                            $errmsg .= get_string('analysiserror04','questionnaire', $a).'<br />';
                                        }
                                    }
                                    else { // subquestion n� not set
                                        if ($nbsubquestions > 1) {
                                            $a = '';
                                            $a->linenumber = $linenumber;
                                            $a->questionnb = "$i0";
                                            $a->nbsubquestions = $nbsubquestions;
                                            $a->questiontype = get_string('subquestionnum','questionnaire');
                                            $a->subquestions = get_string('subquestions','questionnaire');
                                            $errmsg .= get_string('analysiserror06','questionnaire', $a).'<br />';
                                        }
                                    }
                                } 
                            }
                            if ($errmsg != '') {
                                $errors['thank_body'] .= $errmsg;
                                break;
                            }
                        }
                        if ($condition) {
                            $linestring = preg_replace('/and/i', '&&', $linestring);
                            $result = $this->check_legalvariables ($linestring, 'expression', $linenumber,  $analysistype);
                            if ($result != '' ) {
                                $errors['thank_body'].= $result;
                                break;
                            }
                            $pattern = '/^if\(\$(inverse)?subscore(percent)?\b(==|(>|<)(\=)?)\d{1,3}(&&\$(inverse)?subscore(percent)?\b(>|<)(\=)?\d{1,3})?\)$/';
                            if (!preg_match($pattern, $linestring) ) {
                                $errmsg .= get_string('analysiserror09','questionnaire', $a).'<br />';
                                $errors['thank_body'] .= $errmsg;
                                break;
                            }
                        }                      
                    } elseif (ord($linestring) != 13) {
                        // this is a message
                        // adding slashes just in case the message text contains double quotes
                        $linestring = preg_replace('/"/', '\"', $linestring);
                        //check legal variables                        
                        $result = $this->check_legalvariables ($linestring, 'message', $linenumber,  $analysistype);
                        if ($result != '' ) {
                            $errors['thank_body'].= $result;
                            break;
                        }
                    }
                    $i++;
                }    
        }
        if ($errors['thank_body'] != '') {
            return $errors;
        }
    }

    function check_legalvariables ($linestring, $linetype, $linenumber, $analysistype) {
        $errmsg = '';
        switch ($analysistype) {
            case 'totalscore':
                $legalvariables = array('$totalscore', '$totalscorepercent', '$inversetotalscorepercent', '$maxtotalscore');
                break;
            case 'subscores':
                $legalvariables = array('$subscore', '$subscorepercent', '$inversesubscorepercent', '$maxsubscore');
        }
        switch ($linetype) {
            case 'expression':
                $pattern = '/[^a-z](\$.*?)[^a-z]/';
            case 'message':
                $pattern = '/(\$\b\w*\b)/';
        }
        $result = preg_match_all($pattern, $linestring, $matches);
        if ($result) {
            $varmatches = array();
            foreach($matches[1] as $match) {
                if ($match != $legalvariables[0] && $match != $legalvariables[1] && $match != $legalvariables[2] && $match != $legalvariables[3]) {
                    $varmatches[] = $match;
                }
            }
            if (!empty($varmatches)) {
                $output = implode(", ", $varmatches);
                $a = new stdClass();
                $a->linenumber = $linenumber;
                $a->linestring = $linestring;
                $a->output = $output;
                $errmsg = get_string('analysiserror05','questionnaire', $a).'<br />';
            }
            return $errmsg;
        }
    }    
}
?>