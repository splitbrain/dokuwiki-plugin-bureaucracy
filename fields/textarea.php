<?php
/**
 * Class syntax_plugin_bureaucracy_field_textarea
 *
 * Creates a multi-line input field
 */
class syntax_plugin_bureaucracy_field_textarea extends syntax_plugin_bureaucracy_field {
    /**
     * Arguments:
     *  - cmd
     *  - label
     *  - x123 (optional) as number of lines
     */

    protected $tpl = '<label class="@@CLASS@@"><span>@@DISPLAY@@</span><textarea name="@@NAME@@" rows="@@ROWS|10@@" cols="10" class="edit">@@VALUE@@</textarea></label>';
}
