<?php
require_once(__DIR__ . '/ttr/require.php');
require_once(__DIR__ . '/core.php');

$g_conf_idx = 0;
define('D_CONF_DIR', __DIR__ . '/../conf');

try {
    /* load config file */
    $conf = \ttr\dir\getFiles(D_CONF_DIR);
    foreach ($conf as $cnf_elm) {
        
        /* get config */
        $yml_cnf = yaml_parse_file($cnf_elm);
        if (false === $yml_cnf) {
            throw new Exception('invalid config');
        }
        
        /* check interval */
        if (true !== isExecConf($yml_cnf)) {
            continue;
        }
        
        /* execute backup */
        $tm = time();
        execBackup($yml_cnf, $tm);
        
        /* record log */
        $yml_cnf['last_backup'] = $tm;
        if (false === yaml_emit_file($cnf_elm, $yml_cnf)) {
            throw new Exception('failed record log');
        }
    }
} catch (Exception $e) {
    echo $e->getMessage() . PHP_EOL;
}


function isExecConf($conf) {
    try {
        $last = intval($conf["last_backup"]);
        if (0 === $last) {
            return true;
        }
        $cur_tm  = time();
        $diff_tm = $cur_tm - $last;
        
        /* check whether diff-time is outer than interval-time */
        if ($diff_tm > intval($conf["interval"])) {
            return true;
        }
        return false;
    } catch (Exception $e) {
        throw new Exception(
                   PHP_EOL .
                   'File:' . __FILE__     . ',' .
                   'Line:' . __line__     . ',' .
                   'Func:' . __FUNCTION__ . ',' .
                   $e->getMessage()
              );
    }
}
