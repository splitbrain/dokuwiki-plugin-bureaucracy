<?php
/**
 * Class syntax_plugin_bureaucracy_field_fieldset
 *
 * Creates a new set of fields, which optional can be shown/hidden depending on the value of another field above it.
 */
class syntax_plugin_bureaucracy_field_fieldset extends syntax_plugin_bureaucracy_field {
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
    public function __construct($args) {
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
     */
    function renderfield($params, Doku_Form $form) {
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
     * @param array $params
     *  for a fieldset $params is an array of the entries:
     *    [0] null  field value of fieldset always empty
     *    [1] int   $my_id index number of field
     *    [2] array $fields the form fields
     * @return bool true
     */
    public function handle_post(&$params) {
        $my_id = $params[1];
        $fields = &$params[2];

        if(!isset($this->depends_on)) {
            return true;
        }
        $hidden = false;
        for ($n = 0 ; $n < $my_id; ++$n) {
            $field = $fields[$n];
            /** @var syntax_plugin_bureaucracy_field $field  */
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
            for ($n = $my_id + 1 ; $n < count($fields) ; ++$n) {
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
