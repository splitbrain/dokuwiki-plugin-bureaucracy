<?php
/**
 * Base class for bureaucracy actions.
 *
 * All bureaucracy actions have to inherit from this class.
 *
 * ATM this class is pretty empty but, in the future it could be used to add
 * helper functions which can be utilized by the different actions.
 *
 * @author Michael Klier <chi@chimeric.de>
 */
class syntax_plugin_bureaucracy_action extends syntax_plugin_bureaucracy {

    /**
     * Handle the user input [required]
     *
     * This function needs to be implemented to accept the user data collected
     * from the form. Data has to be grabbed from $_POST['bureaucracy'] using
     * the indicies in the 'idx' members of the $data items.
     *
     * @param array  $data      - the list of fields in the form
     * @param string $thanks    - the thank you message as defined in the form
     *                            or default one. Might be modified by the action
     *                            before returned
     * @param array  $argv      - additional arguments passed to the action
     * @return mixed            - false on error, $thanks on success
     */
    function run($data, $thanks, $argv){
        msg('ERROR: called action %s did not implement a run() function');
        return false;
    }
}
