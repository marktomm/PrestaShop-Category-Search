<?php

require_once(dirname(__FILE__).'/settings.inc.php');

class Logger
{
    const NAME = 'blocksearchcategories';
    
    public static function log()
    {
        if(_PM_CAT_LOG) {
            $msg = "\r\n". date('Y.m.d H:i:s') .' - ';

            $nr = 0;
            // Add all input arguments to message.
            foreach(func_get_args() as $arg) {
                if($nr++ > 0) {
                    $msg .= ', ';
                }
                if(is_numeric($arg) || is_string($arg)) {
                    $msg .= $arg;
                } elseif(is_bool($arg)) {
                    $msg .= ($arg === true) ? 'TRUE' : 'FALSE';
                } else {
                    $msg .= print_r($arg, true);
                }
            }

            // Write to file
            file_put_contents(_PS_MODULE_DIR_.'/'.self::NAME. '/'.self::NAME.'.txt', $msg, FILE_APPEND);
            
            return $msg;
        }
    }
}