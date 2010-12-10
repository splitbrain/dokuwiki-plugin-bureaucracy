<?php
function syntax_plugin_bureaucracy_autoload($name) {
    if (!preg_match('/^syntax_plugin_bureaucracy_(field|action)(?:_(.*))?$/', $name, $matches)) {
        return false;
    }

    if (!isset($matches[2])) {
        // Autoloading the field / action base class
        $matches[2] = $matches[1];
    }

    $filename = DOKU_PLUGIN . "bureaucracy/{$matches[1]}s/{$matches[2]}.php";
    if (!@file_exists($filename)) {
        $plg = new syntax_plugin_bureaucracy;
        msg(sprintf($plg->getLang($matches[1] === 'field' ? 'e_unknowntype' : 'e_noaction'),
                    hsc($matches[2])), -1);
        eval("class $name extends syntax_plugin_bureaucracy_{$matches[1]} { };");
        return true;
    }
    include $filename;
    return true;
}

spl_autoload_register('syntax_plugin_bureaucracy_autoload');

