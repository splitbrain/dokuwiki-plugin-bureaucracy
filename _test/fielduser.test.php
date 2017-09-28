<?php
namespace dokuwiki\plugin\bureaucracy\test;

/**
 * @group plugin_bureaucracy
 * @group plugins
 */
class syntax_plugin_bureaucracy_fielduser_test extends BureaucracyTest {

    /**
     * Create some users
     */
    public function setUp() {
        parent::setUp();

        /** @var DokuWiki_Auth_Plugin $auth */
        global $auth;

        $auth->createUser("user1", "54321", "a User", "you@example.com");
        $auth->createUser("user2", "543210", "You", "he@example.com");
        $auth->createUser("mwuser", "12345", "Wiki User", "me@example.com", array('group1', 'gropu2'));
    }

    /**
     * Simulate a bureaucracy form send with the 'user' field
     *
     * @param string       $template_syntax     template to be used as form action
     * @param string|array $users               value of 'users' field or array('label' => 'own label', 'value' => 'value')
     * @param bool         $assertValid         should we assert the form validity
     * @param array        &$validation_errors  labels of invalid form fields
     *
     * @return string content of newly created page
     * @throws \Exception
     */
    protected function send_form($template_syntax, $user, $assertValid=true, &$validation_errors=array()) {
        $label = 'user';
        if (is_array($user)) {
            if (!isset($user['value'])) {
                throw new \Exception('$user should be string or array("label" => label, "value" => value');
            }
            if (isset($user['label'])) $label = $user['label'];
            $user = $user['value'];
        }
        $result = parent::send_form_action_template('user "'.$label.'"', $template_syntax, $validation_errors, $user);
        if ($assertValid) {
            $this->assertEmpty($validation_errors, 'validation error: fields not valid: '.implode(', ', $validation_errors));
        }

        return $result;
    }

    public function test_regex_label() {
        $label = '*]]'; //somthing to break a regex when not properly quoted
        $user = array('label' => $label, 'value' => 'mwuser');
        $result = $this->send_form("user:@@$label@@", $user);
        $this->assertEquals("user:$user[value]", $result);
    }

    public function test_action_template_default_substitution() {
        $user = 'mwuser';
        $result = $this->send_form('user:@@user@@', $user);
        $this->assertEquals("user:$user", $result);
    }

    public function test_action_template_empty_substitution() {
        $template_syntax = 'user:@@user@@';
        $validation_errors = array();
        $result = $this->send_form($template_syntax, '', false, $validation_errors);
        $this->assertEquals(array('user'), $validation_errors);
    }

    public function test_action_template_name_substitution() {
        /** @var DokuWiki_Auth_Plugin $auth */
        global $auth;
        $user = 'mwuser';
        $name = $auth->getUserData($user)['name'];

        $result = $this->send_form('user:@@user.name@@', $user);
        $this->assertEquals("user:$name", $result);
    }

    public function test_action_template_email_substitution() {
        /** @var DokuWiki_Auth_Plugin $auth */
        global $auth;
        $user = 'mwuser';
        $mail = $auth->getUserData($user)['mail'];

        $result = $this->send_form('user:@@user.mail@@', $user);
        $this->assertEquals("user:$mail", $result);
    }

    public function test_action_template_group_substitution_default_delimiter() {
        /** @var DokuWiki_Auth_Plugin $auth */
        global $auth;
        $user = 'mwuser';
        $delimiter = ', ';
        $grps = implode($delimiter, $auth->getUserData($user)['grps']);

        $result = $this->send_form('user:@@user.grps@@', $user);
        $this->assertEquals("user:$grps", $result);
    }

    //user.grps(, )
    public function test_action_template_group_substitution_custom_delimiter() {
        /** @var DokuWiki_Auth_Plugin $auth */
        global $auth;
        $user = 'mwuser';
        $delimiter = ';';
        $grps = implode($delimiter, $auth->getUserData($user)['grps']);

        $result = $this->send_form("user:@@user.grps($delimiter)@@", $user);
        $this->assertEquals("user:$grps", $result);
    }

    //user.grps(, )
    public function test_action_template_group_substitution_custom_delimiter_with_brackets() {
        /** @var DokuWiki_Auth_Plugin $auth */
        global $auth;
        $user = 'mwuser';
        $delimiter = ')';
        $grps = implode($delimiter, $auth->getUserData($user)['grps']);

        $result = $this->send_form("user:@@user.grps($delimiter)@@", $user);
        $this->assertEquals("user:$grps", $result);
    }

    public function test_action_template_unknown_user_substitution() {
        $template_syntax = 'user:@@user@@';
        $user = 'no_such_user';

        $validation_errors = array();
        $result = $this->send_form('user:@@user@@', $user, false, $validation_errors);
        $this->assertEquals(array('user'), $validation_errors);
    }

    public function test_action_template_unknown_attribute_substitution() {
        /** @var DokuWiki_Auth_Plugin $auth */
        global $auth;
        $template_syntax = 'user:@@user.no_sutch_attribute@@';
        $user = 'mwuser';

        $result = $this->send_form($template_syntax, $user);
        $this->assertEquals($template_syntax, $result);
    }

    public function test_action_template_hash_subsitution() {
        /** @var DokuWiki_Auth_Plugin $auth */
        global $auth;
        $template_syntax = 'user:##user##';
        $user = 'mwuser';

        $result = $this->send_form($template_syntax, $user);
        $this->assertEquals('user:mwuser', $result);
    }

    public function test_action_template_hash_subsitution_with_attribute() {
        /** @var DokuWiki_Auth_Plugin $auth */
        global $auth;
        $user = 'mwuser';
        $mail = $auth->getUserData($user)['mail'];

        $result = $this->send_form('user:##user.mail##', $user);
        $this->assertEquals("user:$mail", $result);
    }


    public function test_action_template_hash_at_sign_mismatch() {
        /** @var DokuWiki_Auth_Plugin $auth */
        global $auth;
        $template_syntax = 'user:##user@@';
        $user = 'mwuser';

        $result = $this->send_form($template_syntax, $user);
        $this->assertEquals($template_syntax, $result);
    }

    public function test_action_template_multiple_replacements() {
        /** @var DokuWiki_Auth_Plugin $auth */
        global $auth;
        $user = 'mwuser';
        $mail = $auth->getUserData($user)['mail'];

        $delimiter = "\n";
        $grps = implode($delimiter, $auth->getUserData($user)['grps']);

        $result = $this->send_form("user:@@user@@\n\nmail:@@user.mail@@\n\ngrps:@@user.grps($delimiter)@@", $user);
        $this->assertEquals("user:$user\n\nmail:$mail\n\ngrps:$grps", $result);
    }

    public function test_action_template_grps_twice() {
        /** @var DokuWiki_Auth_Plugin $auth */
        global $auth;
        $user = 'mwuser';

        $delimiter1 = "\n";
        $delimiter2 = "()";
        $grps1 = implode($delimiter1, $auth->getUserData($user)['grps']);
        $grps2 = implode($delimiter2, $auth->getUserData($user)['grps']);

        $result = $this->send_form("grps1:@@user.grps($delimiter1)@@\n\ngrps2:@@user.grps($delimiter2)@@", $user);
        $this->assertEquals("grps1:$grps1\n\ngrps2:$grps2", $result);
    }

    public function test_action_template_grps_special_glue() {
        /** @var DokuWiki_Auth_Plugin $auth */
        global $auth;
        $user = 'mwuser';

        $delimiter = "end)";
        $grps = implode($delimiter, $auth->getUserData($user)['grps']);

        $result = $this->send_form("grps:@@user.grps($delimiter)@@", $user);
        $this->assertEquals("grps:$grps", $result);
    }

}
