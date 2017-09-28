<?php
namespace dokuwiki\plugin\bureaucracy\test;


class BureaucracyTest extends \DokuWikiTest {
    protected $pluginsEnabled = array('bureaucracy');

    /**
     * Simulate sending of bureaucracy form
     *
     * @param string $form_syntax       syntax to build a bureaucracy form
     * @param string $template_syntax   syntax used as a page template for the "action template"
     * @param array& $validation_errors field labels that were invalid
     * @param string|array ...$values   values passed to form handler
     * @return string content of newly created page
     */
    protected function send_form_action_template($form_syntax, $template_syntax, &$validation_errors, ...$values) {
        if (is_array($values[0])) {
            $values = $values[0];
        }
        $id = uniqid('page');
        $template_id = uniqid('template');

        //create full form syntax
        if (is_array($form_syntax)) $form_syntax = implode("\n", $form_syntax);
        $form_syntax = "<form>\naction template $template_id $id\n$form_syntax\n</form>";

        saveWikiText($template_id, $template_syntax, 'summary');

        $syntax_plugin = plugin_load('syntax', 'bureaucracy');
        $data = $syntax_plugin->handle($form_syntax, 0, 0, new \Doku_Handler());

        $actionData = $data['actions'][0];
        $action = plugin_load('helper', $actionData['actionname']);
        //this is the only form
        $form_id = 0;

        for ($i = 0; $i < count($data['fields']); ++$i) {
            //set null for not existing values
            if (!isset($values[$i])) $values[$i] = null;

            $field = $data['fields'][$i];
            $isValid = $field->handle_post($values[$i], $data['fields'], $i, $form_id);
            if (!$isValid) {
                $validation_errors[] = $field->getParam('label');
            }
        }

        $action->run(
            $data['fields'],
            $data['thanks'],
            $actionData['argv']
        );

        return rawWiki($id);
    }
}
