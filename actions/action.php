<?php
/**
 * Base class for bureaucracy actions.
 *
 * All bureaucracy actions have to inherit from this class.
 *
 * ATM this class is pretty empty but, in the future it could be used to add
 * helper functions which can be utilized by the different actions.
 *
 * @author Michael Klier <chi@chimeric.de>
 */
class syntax_plugin_bureaucracy_action extends syntax_plugin_bureaucracy {

    /**
     * Handle the user input [required]
     *
     * This function needs to be implemented to accept the user data collected
     * from the form. Data has to be grabbed from $_POST['bureaucracy'] using
     * the indicies in the 'idx' members of the $data items.
     *
     * @param array  $fields    - the list of fields in the form
     * @param string $thanks    - the thank you message as defined in the form
     *                            or default one. Might be modified by the action
     *                            before returned
     * @param array  $argv      - additional arguments passed to the action
     * @return mixed            - false on error, $thanks on success
     */
    public function run($fields, $thanks, $argv){
        msg('ERROR: called action %s did not implement a run() function');
        return false;
    }

    /**
     * Adds some language related replacement patterns
     */
    function prepareLanguagePlaceholder() {
        global $ID;
        global $conf;

        $this->patterns['__lang__'] = '/@LANG@/';
        $this->values['__lang__'] = $conf['lang'];

        $this->patterns['__trans__'] = '/@TRANS@/';
        $this->values['__trans__'] = '';

        /** @var helper_plugin_translation $trans */
        $trans = plugin_load('helper', 'translation');
        if (!$trans) return;

        $this->values['__trans__'] = $trans->getLangPart($ID);
        $this->values['__lang__'] = $trans->realLC('');
    }

    /**
     * Adds replacement pattern for fieldlabels (e.g @@Label@@)
     *
     * @param string $label field label
     * @param string $value field value
     */
    function prepareFieldReplacement($label, $value) {
        if(!is_null($label)) {
            $this->patterns[$label] = '/(@@|##)' . preg_quote($label, '/') .
                '(?:\|(.*?))' . (is_null($value) ? '' : '?') .
                '\1/si';
            $this->values[$label] = is_null($value) || $value === false ? '$2' : $value;
        }
    }

    /**
     * Adds <noinclude></noinclude> to replacement patterns
     */
    function prepareNoincludeReplacement() {
        $this->patterns['__noinclude__'] = '/<noinclude>(.*?)<\/noinclude>/is';
        $this->values['__noinclude__'] = '';
    }

    /**
     * Generate field replacements
     *
     * @param helper_plugin_bureaucracy_field[]  $fields  List of field objects
     * @return array
     */
    function prepareFieldReplacements($fields) {
        foreach ($fields as $field) {
            $label = $field->getParam('label');
            $value = $field->getParam('value');

            //field replacements
            $this->prepareFieldReplacement($label, $value);
        }
    }

}
