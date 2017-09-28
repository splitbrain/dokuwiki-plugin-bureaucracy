<?php
namespace dokuwiki\plugin\bureaucracy\test;

/**
 * @group plugin_bureaucracy
 * @group plugins
 */
class syntax_plugin_bureaucracy_fieldusers_test extends BureaucracyTest {

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
     * Simulate a bureaucracy form send with the 'users' field
     *
     * @param string       $template_syntax     template to be used as form action
     * @param string|array $users               value of 'users' field or array('label' => 'own label', 'value' => 'value')
     * @param bool         $assertValid         should we assert the form validity
     * @param array        &$validation_errors  labels of invalid form fields
     *
     * @return string content of newly created page
     * @throws \Exception
     */
    protected function send_form($template_syntax, $users, $assertValid=true, &$validation_errors=array()) {
        $label = 'users';
        if (is_array($users)) {
            if (!isset($users['value'])) {
                throw new \Exception('$users should be string or array("label" => label, "value" => value');
            }
            if (isset($users['label'])) $label = $users['label'];
            $users = $users['value'];
        }
        $result = parent::send_form_action_template('users "'.$label.'"', $template_syntax, $validation_errors, $users);
        if ($assertValid) {
            $this->assertEmpty($validation_errors, 'validation error: fields not valid: '.implode(', ', $validation_errors));
        }

        return $result;
    }

    public function test_regex_label() {
        $label = '*]]'; //somthing to break a regex when not properly quoted
        $user = array('label' => $label, 'value' => 'user1, user2');
        $result = $this->send_form("users:@@$label@@", $user);
        $this->assertEquals("users:$user[value]", $result);
    }

    public function test_mixed_case_and_spaces_label_names_substitution() {
        /** @var DokuWiki_Auth_Plugin $auth */
        global $auth;

        $label = 'tHis Is UsEr';
        $users = array('user1', 'user2');
        $names = array_map(function($user) use ($auth) {
            return $auth->getUserData($user)['name'];
        }, $users);

        $users_i = implode(', ', $users);
        $names_i = implode(', ', $names);

        $user = array('label' => $label, 'value' => $users_i);
        $result = $this->send_form("user:@@$label.name@@", $user);
        $this->assertEquals("user:$names_i", $result);
    }


    public function test_action_template_default_substitution() {
        $users = 'user1, user2';
        $result = $this->send_form('users:@@users@@', $users);
        $this->assertEquals("users:$users", $result);
    }

    public function test_action_template_empty_substitution() {
        $template_syntax = 'users:@@users@@';
        $validation_errors = array();
        $result = $this->send_form($template_syntax, '', false, $validation_errors);
        $this->assertEquals(array('users'), $validation_errors);
    }

    public function test_action_template_substitution_custom_delimiter() {
        $users = array('user1', 'user2');
        $delimiter = ';';

        $post_users = implode(', ', $users);
        $reuslt_users = implode($delimiter, $users);

        $result = $this->send_form("users:@@users($delimiter)@@", $post_users);
        $this->assertEquals("users:$reuslt_users", $result);
    }

    public function test_action_template_substitution_new_line_delimiter() {
        $users = array('user1', 'user2');
        $delimiter = "\n";

        $post_users = implode(', ', $users);
        $reuslt_users = implode($delimiter, $users);

        $result = $this->send_form("users:@@users($delimiter)@@", $post_users);
        $this->assertEquals("users:$reuslt_users", $result);
    }

    public function test_action_template_substitution_empty_delimiter() {
        $users = array('user1', 'user2');
        $delimiter = '';

        $post_users = implode(', ', $users);
        $reuslt_users = implode($delimiter, $users);

        $result = $this->send_form("users:@@users($delimiter)@@", $post_users);
        $this->assertEquals("users:$reuslt_users", $result);
    }

    public function test_action_template_names_substitution_default_delitmiter() {
        /** @var DokuWiki_Auth_Plugin $auth */
        global $auth;
        $users = array('user1', 'user2');
        $names = array_map(function($user) use ($auth) {
            return $auth->getUserData($user)['name'];
        }, $users);

        $delimiter = ', ';
        $users_i = implode(', ', $users);
        $names_i = implode($delimiter, $names);

        $result = $this->send_form('users:@@users.name@@', $users_i);
        $this->assertEquals("users:$names_i", $result);
    }

    public function test_action_template_names_substitution_custom_delitmiter() {
        /** @var DokuWiki_Auth_Plugin $auth */
        global $auth;
        $users = array('user1', 'user2');
        $names = array_map(function($user) use ($auth) {
            return $auth->getUserData($user)['name'];
        }, $users);

        $delimiter = ';';
        $users_i = implode(', ', $users);
        $names_i = implode($delimiter, $names);

        $result = $this->send_form("users:@@users($delimiter).name@@", $users_i);
        $this->assertEquals("users:$names_i", $result);
    }

    public function test_action_template_mails_substitution_default_delitmiter() {
        /** @var DokuWiki_Auth_Plugin $auth */
        global $auth;
        $users = array('user1', 'user2');
        $mails = array_map(function($user) use ($auth) {
            return $auth->getUserData($user)['mail'];
        }, $users);

        $delimiter = ', ';
        $users_i = implode(', ', $users);
        $mails_i = implode($delimiter, $mails);

        $result = $this->send_form('users:@@users.mail@@', $users_i);
        $this->assertEquals("users:$mails_i", $result);
    }

    public function test_action_template_multiple_replacements() {
        /** @var DokuWiki_Auth_Plugin $auth */
        global $auth;
        $users = array('user1', 'user2');
        $names = array_map(function($user) use ($auth) {
            return $auth->getUserData($user)['name'];
        }, $users);
        $mails = array_map(function($user) use ($auth) {
            return $auth->getUserData($user)['mail'];
        }, $users);

        $names_delimiter = "\n";
        $mails_delimiter = ', ';
        $users_i = implode(', ', $users);
        $names_i = implode($names_delimiter, $names);
        $mails_i = implode($mails_delimiter, $mails);

        $result = $this->send_form("mails:@@users.mail@@\n\nnames:@@users($names_delimiter).name@@", $users_i);
        $this->assertEquals("mails:$mails_i\n\nnames:$names_i", $result);
    }

}
