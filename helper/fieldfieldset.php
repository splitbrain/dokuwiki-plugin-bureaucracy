<?php
/**
 * Class helper_plugin_bureaucracy_fieldfieldset
 *
 * Creates a new set of fields, which optional can be shown/hidden depending on the value of another field above it.
 */
class helper_plugin_bureaucracy_fieldfieldset extends helper_plugin_bureaucracy_field {
    protected $mandatory_args = 1;

    /**
     * Arguments:
     *  - cmd
     *  - label (optional)
     *  - field name where switching depends on (optional)
     *  - match value (optional)
     *
     * @param array $args The tokenized definition, only split at spaces
     */
    public function initialize($args) {
        // get standard arguments
        $this->opt = array('cmd' => array_shift($args));

        if (count($args) > 0) {
            $this->opt['label'] = array_shift($args);
            $this->opt['display'] = $this->opt['label'];
        }

        if (count($args) > 0) {
            $this->depends_on = $args;
        }
    }

    /**
     * Render the top of the fieldset as XHTML
     *
     * @params array     $params Additional HTML specific parameters
     * @params Doku_Form $form   The target Doku_Form object
     * @params int       $formid unique identifier of the form which contains this field
     */
    function renderfield($params, Doku_Form $form, $formid) {
        $form->startFieldset(hsc($this->getParam('display')));
        if (isset($this->depends_on)) {
            $dependencies = array_map('hsc',(array) $this->depends_on);
            if (count($this->depends_on) > 1) {
                $msg = 'Only edit this fieldset if ' .
                       '“<span class="bureaucracy_depends_fname">%s</span>” '.
                       'is set to “<span class="bureaucracy_depends_fvalue">%s</span>”.';
            } else {
                $msg = 'Only edit this fieldset if ' .
                       '“<span class="bureaucracy_depends_fname">%s</span>” is set.';
            }
            $form->addElement('<p class="bureaucracy_depends">' . vsprintf($msg, $dependencies) . '</p>');
        }
    }

    /**
     * Handle a post to the fieldset
     *
     * When fieldset is closed, set containing fields to hidden
     *
     * @param null $value field value of fieldset always empty
     * @param helper_plugin_bureaucracy_field[] $fields (reference) form fields (POST handled upto $this field)
     * @param int    $index  index number of field in form
     * @param int    $formid unique identifier of the form which contains this field
     * @return bool Whether the passed value is valid
     */
    public function handle_post($value, &$fields, $index, $formid) {
        if(!isset($this->depends_on)) {
            return true;
        }

        $hidden = false;
        for ($n = 0 ; $n < $index; ++$n) {
            $field = $fields[$n];
            if ($field->getParam('label') != $this->depends_on[0]) {
                continue;
            }
            $hidden = (count($this->depends_on) > 1) ?
                      ($field->getParam('value') != $this->depends_on[1]) :
                      !($field->isSet_());
            break;
        }
        if ($hidden) {
            $this->hidden = true;
            for ($n = $index + 1 ; $n < count($fields) ; ++$n) {
                $field = $fields[$n];
                if ($field->getFieldType() === 'fieldset') {
                    break;
                }
                $field->hidden = true;
            }
        }
        return true;
    }

    /**
     * Get an arbitrary parameter
     *
     * @param string $name
     * @return mixed|null
     */
    function getParam($name) {
        return ($name === 'value') ? null : parent::getParam($name);
    }
}
