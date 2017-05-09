<?php
require_once(__DIR__ . '/ttr/require.php');
require_once(__DIR__ . '/core.php');

$g_conf_idx = 0;
define('D_CONF_DIR', __DIR__ . '/../conf');

try {
    # load config
    while (null !== ($conf = readConf())) {
        # check exec backup
        if (true !== isExecConf($conf)) {
            continue;
        }
        execBackup($conf);
        
        # record log
        time();
    }
    
    # record log
    //time();
    
} catch (Exception $e) {
    echo $e->getMessage() . PHP_EOL;
}


function readConf () {
    try {
        global $g_conf_idx;
        
        $conts  = scandir(D_CONF_DIR);
        $hitcnt = 0;
        foreach ($conts as $elm) {
            $elm_pth = D_CONF_DIR . DIRECTORY_SEPARATOR . $elm;
            $ctype = filetype($elm_pth);
            if (0 !== strcmp('file', $ctype)) {
                continue;
            }
            $hitcnt++;
            if ($g_conf_idx < $hitcnt) {
                $g_conf_idx = $hitcnt;
                return yaml_parse_file($elm_pth);
            }
        }
        return null;
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
