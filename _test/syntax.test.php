<?php

class syntax_plugin_bureaucracy_test extends DokuWikiTest {

    protected $pluginsEnabled = array('bureaucracy');

    public function testHTML() {
        $input = file_get_contents(dirname(__FILE__) . '/input.txt');
        $xhtml = p_render('xhtml', p_get_instructions($input), $info);

        $doc = phpQuery::newDocument($xhtml);

        $this->assertEquals(1, pq('form.bureaucracy__plugin', $doc)->length);
        $this->assertEquals(5, pq('form.bureaucracy__plugin fieldset', $doc)->length);

        // standard input types
        $this->checkField($doc, 'Employee Name *', 'input[type=text][value=Your Name].edit');
        $this->checkField($doc, 'Your Age *', 'input[type=text].edit');
        $this->checkField($doc, 'Your E-Mail Address *', 'input[type=text].edit');
        $this->checkField($doc, 'Occupation (optional)', 'input[type=text].edit');

        // select field
        $select = $this->checkField($doc, 'Please select an option', 'select');
        $this->assertEquals(3, pq('option', $select)->length);
        $this->assertEquals(1, pq('option:selected', $select)->length);
        $this->assertEquals('Peaches', pq('option:selected', $select)->val());

        // static text
        $this->assertEquals(1, pq('p:contains(Some static text)', $doc)->length);

        // checkbox
        $cb = $this->checkField($doc, 'Read the agreement?', 'input[type=checkbox][value=1]');
        $this->assertEquals('1', pq('input[type=hidden][value=0]', $cb->parent())->length);

        // text area
        $this->checkField($doc, 'Tell me about your self *', 'textarea.edit');

        // submit button
        $this->assertEquals(1, pq('input[type=submit][value=Submit Query]')->length);
    }

    private function checkField($doc, $name, $check) {

        $field = pq('form.bureaucracy__plugin label span:contains(' . $name . ')', $doc);
        $this->assertEquals(1, $field->length, "find span of $name");

        $label = $field->parent();
        $this->assertTrue($label->is('label'), "find label of $name");

        $input = pq($check, $label);
        $this->assertEquals(1, $input->length, "find check of $name");

        return $input;
    }

}