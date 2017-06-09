<?php
if (!defined('BASEPATH'))
    exit('No direct script access allowed');
class MY_Model extends CI_Model {
    protected $CI;
    protected $DB;
    protected $zeit;
    static private $DB_INSTANCE = array();
    function __construct($init_db = true) {
        parent::__construct();
        $this->CI = & get_instance();
        $this->zeit = date('Y-m-d H:i:s', time());
        $this->ip = $this->CI->input->ip_address();
        if ($init_db) {
            $this->DB = $this->get_db_instance('default');
            
        }
    }
    /**
     * 获取数据库对象
     */
    private function get_db_instance($database) {
        if (empty(self::$DB_INSTANCE[$database])) {
            self::$DB_INSTANCE[$database] = $this->CI->load->database($database, true);
        } 
        return self::$DB_INSTANCE[$database];
    }

     /**
     * 开始事务
     */
    function start() {
        $this->DB->trans_start();
    }

    /**
     * 事务回滚并返回报错
     */
    function error() {
        $this->DB->trans_rollback();
        return false;
    }

    /**
     * 事务提交并返回成功
     */
    function success() {
        $this->DB->trans_complete();
        if ($this->DB->trans_status() === false) {
            $this->log_error(__method__);
            return false;
        }
        return true;
    }
    
    
    function set_error($code){
        return $this->CI->errorlib->set_error($code);
    }
    
    //$batch true=> 多条数据  false=>单条数据
    function get_row_array($condition, $select, $table, $batch = FALSE) {
        if ($condition == '' || !is_array($select) || $table == '') {
            return false;
        }
        $this->DB->select($select);
        $this->DB->where($condition);
        if (!$batch) {
            $this->DB->limit(1);
        }
        $query = $this->DB->get($table);
        // 记录数据库错误日志
        if ($query === false) {
            if(is_array($condition)) $condition = http_build_query ($condition);
            log_scribe('trace','model','get_info_fail'. $this->ip .': condition：'.$condition);
            return false;
        }
        $ret = array();
        if ($query->num_rows() > 0) {
            $ret = $query->result_array();
            if (!$batch) return $ret[0];
            return $ret;
        }
        return $ret;
    }
    
    //获取排序数据 默认降序
    function get_order_row_array($condition, $select, $table, $order_name, $order_type = 'DESC',$limit = 0) {
        if ($condition == '' || !is_array($select) || $table == '') {
            return false;
        }
        $this->DB->select($select);
        $this->DB->where($condition);
        $this->DB->order_by($order_name, $order_type);
         if ($limit != 0) {
            $this->DB->limit($limit);
        }
        $query = $this->DB->get($table);
        // 记录数据库错误日志
        if ($query === false) {
            if(is_array($condition)) $condition = http_build_query ($condition);
            log_scribe('trace','model','get_order_row_array'. $this->ip .': condition：'.$condition);
            return false;
        }
        $ret = array();
        if ($query->num_rows() > 0) {
            $ret = $query->result_array();
        }
        return $ret;
    }

    //获取信息的总记录数
    function get_data_num($condition, $table) {
        $this->DB->from($table);
        $this->DB->where($condition);
        $this->DB->limit(1);
        $num = $this->DB->count_all_results();
        // 记录数据库错误日志
        if ($num === false) {
            $text = (is_array($condition)&&$condition!='') ? http_build_query($condition):$condition;
            log_scribe('trace', 'model', $this->ip.' [get_data_num] where :'.$text);
            $this->set_error(Err_Code::ERR_DB);
            return false;
        }
        return $num;
    }
}
