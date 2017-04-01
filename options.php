<?php
require_once('parser.php');

function get_options()
{
    return parser::get_instance()->get_asterisk_options();
}
?>
