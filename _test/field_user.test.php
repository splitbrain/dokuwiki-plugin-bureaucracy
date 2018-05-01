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

        /** @var \DokuWiki_Auth_Plugin $auth */
        global $auth;

        $auth->createUser("user1", "54321", "user1Name", "user1@example.com");
        $auth->createUser("user2", "543210", "user2Name", "user2@example.com");
        $auth->createUser("mwuser", "12345", "Wiki User", "wikiuser@example.com", array('group1', 'group2'));
    }

    public function dataProvider()
    {
        return [
            [
                'user:@@user@@',
                'mwuser',
                'user:mwuser',
                [],
                'default substitution',
            ],
            [
                'user:@@user@@',
                '',
                'user:',
                ['user'],
                'error for empty substitution',
            ],
            [
                'user:@@user.name@@',
                'mwuser',
                'user:Wiki User',
                [],
                'name substitution',
            ],
            [
                'user:@@user.mail@@',
                'mwuser',
                'user:wikiuser@example.com',
                [],
                'mail substitution',
            ],
            [
                'user:@@user.grps@@',
                'mwuser',
                'user:group1, group2',
                [],
                'groups substitution',
            ],
            [
                'user:@@user.grps(;)@@',
                'mwuser',
                'user:group1;group2',
                [],
                'groups substitution custom delimiter',
            ],
            [
                'user:@@user.grps())@@',
                'mwuser',
                'user:group1)group2',
                [],
                'groups substitution custom delimiter with brackets',
            ],
            [
                'user:@@user.no_sutch_attribute@@',
                'mwuser',
                'user:@@user.no_sutch_attribute@@',
                [],
                'template unknown attribute substitution',
            ],
            [
                'user:##user##',
                'mwuser',
                'user:mwuser',
                [],
                'hash substitution',
            ],
            [
                'user:##user.mail##',
                'mwuser',
                'user:wikiuser@example.com',
                [],
                'hash substitution with attribute',
            ],
            [
                'user:##user@@',
                'mwuser',
                'user:##user@@',
                [],
                'hash substitution sign mismatch',
            ],
            [
                "user:@@user@@\n\nmail:@@user.mail@@\n\ngrps:@@user.grps(\n)@@",
                'mwuser',
                "user:mwuser\n\nmail:wikiuser@example.com\n\ngrps:group1\ngroup2",
                [],
                'multiple replacements',
            ],
            [
                "grps1:@@user.grps(\n)@@\n\ngrps2:@@user.grps(())@@",
                'mwuser',
                "grps1:group1\ngroup2\n\ngrps2:group1()group2",
                [],
                'groups twice',
            ],
            [
                'grps:@@user.grps(end))@@',
                'mwuser',
                'grps:group1end)group2',
                [],
                'groups special glue',
            ],
            [
                'grps:@@user.grps()@@',
                'mwuser',
                'grps:group1group2',
                [],
                'groups with empty delimiter',
            ],
            [
                'user:@@user@@',
                'non_existant_user',
                'user:non_existant_user',
                ['user'],
                'error for non existant user',
            ],
            [
                'user:@@user.name@@',
                'non_existant_user',
                'user:@@user.name@@',
                ['user'],
                'error for non existant user with attribute',
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
    public function test_field_user(
        $templateSyntax,
        $users,
        $expectedWikiText,
        $expectedValidationErrors,
        $msg
    ) {
        $actualValidationErrors = [];

        $label = 'user';
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
            'user "' . $label . '"',
            $templateSyntax,
            $actualValidationErrors,
            $users
        );

        $this->assertEquals($expectedWikiText, $actualWikiText, $msg);
        $this->assertEquals($expectedValidationErrors, $actualValidationErrors, $msg);
    }
}
