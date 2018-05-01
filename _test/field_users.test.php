<?php

namespace dokuwiki\plugin\bureaucracy\test;

/**
 * @group plugin_bureaucracy
 * @group plugins
 */
class syntax_plugin_bureaucracy_fieldusers_test extends BureaucracyTest
{

    /**
     * Create some users
     */
    public function setUp()
    {
        parent::setUp();

        /** @var \DokuWiki_Auth_Plugin $auth */
        global $auth;

        $auth->createUser('user1', '54321', 'user1Name', 'user1@example.com');
        $auth->createUser('user2', '543210', 'user2Name', 'user2@example.com');
        $auth->createUser('mwuser', '12345', 'Wiki User', 'me@example.com', array('group1', 'group2'));
    }

    public function dataProvider()
    {
        return [
            [
                'users:@@users@@',
                'user1, user2',
                'users:user1, user2',
                [],
                'default substitution',
            ],
            [
                'users:@@users@@',
                '',
                'users:',
                ['users'],
                'error for empty substitution',
            ],
            [
                'users:@@users(;)@@',
                'user1, user2',
                'users:user1;user2',
                [],
                'custom delimiter',
            ],
            [
                "users:@@users(\n)@@",
                'user1, user2',
                "users:user1\nuser2",
                [],
                'newline delimiter',
            ],
            [
                'users:@@users()@@',
                'user1, user2',
                'users:user1user2',
                [],
                'empty delimiter',
            ],
            [
                'users:@@users.name@@',
                'user1, user2',
                'users:user1Name, user2Name',
                [],
                'names substitution default delitmiter',
            ],
            [
                'users:@@users(;).name@@',
                'user1, user2',
                'users:user1Name;user2Name',
                [],
                'names substitution custom delitmiter',
            ],
            [
                'users:@@users.mail@@',
                'user1, user2',
                'users:user1@example.com, user2@example.com',
                [],
                'mail substitution default delitmiter',
            ],
            [
                "mails:@@users.mail@@\n\nnames:@@users(\n).name@@",
                'user1, user2',
                "mails:user1@example.com, user2@example.com\n\nnames:user1Name\nuser2Name",
                [],
                'multiple replacements',
            ],
            [
                'users:@@users@@',
                'not_existing1, not_existing2',
                'users:not_existing1, not_existing2',
                ['users'],
                'unknown users should cause errors',
            ],
            [
                'users:@@users.unknown_attribute@@',
                'user1, user2',
                'users:@@users.unknown_attribute@@',
                [],
                'non existant attribute is not replaced',
            ],
            [
                'users:@@*]]@@',  // the label must be something to break a regex when not properly quoted
                ['label' => '*]]', 'value' => 'user1, user2'],
                'users:user1, user2',
                [],
                'ensure label desn\'t break regex',
            ],
            [
                'users:@@tHis Is UsEr@@',
                ['label' => 'tHis Is UsEr', 'value' => 'user1, user2'],
                'users:user1, user2',
                [],
                'label with spaces and mixed case',
            ],
        ];
    }

    /**
     * @dataProvider dataProvider
     *
     * @param string       $templateSyntax
     * @param string|array $users value of 'users' field or array('label' => 'own label', 'value' => 'value')
     * @param string       $expectedWikiText
     * @param string       $expectedValidationErrors
     * @param string       $msg
     *
     * @throws \InvalidArgumentException
     */
    public function test_field_users(
        $templateSyntax,
        $users,
        $expectedWikiText,
        $expectedValidationErrors,
        $msg
    ) {
        $actualValidationErrors = [];

        $label = 'users';
        if (is_array($users)) {
            if (!isset($users['value'])) {
                throw new \InvalidArgumentException('$users should be string or array("label" => label, "value" => value');
            }
            if (isset($users['label'])) {
                $label = $users['label'];
            }
            $users = $users['value'];
        }
        $actualWikiText = parent::send_form_action_template(
            'users "' . $label . '"',
            $templateSyntax,
            $actualValidationErrors,
            $users
        );

        $this->assertEquals($expectedWikiText, $actualWikiText, $msg);
        $this->assertEquals($expectedValidationErrors, $actualValidationErrors, $msg);
    }
}
