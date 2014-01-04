<?php
/**
 * @group plugin_bureaucracy
 * @group plugins
 */
class syntax_plugin_bureaucracy_action_template_test extends DokuWikiTest {

    protected $pluginsEnabled = array('bureaucracy');

    public function testPrepareLanguagePlaceholderNoTranslate() {
        $action = $this->getTemplateClass();
        $action->prepareLanguagePlaceholder();

        $this->assertEquals('en', $action->values['__lang__']);
        $this->assertEquals('/@LANG@/', $action->patterns['__lang__']);
        $this->assertEquals('', $action->values['__trans__']);
        $this->assertEquals('/@TRANS@/', $action->patterns['__trans__']);
    }

    public function testPrepareLanguagePlaceholderTranslateDefaultNS() {
        global $conf;
        global $ID;

        $conf['plugin']['translation']['translations'] = 'de';
        $ID = 'bla';

        plugin_enable('translation');
        if (null === plugin_load('helper', 'translation')) return;

        $action = $this->getTemplateClass();
        $action->prepareLanguagePlaceholder();

        $this->assertEquals('en', $action->values['__lang__']);
        $this->assertEquals('/@LANG@/', $action->patterns['__lang__']);
        $this->assertEquals('', $action->values['__trans__']);
        $this->assertEquals('/@TRANS@/', $action->patterns['__trans__']);
    }

    public function testPrepareLanguagePlaceholderTranslateLanguageNS() {
        global $conf;
        global $ID;

        $conf['plugin']['translation']['translations'] = 'de';
        $ID = 'de:bla';

        plugin_enable('translation');
        $translation = plugin_load('helper', 'translation');
        if (null === $translation) return;

        $action = $this->getTemplateClass();
        $action->prepareLanguagePlaceholder();

        $this->assertEquals('en', $action->values['__lang__']);
        $this->assertEquals('/@LANG@/', $action->patterns['__lang__']);
        $this->assertEquals('de', $action->values['__trans__']);
        $this->assertEquals('/@TRANS@/', $action->patterns['__trans__']);
    }

    public function testProcessFields() {
        $data = array();
        $data[] = new syntax_plugin_bureaucracy_field_static(array('text', 'text1'));

        $action = $this->getTemplateClass();
        $action->processFields($data, '_', '');

        $this->assertEquals('/(@@|##)text1(?:\|(.*?))\1/si', $action->patterns['text1']);
        $this->assertEquals('$2', $action->values['text1']);
        $this->assertEmpty($action->templates);
    }

    private function getTemplateClass() {
        $class = new syntax_plugin_bureaucracy_action_template();
        $class->patterns = array();
        $class->values = array();
        $class->templates = array();
        $class->pagename = array();
        return $class;
    }


}
