<?php

function execBackup($cnf, $tm) {
    try {
        if ( (0 !== strcmp($cnf['src']['type'], 'dir')) ||
             (0 !== strcmp($cnf['dest']['type'], 'dir')) ) {
            throw new Exception(
                      PHP_EOL .
                      'File:' . __FILE__     . ',' .
                      'Line:' . __line__     . ',' .
                      'Message: not supported module type'
                  );
        }
        $src  = getInfo($cnf, 'src');
        $dest = getInfo($cnf, 'dest');
        
        $src_ssh = new \ttr\ssh\Command(
                       $src['host'],
                       array(
                           'username' => 'root',
                           'pubkey'   => '/root/.ssh/id_rsa.pub',
                           'prikey'   => '/root/.ssh/id_rsa'
                       )
                   );
        $tmp_nm  = $tm . '_' . $cnf['name'] . '.tar.gz';
        $cmd_str = 'tar zcvf /tmp/' . $tmp_nm . ' ' . $src['path'];
        /* create compress file */
        $src_ssh->execute($cmd_str);
        
        /* download compress file */
        $src_scp = new \ttr\ssh\Scp(
                       $src['host'],
                       array(
                           'username' => 'root',
                           'pubkey'   => '/root/.ssh/id_rsa.pub',
                           'prikey'   => '/root/.ssh/id_rsa'
                       )
                   );
        $src_scp->download('/tmp/' . $tmp_nm, '/tmp/'. $tmp_nm);
        
        /* remove remote temp file */
        $src_ssh->execute('rm -rf /tmp/' . $tmp_nm);
        
        /* upload backup file */
        $dst_scp = new \ttr\ssh\Scp(
                       $dest['host'],
                       array(
                           'username' => 'root',
                           'pubkey'   => '/root/.ssh/id_rsa.pub',
                           'prikey'   => '/root/.ssh/id_rsa'
                       )
                   );
        $dst_scp->upload('/tmp/' . $tmp_nm, $dest['path'] . '/' . $tmp_nm);
        
        /* remove local temp file */
        shell_exec('rm -rf /tmp/' . $tmp_nm);
        
        generation($cnf, $dest);
        
        $src_scp = null;
        $src_ssh = null;
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

function generation ($cnf, $dest) {
    try {
        $dst_cli = new \ttr\ssh\Command(
                       $dest['host'],
                       array(
                           'username' => 'root',
                           'pubkey'   => '/root/.ssh/id_rsa.pub',
                           'prikey'   => '/root/.ssh/id_rsa'
                       )
                   );
        $resp    = $dst_cli->execute('ll ' . $dest['path']);
        $resp    = \ttr\str\rem_ctrl_char($resp);
        $ex_resp = explode("\r\n", $resp);
        $stamps  = null;
        foreach ($ex_resp as $res_elm) {
            if ( (0 === strcmp('', $res_elm)) ||
                 (1 !== preg_match('/.*_'. $cnf['name'] . '[.]tar[.]gz/', $res_elm, $match)) ) {
                continue;
            }
            $ex_elm   = explode(' ', $match[0]);
            $gz_elm   = $ex_elm[count($ex_elm)-1];
            $stamps[] = explode('_', $gz_elm)[0];
        }
        
        if (intval($cnf['generation']) >= count($stamps)) {
            return;
        }
        $del_cnt = count($stamps) - intval($cnf['generation']);
        
        for ($loop=0; $loop < $del_cnt ;$loop++) {
            $oldest = null;
            foreach ($stamps as $stm_idx => $stm_val) {
                if ( ((null === $oldest)  && (null !== $stm_val)) ||
                     ((null !== $stm_val) && ($oldest[0] > $stm_val)) ) {
                    $oldest = array($stm_val, $stm_idx);
                }
            }
            /* delete oldest file */
            $dst_cli->execute('rm -rf ' . $dest['path'] . '/' . $oldest[0] . '_' . $cnf['name'] . '.tar.gz');
            $stamps[$oldest[1]] = null;
        }
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

function getInfo ($cnf, $type) {
    try {
        $ret_val['host'] = $cnf[$type]['host'];
        $ret_val['path'] = $cnf[$type]['prm']['target'];
        return $ret_val;
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

