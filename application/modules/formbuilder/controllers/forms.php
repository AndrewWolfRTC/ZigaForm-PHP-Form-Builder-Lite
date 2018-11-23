<?php
/**
 * Intranet
 *
 * PHP version 5
 *
 * @category  PHP
 * @package   PHP_Form_Builder
 * @author    Softdiscover <info@softdiscover.com>
 * @copyright 2013 Softdiscover
 * @license   http://www.php.net/license/3_01.txt  PHP License 3.01
 * @version   CVS: $Id: intranet.php, v2.00 2013-11-30 02:52:40 Softdiscover $
 * @link      https://php-form-builder.zigaform.com/
 */
if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/**
 * Estimator intranet class
 *
 * @category  PHP
 * @package   PHP_Form_Builder
 * @author    Softdiscover <info@softdiscover.com>
 * @copyright 2013 Softdiscover
 * @license   http://www.php.net/license/3_01.txt  PHP License 3.01
 * @version   Release: 1.00
 * @link      https://php-form-builder.zigaform.com/
 */
class Forms extends MX_Controller {
    /**
     * max number of forms in order show by pagination
     *
     * @var int
     */

    const VERSION = '0.1';

    /**
     * name of form estimator table
     *
     * @var string
     */
    var $per_page = 10;
    protected $modules;
            
    private $saved_form_id = "";
    private $current_data_addon = array();
    private $current_data_form = array();
    private $current_data_num_tabs = array();
    private $current_data_tab_cont = array();
    private $current_data_steps = array();
    private $current_data_skin = array();
    private $current_data_wizard = array();
    private $current_data_onsubm = array();
    private $current_data_main = array();
    private $saveform_clogic = array();
    
    
     public $gen_post_src;
     
    /**
     * Forms::__construct()
     * 
     * @return 
     */
    function __construct() {
        parent::__construct();
        $this->load->language_alt(model_settings::$db_config['language']);
        $this->template->set('controller', $this);
        $this->load->model('model_forms');
        $this->load->model('model_fields');
        $this->load->model('model_form_log');
        $this->load->model('addon/model_addon');
        $this->load->model('addon/model_addon_details');
        $this->load->library('cache');  
        $this->auth->authenticate(true);
    }
    
    public function ajax_pdf_showsample() {
                                    
        $full_page = (isset($_POST['full_page'])) ? Uiform_Form_Helper::sanitizeInput(trim($_POST['full_page'])) : '';
        $message = (isset($_POST['message'])) ? Uiform_Form_Helper::sanitizeInput_html($_POST['message']) : '';
        $message = urldecode($message);
        
        $data2=array();
        $data2['font']='2';
        $data2['charset']='UTF-8';
        $data2['head_extra']='';
        $data2['content']=$message;
        $data2['html_wholecont']=$full_page;
                                    
        $content=$this->load->view('formbuilder/frontend/pdf_global_template',$data2,true);
        
        $file_name = 'zgfm_pdf_sample';
        
         //remove previous pdf sample
        @unlink(FCPATH.'temp/'.$file_name.'.pdf');
        
        $output = generate_pdf($content, $file_name, false);
        $status='0';
        if (file_exists($output)) {
            $status='1';
        } else {
            $status='0';
        }
        
        $json = array();           
        $json['status'] =$status;
        $json['pdf_name'] =$file_name;
        $json['pdf_dir'] =$output;
        $json['dir'] = FCPATH;
        $json['pdf_url'] =base_url().'temp/'.$file_name.'.pdf';
        
        //return data to ajax callback
        header('Content-Type: application/json');
        echo json_encode($json);
        die();
    }
    
    public function ajax_email_sendsample() {
       
        $full_page = (isset($_POST['full_page'])) ? Uiform_Form_Helper::sanitizeInput(trim($_POST['full_page'])) : '';
        $message = (isset($_POST['message'])) ? Uiform_Form_Helper::sanitizeInput_html($_POST['message']) : '';
        $message = urldecode($message);
        $email_to = (isset($_POST['email_to'])) ? Uiform_Form_Helper::sanitizeInput($_POST['email_to']) : '';
                                    
        $mail_template_msg = $this->load->view('formbuilder/frontend/mail_global_template',array('content'=>$message,'html_wholecont'=>$full_page),true);
        
        $data_mail=array();
        $data_mail['from_mail']=$email_to;
        $data_mail['from_name']='test';
        $data_mail['message']=$mail_template_msg;
        $data_mail['subject']='Zigaform - this is just a test';
        $data_mail['attachments']=array();
        $data_mail['to']=$email_to;
        $data_mail['cc']='';
        $data_mail['bcc']='';
        $data_mail['mail_replyto']='';
                                    
        //$mail_errors=$this->process_mail($data_mail);
        $mail_errors = modules::run('formbuilder/frontend/process_mail',$data_mail);
        
        $json = array();           
        $json['st_error'] =($mail_errors===true)?1:0;
        
        
        //return data to ajax callback
        header('Content-Type: application/json');
        echo json_encode($json);
        die();
    }
    
    
     public function ajax_rollback_process() {
        
        //check_ajax_referer( 'zgfm_ajax_nonce', 'zgfm_security' );
        
        $log_id = (isset($_POST['log_id'])) ? Uiform_Form_Helper::sanitizeInput(trim($_POST['log_id'])) : '';
        
        
        
        
        $query_obj=$this->model_form_log->getLogById($log_id);
        
            
            
        $json = array();
       /* $json['log_frm_data'] =  $query_obj->log_frm_data;
        $json['log_frm_name'] =  $query_obj->log_frm_name;
        $json['log_frm_html_backend'] =  $query_obj->log_frm_html_backend;
        $json['log_frm_id'] =  $query_obj->log_frm_id;  */  
        
        $data=array();
        $data['fmb_data'] = json_decode($query_obj->log_frm_data, true);
        $data['fmb_name'] = $query_obj->log_frm_name;
        $data['fmb_html_backend']=Uiform_Form_Helper::encodeHex($query_obj->log_frm_html_backend);

        $json['data'] =$data;
        
        
        //return data to ajax callback
        header('Content-Type: application/json');
        echo json_encode($json);
         die();
    }
    
    
    public function ajax_rollback_openmodal() {
        
        //check_ajax_referer( 'zgfm_ajax_nonce', 'zgfm_security' );
        
        $form_id = (isset($_POST['form_id'])) ? Uiform_Form_Helper::sanitizeInput(trim($_POST['form_id'])) : '';
        
        $data=array();
        
        
        $query_obj=$this->model_form_log->getAvailableLogById($form_id);
        
            
        $log_array=array();
        foreach ($query_obj as $key => $value) {
            $temp=array();
            $temp['form_name']=$value->log_frm_name;
            $temp['created_date']=date("d-m-Y h:m:s", strtotime($value->updated_date));
            $temp['log_id']=$value->log_id;
            $log_array[]=$temp;
        }
        
        $data['logs']=$log_array;
        
        $json = array();
        $json['modal_header'] = '<h3>'.__('Rollback Form','FRocket_admin').'</h3>';
        $json['modal_body'] = $this->load->view('formbuilder/forms/ajax_rollback_openmodal', $data, true);
        $json['modal_footer'] = $this->load->view('formbuilder/forms/modal1_footer', $data,true);
        
        //return data to ajax callback
        header('Content-Type: application/json');
        echo json_encode($json);
         die();
    }
    
    public function ajax_integrity_openmodal() {
        
        //check_ajax_referer( 'zgfm_ajax_nonce', 'zgfm_security' );
        
        $form_id = (isset($_POST['form_id'])) ? Uiform_Form_Helper::sanitizeInput(trim($_POST['form_id'])) : '';
        
        $data=array();
        
            
        
        $json = array();
        $json['modal_header'] = '<h3>'.__('Error message','FRocket_admin').'</h3>';
        
        $json['modal_body'] = self::render_template('formbuilder/views/forms/ajax_integrity_openmodal.php', $data, 'always');
        $json['modal_footer'] = self::render_template('formbuilder/views/forms/modal1_footer.php', $data);
            
        //return data to ajax callback
        header('Content-Type: application/json');
        echo json_encode($json);
        die();
    }
    
    public function ajax_variables_openmodal() {
            
        $data=array();
        $form_id = (isset($_POST['form_id'])) ? Uiform_Form_Helper::sanitizeInput(trim($_POST['form_id'])) : '';
        $fmb_data = (isset($_POST['form_data'])) ? $_POST['form_data'] : '';
        $fmb_data = (isset($fmb_data) && $fmb_data) ? array_map(array('Uiform_Form_Helper', 'sanitizeRecursive_html'), json_decode($fmb_data, true)) : array();
            $data['fmb_data'] = $fmb_data;
            
            
            
        $json = array();
        $json['modal_header'] = '<h3>'.__('Form variables','FRocket_admin').'</h3>';
        $json['modal_body'] = $this->load->view('formbuilder/forms/ajax_variables_openmodal', $data, true);
        $json['modal_footer'] = $this->load->view('formbuilder/forms/modal1_footer', $data, true);
        
        //return data to ajax callback
        header('Content-Type: application/json');
        echo json_encode($json);
        die();
    }   
    /**
     * Forms::ajax_load_templateform()
     * 
     * @return 
     */
    public function ajax_load_templateform() {
        $number = ($_POST['number']) ? Uiform_Form_Helper::sanitizeInput(trim($_POST['number'])) : '';
        $fallback_file = file_get_contents(FCPATH . '/assets/backend/json/template_' . $number . '.json');
        header('Content-Type: application/json');
        echo $fallback_file;
        die();
    }

    /**
     * Forms::ajax_load_getthumbimg()
     * 
     * @return 
     */
    public function ajax_load_getthumbimg() {
        //this function is disabled
        return;
        $id_img = ($_POST['img_id']) ? Uiform_Form_Helper::sanitizeInput($_POST['img_id']) : '';
        $img_full = ($_POST['img_src_full']) ? Uiform_Form_Helper::sanitizeInput_html($_POST['img_src_full']) : '';
        $json = array();
        $json['img_full'] = $img_full;
        $json['img_thumb'] = (!empty($thumb[0])) ? $thumb[0] : $img_full;
        header('Content-Type: application/json');
        echo json_encode($json);
        die();
    }

    /**
     * Forms::ajax_load_import_form()
     * 
     * @return 
     */
    public function ajax_load_import_form() {
        $imp_form = (isset($_POST['importcode']) && $_POST['importcode']) ? Uiform_Form_Helper::sanitizeInput($_POST['importcode']) : '';
            
        $dump_form = unserialize(Uiform_Form_Helper::base64url_decode($imp_form));
        $data_form = array();
        $data_form['fmb_data'] = json_decode($dump_form['fmb_data']);
        $data_form['fmb_html_backend'] = $dump_form['fmb_html_backend'];
        $data_form['fmb_name'] = $dump_form['fmb_name'];
        $json = array();
        $json['data'] = $data_form;
        header('Content-Type: application/json');
        echo json_encode($json);
        die();
    }
    
    
    public function ajax_preview_clogic_graph(){
        
        $saveform_clogic=array();
            
        $fmb_data = (!empty($_POST['form_data'])) ? $_POST['form_data'] : '';
        $fmb_data = (!empty($fmb_data)) ? array_map(array('Uiform_Form_Helper', 'sanitizeRecursive_html'), json_decode($fmb_data, true)) : array();

        
        
        //creating again
        $steps_src = $fmb_data['steps_src'];
        $tmp_var_typename=array();
        $tmp_var_fname=array();
        $tmp_var_fstep=array();
        if (!empty($steps_src)) {
            foreach ($steps_src as $tabindex => $fields) {
                if (!empty($fields)) {
                    foreach ($fields as $key => $value) {
                        $data = array();
                        $data['fmf_uniqueid'] = $value['id'];
                       
                        $data['fmf_fieldname'] = isset($value['field_name'])?$value['field_name']:'not defined';
                        $data['fmf_type_n'] = isset($value['type_n'])?$value['type_n']:'not defined';
                       
                        $data['type_fby_id'] = $value['type'];
                         
                        
                        $tmp_var_typename[$value['id']]=$data['fmf_type_n'];
                        $tmp_var_fname[$value['id']]=$data['fmf_fieldname'];
                        $tmp_var_fstep[$value['id']]=  intval($tabindex)+1;
                        
                        if (isset($value['clogic']) && intval($value['clogic']['show_st']) === 1) {
                            $tmp_clogic = array();
                            $tmp_clogic['field_cond'] = $value['id'];
                            $tmp_clogic['field_cond_fname'] = $data['fmf_fieldname'];
                            $tmp_clogic['field_type_n'] = $data['fmf_type_n'];
                            
                            $tmp_clogic['action'] = $value['clogic']['f_show'];
                            
                            foreach ($value['clogic']['list'] as $key2 => $value2) {
                                if (empty($value2)) {
                                    unset($value['clogic']['list'][$key2]);
                                }
                            }
                            $tmp_clogic['list'] = array_filter($value['clogic']['list']);
                            $tmp_clogic['req_match'] = (intval($value['clogic']['f_all']) === 1) ? count($value['clogic']['list']) : 1;
                            $saveform_clogic['cond'][] = $tmp_clogic;
                        }
                    }
                }
            }
        }
          
        $clogic_src = $saveform_clogic;
        if (!empty($clogic_src)) {
            //get fires
            $fields_fire = array();
            foreach ($clogic_src['cond'] as $key => $value) {
                foreach ($value['list'] as $key2 => $value2) {
                    if (!empty($value2)) {
                        if (!isset($fields_fire[$value2['field_fire']]['list'][$value['field_cond']])) {
                            $fields_fire[$value2['field_fire']]['list'][] = $value['field_cond'];
                        }
                    } else {
                        unset($clogic_src['cond'][$key]['list'][$key2]);
                    }
                }
            }
            $saveform_clogic = $clogic_src;
            // field fires
            $logic_field_fire = array();
            foreach ($fields_fire as $key => $value) {
                $temp_logic = array();
                $temp_logic['field_fire'] = $key;
                $temp_logic['field_fire_typen'] = isset($tmp_var_typename[$key])?$tmp_var_typename[$key]:'undefined';
                $temp_logic['field_fire_fname'] = isset($tmp_var_fname[$key])?$tmp_var_fname[$key]:'undefined';
                $temp_logic['field_fire_fstep'] = isset($tmp_var_fstep[$key])?$tmp_var_fstep[$key]:'undefined';
                
                $tmp_list = array();
                foreach ($value['list'] as $value2) {
                    $tmp_list[] = array('field_cond' => $value2,
                                    'field_cond_typen' => isset($tmp_var_typename[$value2])?$tmp_var_typename[$value2]:'undefined',
                                    'field_cond_fname' => isset($tmp_var_fname[$value2])?$tmp_var_fname[$value2]:'undefined',
                                    'field_cond_fstep' => isset($tmp_var_fstep[$value2])?$tmp_var_fstep[$value2]:'undefined'
                                    );
                }
                $temp_logic['list'] = $tmp_list;
                $logic_field_fire[$key] = $temp_logic;
            }

            $clogic_src['fire'] = $logic_field_fire;
            $saveform_clogic = $clogic_src;
        }
        
        $data2=array();
        $data2['clogic']=$saveform_clogic;
        $output=$this->load->view('formbuilder/forms/preview_clogic_graph',$data2,true);
        
        $json = array();
        $json['html'] = $output;
        header('Content-Type: text/html; charset=UTF-8');
        echo json_encode($json);
        die();
    }
    
    
    /**
     * Forms::ajax_listform_duplicate()
     * 
     * @return 
     */
    public function ajax_listform_duplicate() {
        $list_ids = (isset($_POST['id']) && $_POST['id']) ? array_map(array('Uiform_Form_Helper', 'sanitizeRecursive'), $_POST['id']) : array();

        if ($list_ids) {
            foreach ($list_ids as $value) {
                $data_form = $this->model_forms->getFormById($value);
                $data = array();
                $data['fmb_data'] = $data_form->fmb_data;
                $data['fmb_data2'] = $data_form->fmb_data2;
                $data['fmb_name'] = $data_form->fmb_name . ' - copy';
                $data['fmb_html_backend'] = $data_form->fmb_html_backend;
                $data['created_ip'] = $_SERVER['REMOTE_ADDR'];
                $data['created_by'] = 1;
                $data['created_date'] = date('Y-m-d h:i:s');

                $this->db->set($data);
                $this->db->insert($this->model_forms->table);
            }
        }
    }

    
    /**
     * Forms::ajax_listform_updatest()
     * 
     * @return 
     */
    public function ajax_listform_updatest() {
        $list_ids = (isset($_POST['id']) && $_POST['id']) ? array_map(array('Uiform_Form_Helper', 'sanitizeRecursive'), $_POST['id']) : array();
        $form_st = (isset($_POST['form_st']) && $_POST['form_st']) ? Uiform_Form_Helper::sanitizeInput($_POST['form_st']) : '';
        if ($list_ids) {
            foreach ($list_ids as $value) {

                $data = array(
                    'flag_status' => intval($form_st)
                );

                $this->db->set($data);
                $this->db->where('fmb_id', $value);
                $this->db->update($this->model_forms->table);
            }
        }
    }

    /**
     * Forms::ajax_delete_form_byid()
     * 
     * @return 
     */
    public function ajax_delete_form_byid() {
        $form_id = (isset($_POST['form_id']) && $_POST['form_id']) ? Uiform_Form_Helper::sanitizeInput($_POST['form_id']) : 0;

        $data = array(
            'flag_status' => 0
        );

        $this->db->set($data);
        $this->db->where('fmb_id', $form_id);
        $this->db->update($this->model_forms->table);
        
    }

    /**
     * Forms::ajax_load_preview_form()
     * 
     * @return 
     */
    public function ajax_load_preview_form() {

        $form_id = (isset($_POST['form_id'])) ? Uiform_Form_Helper::sanitizeInput(trim($_POST['form_id'])) : '';
        header('Content-type: text/html');
        ob_start();
        
        $data=array();
        $content = '';
            $content = site_url() . 'formbuilder/frontend/viewform/?form=' . $form_id;
            $data['url'] = escape_text($content);

            $temp=array();
            $temp['url_form']=$data['url'].'&lmode=1';
            $temp['base_url']=base_url();
            $temp['form_id']=$form_id;
            $data_addon_front = $this->cache->get('addon_front');
            $temp['addon']= json_encode($data_addon_front);
            $data['iframe'] = $this->load->view('formbuilder/forms/get_code_iframe', $temp, true);
            echo $data['iframe'];
        ?>
             
        
        <?php
        $output = ob_get_clean();
        echo $output;
        die();
    }
    
    
    public function ajax_modal_form_showmodalsuccess() {
            
        $form_id = (isset($_POST['form_id'])) ? Uiform_Form_Helper::sanitizeInput(trim($_POST['form_id'])) : '';
        $data=array();
        $data['content_top']=__('Success! The form was created. Now just copy and paste the shortcode to your content','FRocket_admin');
        $data['form_id']=$form_id;
        $json = array();
        $json['html_title'] = __('Success','FRocket_admin');
        $json['html'] = $this->load->view('formbuilder/forms/form_show_shortcodes', $data, true);;
        
        //return data to ajax callback
        header('Content-type: text/html');
        echo json_encode($json);
        die();
    }
    
    public function ajax_modal_form_getshorcodes() {
            
        $form_id = (isset($_POST['form_id'])) ? Uiform_Form_Helper::sanitizeInput(trim($_POST['form_id'])) : '';
        $data=array();
        $data['form_id']=$form_id;
        $json = array();
        $json['html_title'] = __('Shortcodes','FRocket_admin');
        $json['html'] = $this->load->view('formbuilder/forms/form_show_shortcodes', $data, true);;
        
        //return data to ajax callback
        header('Content-type: text/html');
        echo json_encode($json);
        die();
    }
    
    /**
     * Forms::ajax_refresh_previewpanel()
     * 
     * @return 
     */
    public function ajax_refresh_previewpanel() {
        $data = array();
        $fmb_data = (!empty($_POST['form_data'])) ? $_POST['form_data'] : '';
        $fmb_data = (!empty($fmb_data)) ? array_map(array('Uiform_Form_Helper', 'sanitizeRecursive_html'), json_decode($fmb_data, true)) : array();

        $data['fmb_data'] = $fmb_data;
        $data['fmb_name'] = (!empty($_POST['uifm_frm_main_title'])) ? Uiform_Form_Helper::sanitizeInput(trim($_POST['uifm_frm_main_title'])) : '';
        
        //in case title is empty
        if(empty($data['fmb_name']) && !empty($_POST['uifm_frm_main_id']) && intval($_POST['uifm_frm_main_id'])>0){
            $tmp_form_id = (!empty($_POST['uifm_frm_main_id'])) ? Uiform_Form_Helper::sanitizeInput(trim($_POST['uifm_frm_main_id'])) : '';
            $tmp_form_title = $this->model_forms->getTitleFormById($tmp_form_id);
            $data['fmb_name'] = $tmp_form_title->fmb_name;
            
        }
        
        $json = array();
        $tmp_html = $this->generate_previewpanel_html($data);
        $data['fmb_html_backend'] = Uiform_Form_Helper::encodeHex(do_shortcode($tmp_html['output_html']));
        $json['data'] = $data;
        //return data to ajax callback
        header('Content-Type: application/json');
        echo json_encode($json);
        die();
    }

    /**
     * Forms::ajax_save_form_updateopts()
     * 
     * @return 
     */
    public function ajax_save_form_updateopts() {
        $data = array();
        $fmb_id = ($_POST['uifm_frm_main_id']) ? Uiform_Form_Helper::sanitizeInput(trim($_POST['uifm_frm_main_id'])) : 0;
        $data['fmb_html_backend'] = ($_POST['form_html_backend']) ? Uiform_Form_Helper::sanitizeInput_html($_POST['form_html_backend']) : '';
        $json = array();
        if (intval($fmb_id) > 0) {

            $this->db->set($data);
            $this->db->where('fmb_id', $fmb_id);
            $this->db->update($this->model_forms->table);


            $json['status'] = 'updated';
            $json['id'] = $fmb_id;
        }
        //return data to ajax callback
        header('Content-Type: application/json');
        echo json_encode($json);
        die();
    }

    /**
     * Forms::ajax_save_form()
     * 
     * @return 
     */
    public function ajax_save_form() {
        try{
            ob_start();
        $data = array();

        $fmb_data = (isset($_POST['form_data'])) ? $_POST['form_data'] : '';
        $fmb_data = urldecode($fmb_data);
        $fmb_data = (isset($fmb_data) && $fmb_data) ? array_map(array('Uiform_Form_Helper', 'sanitizeRecursive_html'), json_decode($fmb_data, true)) : array();

        //here a message should be sent
        if (empty($fmb_data)) {
            return false;
        }
        
        //addon data
        $fmb_addon_data = (isset($_POST['addon_data']))?urldecode(Uiform_Form_Helper::sanitizeInput_html($_POST['addon_data'])):'';
        $fmb_addon_data = (isset($fmb_addon_data) && $fmb_addon_data) ? array_map(array('Uiform_Form_Helper', 'sanitizeRecursive_html'), json_decode($fmb_addon_data, true)) : array();
         
        
        $data['fmb_data'] = json_encode($fmb_data);
        $tmp_data2=array();
        $tmp_data2['onsubm']=isset($fmb_data['onsubm']) ? $fmb_data['onsubm'] : '';
        $tmp_data2['main']=isset($fmb_data['main']) ? $fmb_data['main'] : '';
        $data['fmb_data2'] = !empty($tmp_data2) ? json_encode($tmp_data2) : '';
        $data['fmb_name'] = (!empty($_POST['uifm_frm_main_title'])) ? Uiform_Form_Helper::sanitizeInput(trim($_POST['uifm_frm_main_title'])) : '';
        $data['created_ip'] = $_SERVER['REMOTE_ADDR'];
        $data['created_by'] = 1;
        $data['created_date'] = date('Y-m-d h:i:s');
        $fmb_id = (isset($_POST['uifm_frm_main_id'])) ? Uiform_Form_Helper::sanitizeInput(trim($_POST['uifm_frm_main_id'])) : 0;

        /* global for fonts */
        global $global_fonts_stored;
        $global_fonts_stored = array();

        $json = array();
        if (intval($fmb_id) > 0) {

            $this->db->set($data);
            $this->db->where('fmb_id', $fmb_id);
            $this->db->update($this->model_forms->table);

            $json['status'] = 'updated';
            $json['id'] = $fmb_id;
        } else {

            $this->db->set($data);
            $this->db->insert($this->model_forms->table);

            $idActivate = $this->db->insert_id();
            $json['status'] = 'created';
            $json['id'] = $idActivate;
        }
        
        $data_form = $this->model_forms->getFormById($json['id']);
        $fmb_data = json_decode($data_form->fmb_data, true);
        //all fields position
        $tab_cont = $fmb_data['steps']['tab_cont'];
        // all data fields
        $this->current_data_addon = $fmb_addon_data;
        $this->current_data_form = $fmb_data['steps_src'];
        $this->current_data_num_tabs = $fmb_data['num_tabs'];
        $this->current_data_tab_cont = $fmb_data['steps']['tab_cont'];
        $this->current_data_steps = $fmb_data['steps'];
        $this->current_data_skin = $fmb_data['skin'];
        $this->current_data_wizard = ($fmb_data['wizard']) ? $fmb_data['wizard'] : array();
        $this->current_data_onsubm = ($fmb_data['onsubm']) ? $fmb_data['onsubm'] : array();
        $this->current_data_main = ($fmb_data['main']) ? $fmb_data['main'] : array();
        
        
        if (intval($json['id']) > 0) {
            
             // process addons
            $data_addon_back = $this->cache->get('addon_back');
            
                    if(!empty($fmb_addon_data)){
                        foreach ($fmb_addon_data as $key => $value) {
                           if(isset($data_addon_back[$key][$value['controller']])){
                                //call_user_func(array(self::$_addons[$key][$value['controller']] , 'saveData'));
                              
                               modules::run($key.'/'.$value['controller'].'/saveData',$json['id'], $value['data']);
                               
                               // self::$_addons[$key][$value['controller']]->saveData($json['id'], $value['data']);
                                
                                switch($key){
                                    case 'addon_func_anim':
                                    
                                        $this->current_data_form = modules::run($key.'/'.$value['controller'].'/mergeData',$this->current_data_form,$value['data']);
                                        
                                       // self::$_addons[$key][$value['controller']]->mergeData($this->current_data_form,$value['data']);
                                        
                                        break;
                                }
                                
                            }
                        }
                    }
            
            //save fields to table
            $this->saved_form_id = $json['id'];
            $this->save_data_fields($json['id']);
            //save fields to table
            $this->save_form_clogic();

            //generate form html
            $gen_return = $this->generate_form_html($json['id']);
            $data4 = array();
            $data4['fmb_html'] = $gen_return['output_html'];
            $data4['fmb_html_backend'] = $this->generate_admin_form_html($json['id']);
             
            //get global style
            $data2 = array();
            $data2['idform'] = $json['id'];
            $data2['addition_css'] = $this->current_data_main['add_css'];
            $data2['skin'] = $this->current_data_skin;
            $gen_return['output_css'].=$this->load->view('formbuilder/forms/formhtml_css_global', $data2, true);

            $data3 = array();
            $data3['fonts'] = $global_fonts_stored;
            $gen_return['output_css']=$this->load->view('formbuilder/forms/formhtml_css_init', $data3, true) . $gen_return['output_css'];
            $data4['fmb_html_css'] = $gen_return['output_css'];
            $this->db->set($data4);
            $this->db->where('fmb_id', $json['id']);
            $this->db->update($this->model_forms->table);

            //generate form css
            ob_start();
            $pathCssFile = FCPATH . '/assets/frontend/css/rockfm_form' . $json['id'] . '.css';
            $f = fopen($pathCssFile, "w");
            fwrite($f, $gen_return['output_css']);
            fclose($f);
            ob_end_clean();
            
              //add to log
            $save_log_st=false;
            $count_log_rec=$this->model_form_log->CountLogsByFormId($json['id']);
            
            
            if(intval($count_log_rec)>0){
                $last_rec=$this->model_form_log->getLastLogById($json['id']);
                $new_hash= md5($data_form->fmb_data);
                $old_hash=$last_rec->log_frm_hash;
                if($new_hash!=$old_hash){
                    $save_log_st=true;
                }
            }else{
                $save_log_st=true;
            }
            
            $log_lastid = 0;
            
            if($save_log_st){
                $data5= array();
                $data5["log_frm_data"]=$data['fmb_data'];
                $data5["log_frm_name"]=$data['fmb_name'];
                $data5["log_frm_html"]='';
                $data5["log_frm_html_backend"]=$data4['fmb_html_backend'];
                $data5["log_frm_html_css"]='';
                $data5["log_frm_id"]=$json['id'];
                $data5["log_frm_hash"]= md5($data_form->fmb_data);
                $data5['created_ip'] = $_SERVER['REMOTE_ADDR'];
                $data5['created_by'] = 1;
                $data5['created_date'] = date('Y-m-d h:i:s');
                
                //$this->wpdb->insert($this->model_form_log->table, $data5);
                 $this->db->set($data5);
                 $this->db->insert($this->model_form_log->table);
                 $log_lastid = $this->db->insert_id();
                 
                 //remove oldest if limit is exceeded
                 if(intval($count_log_rec)>50){
                     $tmp_log=$this->model_form_log->getOldLogById($json['id']);
                     
                     $this->db->where('log_id',$tmp_log->log_id)->delete($this->model_form_log->table);
                 }
            
            }
            
             // process addons
            $data_addon_back = $this->cache->get('addon_back');
            
                    if(!empty($fmb_addon_data)){
                        foreach ($fmb_addon_data as $key => $value) {
                           if(isset($data_addon_back[$key][$value['controller']])){
                                //call_user_func(array(self::$_addons[$key][$value['controller']] , 'saveData'));
                                   
                               modules::run($key.'/'.$value['controller'].'/saveLog',$json['id'],$save_log_st,$log_lastid, $value['data']);
                               //self::$_addons[$key][$value['controller']]->saveLog($json['id'],$save_log_st,$log_lastid, $value['data']);     
                                
                            }
                        }
                    }
            
        }
        
         //checking errors
            $output_error = ob_get_contents();
            if (ob_get_length()) ob_end_clean();
            if(!empty($output_error)){
                throw new Exception($output_error);
            }
        
        } catch (Exception $e) {
            
            
            $json=array();
            $json['status'] = 'failed';
            $json['Message'] = $e->getMessage();
        }
        //return data to ajax callback
        header('Content-Type: application/json');
        echo json_encode($json);
        die();
        
        
    }

    /**
     * Forms::generate_form_getField()
     * 
     * @return 
     */
    protected function generate_form_getField($child_field) {
        $str_output = '';
        $str_output_3 = '';

        $data = array();
        $data = $this->current_data_form[intval($child_field['num_tab'])][$child_field['id']];
        
        $data['addon_extraclass']='';
         // process addons
        
        $tmp_arr=array();
        
         $data_addon_back = $this->cache->get('addon_back');
                                    
             if(!empty($this->current_data_addon)){
                foreach ($this->current_data_addon as $key => $value) {
                   if(isset($data_addon_back[$key][$value['controller']])){
                        
                        switch($key){
                            case 'addon_func_anim':
                                if(isset($data['addon_func_anim'])){
                                    
                                    $data=modules::run($key.'/'.$value['controller'].'/getExtraDataField2', $data);
                            
                                    //$data = array_merge($data, $tmp_arr);
                                   // self::$_addons[$key][$value['controller']]->getExtraDataField($data);
                                }
                                break;
                        }

                    }
                }
            }
        
        switch (intval($child_field['type'])) {
            case 6:
                //textbox
               
                $str_output.=modules::run('formbuilder/fields/formhtml_textbox', $data, $child_field['num_tab']);
                $str_output_3.=modules::run('formbuilder/fields/formhtml_textbox_css', $data);

                break;
            case 7:
                //textarea
                $str_output.=modules::run('formbuilder/fields/formhtml_textarea', $data, $child_field['num_tab']);
                $str_output_3.=modules::run('formbuilder/fields/formhtml_textarea_css', $data);

                break;
            case 8:
                //radio button
                $data['main'] = $this->current_data_main;
                $str_output.=modules::run('formbuilder/fields/formhtml_radiobtn', $data, $child_field['num_tab']);
                $str_output_3.=modules::run('formbuilder/fields/formhtml_radiobtn_css', $data);

                break;
            case 9:
                //checkbox
                $data['main'] = $this->current_data_main;
                $str_output.=modules::run('formbuilder/fields/formhtml_checkbox', $data, $child_field['num_tab']);
                $str_output_3.=modules::run('formbuilder/fields/formhtml_checkbox_css', $data);

                break;
            case 10:
                //select
                $data['main'] = $this->current_data_main;
                $str_output.=modules::run('formbuilder/fields/formhtml_select', $data, $child_field['num_tab']);
                $str_output_3.=modules::run('formbuilder/fields/formhtml_select_css', $data);

                break;
            case 11:
                //multiselect
                $data['main'] = $this->current_data_main;
                $str_output.=modules::run('formbuilder/fields/formhtml_multiselect', $data, $child_field['num_tab']);
                $str_output_3.=modules::run('formbuilder/fields/formhtml_multiselect_css', $data);

                break;
            case 12:
                //fileupload
                $str_output.=modules::run('formbuilder/fields/formhtml_fileupload', $data, $child_field['num_tab']);
                $str_output_3.=modules::run('formbuilder/fields/formhtml_fileupload_css', $data);

                break;
            case 13:
                //imageupload
                $str_output.=modules::run('formbuilder/fields/formhtml_imageupload', $data, $child_field['num_tab']);
                $str_output_3.=modules::run('formbuilder/fields/formhtml_imageupload_css', $data);
                break;
            case 14:
                //custom html
                $str_output.=modules::run('formbuilder/fields/formhtml_customhtml', $data, $child_field['num_tab']);
                $str_output_3.=modules::run('formbuilder/fields/formhtml_customhtml_css', $data);
                break;
            case 15:
                //password
                $str_output.=modules::run('formbuilder/fields/formhtml_password', $data, $child_field['num_tab']);
                $str_output_3.=modules::run('formbuilder/fields/formhtml_password_css', $data);
                break;
            case 16:
                //slider
                $data['main'] = $this->current_data_main;
                $str_output.=modules::run('formbuilder/fields/formhtml_slider', $data, $child_field['num_tab']);
                $str_output_3.=modules::run('formbuilder/fields/formhtml_slider_css', $data);
                break;
            case 17:
                //range
                $str_output.=modules::run('formbuilder/fields/formhtml_range', $data, $child_field['num_tab']);
                $str_output_3.=modules::run('formbuilder/fields/formhtml_range_css', $data);
                break;
            case 18:
                //spinner
                $data['main'] = $this->current_data_main;
                $str_output.=modules::run('formbuilder/fields/formhtml_spinner', $data, $child_field['num_tab']);
                $str_output_3.=modules::run('formbuilder/fields/formhtml_spinner_css', $data);
                break;
            case 19:
                //captcha
                $str_output.=modules::run('formbuilder/fields/formhtml_captcha', $data, $child_field['num_tab']);
                $str_output_3.=modules::run('formbuilder/fields/formhtml_captcha_css', $data);
                break;
            case 20:

                //submit button
                $str_output.=modules::run('formbuilder/fields/formhtml_submitbtn', $data, $child_field['num_tab']);
                $str_output_3.=modules::run('formbuilder/fields/formhtml_submitbtn_css', $data);
                break;
            case 21:
                //hidden field
                $str_output.=modules::run('formbuilder/fields/formhtml_hiddeninput', $data, $child_field['num_tab']);

                break;
            case 22:
                //star rating
                $str_output.=modules::run('formbuilder/fields/formhtml_ratingstar', $data, $child_field['num_tab']);
                $str_output_3.=modules::run('formbuilder/fields/formhtml_ratingstar_css', $data);
                break;
            case 23:
                //color picker
                $str_output.=modules::run('formbuilder/fields/formhtml_colorpicker', $data, $child_field['num_tab']);
                $str_output_3.=modules::run('formbuilder/fields/formhtml_colorpicker_css', $data);
                break;
            case 24:
                //date picker
                $str_output.=modules::run('formbuilder/fields/formhtml_datepicker', $data, $child_field['num_tab']);
                $str_output_3.=modules::run('formbuilder/fields/formhtml_datepicker_css', $data);
                break;
            case 25:
                //time picker
                $str_output.=modules::run('formbuilder/fields/formhtml_timepicker', $data, $child_field['num_tab']);
                $str_output_3.=modules::run('formbuilder/fields/formhtml_timepicker_css', $data);
                break;
            case 26:
                //date time
                $str_output.=modules::run('formbuilder/fields/formhtml_datetime', $data, $child_field['num_tab']);
                $str_output_3.=modules::run('formbuilder/fields/formhtml_datetime_css', $data);
                break;
            case 27:
                //recaptcha
                $str_output.=modules::run('formbuilder/fields/formhtml_recaptcha', $data, $child_field['num_tab']);
                $str_output_3.=modules::run('formbuilder/fields/formhtml_recaptcha_css', $data);
                break;
            case 28:
                //prepended text
                $str_output.=modules::run('formbuilder/fields/formhtml_preptext', $data, $child_field['num_tab']);
                $str_output_3.=modules::run('formbuilder/fields/formhtml_preptext_css', $data);
                break;
            case 29:
                //appended text
                $str_output.=modules::run('formbuilder/fields/formhtml_appetext', $data, $child_field['num_tab']);
                $str_output_3.=modules::run('formbuilder/fields/formhtml_appetext_css', $data);
                break;
            case 30:
                //prep app text
                $str_output.=modules::run('formbuilder/fields/formhtml_prepapptext', $data, $child_field['num_tab']);
                $str_output_3.=modules::run('formbuilder/fields/formhtml_prepapptext_css', $data);
                break;
            case 31:
                //panel
                $str_output.=modules::run('formbuilder/fields/formhtml_panelfld', $data, $child_field['num_tab']);
                $str_output_3.=modules::run('formbuilder/fields/formhtml_panelfld_css', $data);
                break;
            case 32:
                //divider
                $str_output.=modules::run('formbuilder/fields/formhtml_divider', $data, $child_field['num_tab']);
                $data['form_skin'] = $this->current_data_skin;
                $str_output_3.=modules::run('formbuilder/fields/formhtml_divider_css', $data);

                break;
            case 33:
            case 34:
            case 35:
            case 36:
            case 37:
            case 38:
                //heading
                $str_output.=modules::run('formbuilder/fields/formhtml_heading', $data, $child_field['num_tab']);
                $str_output_3.=modules::run('formbuilder/fields/formhtml_heading_css', $data);
                break;
            case 39:
                //wizard buttons
                $data['form_wizard'] = $this->current_data_wizard;
                $str_output.=modules::run('formbuilder/fields/formhtml_wizardbtn', $data, $child_field['num_tab']);
                $str_output_3.=modules::run('formbuilder/fields/formhtml_wizardbtn_css', $data);
                break;
            case 40:
                //switch
                $data['main'] = $this->current_data_main;
                $str_output.=modules::run('formbuilder/fields/formhtml_switch', $data, $child_field['num_tab']);
                $str_output_3.=modules::run('formbuilder/fields/formhtml_switch_css', $data);
                break;
            case 41:
                //dyn checkbox
                $data['main'] = $this->current_data_main;
                $data['form_id'] = $this->saved_form_id;
                $str_output.=modules::run('formbuilder/fields/formhtml_dyncheckbox', $data, $child_field['num_tab']);
                $str_output_3.=modules::run('formbuilder/fields/formhtml_dyncheckbox_css', $data);
                break;
            case 42:
                //dyn radiobtn
                $data['main'] = $this->current_data_main;
                $data['form_id'] = $this->saved_form_id;
                $str_output.=modules::run('formbuilder/fields/formhtml_dynradiobtn', $data, $child_field['num_tab']);
                $str_output_3.=modules::run('formbuilder/fields/formhtml_dynradiobtn_css', $data);
                break;
            case 43:
                //date 2
                $data['main'] = $this->current_data_main;
                $data['form_id'] = $this->saved_form_id;
                $str_output.=modules::run('formbuilder/fields/formhtml_date2', $data, $child_field['num_tab']);
                $str_output_3.=modules::run('formbuilder/fields/formhtml_date2_css', $data);
                break;
            default:
                break;
        }

        $return = array();
        $return['output_html'] = $str_output;
        $return['output_css'] = $str_output_3;

        return $return;
    }

    /**
     * Forms::generate_previewpanel_getField()
     * 
     * @return 
     */
    protected function generate_previewpanel_getField($child_field) {
        $str_output = '';

        $data = array();
        $data = $this->current_data_form[intval($child_field['num_tab'])][$child_field['id']];
        $data['quick_options'] = $this->load->view('formbuilder/fields/templates/prevpanel_quickopts', $data, true);
        switch (intval($child_field['type'])) {
            case 6:
                //textbox
                $str_output.=$this->load->view('formbuilder/fields/templates/prevpanel_textbox', $data, true);
                break;
            case 7:
                //textarea
                $str_output.=$this->load->view('formbuilder/fields/templates/prevpanel_textarea', $data, true);
                break;
            case 8:
                //radio button
                $str_output.=$this->load->view('formbuilder/fields/templates/prevpanel_radiobtn', $data, true);
                break;
            case 9:
                //checkbox
                $str_output.=$this->load->view('formbuilder/fields/templates/prevpanel_checkbox', $data, true);
                break;
            case 10:
                //select
                $str_output.=$this->load->view('formbuilder/fields/templates/prevpanel_select', $data, true);
                break;
            case 11:
                //multiselect
                $str_output.=$this->load->view('formbuilder/fields/templates/prevpanel_multiselect', $data, true);
                break;
            case 12:
                //fileupload
                $str_output.=$this->load->view('formbuilder/fields/templates/prevpanel_fileupload', $data, true);
                break;
            case 13:
                //imageupload
                $str_output.=$this->load->view('formbuilder/fields/templates/prevpanel_imageupload', $data, true);
                break;
            case 14:
                //custom html
                $str_output.=$this->load->view('formbuilder/fields/templates/prevpanel_customhtml', $data, true);
                break;
            case 15:
                //password
                $str_output.=$this->load->view('formbuilder/fields/templates/prevpanel_password', $data, true);
                break;
            case 16:
                //slider
                $str_output.=$this->load->view('formbuilder/fields/templates/prevpanel_slider', $data, true);
                break;
            case 17:
                //range
                $str_output.=$this->load->view('formbuilder/fields/templates/prevpanel_range', $data, true);
                break;
            case 18:
                //spinner
                $str_output.=$this->load->view('formbuilder/fields/templates/prevpanel_spinner', $data, true);
                break;
            case 19:
                //captcha
                $str_output.=$this->load->view('formbuilder/fields/templates/prevpanel_captcha', $data, true);
                break;
            case 20:

                //submit button
                $str_output.=$this->load->view('formbuilder/fields/templates/prevpanel_submitbtn', $data, true);
                break;
            case 21:
                //hidden field
                $str_output.=$this->load->view('formbuilder/fields/templates/prevpanel_hiddeninput', $data, true);
                break;
            case 22:
                //star rating
                $str_output.=$this->load->view('formbuilder/fields/templates/prevpanel_ratingstar', $data, true);
                break;
            case 23:
                //color picker
                $str_output.=$this->load->view('formbuilder/fields/templates/prevpanel_colorpicker', $data, true);
                break;
            case 24:
                //date picker
                $str_output.=$this->load->view('formbuilder/fields/templates/prevpanel_datepicker', $data, true);
                break;
            case 25:
                //time picker
                $str_output.=$this->load->view('formbuilder/fields/templates/prevpanel_timepicker', $data, true);
                break;
            case 26:
                //date time
                $str_output.=$this->load->view('formbuilder/fields/templates/prevpanel_datetime', $data, true);
                break;
            case 27:
                //recaptcha
                $str_output.=$this->load->view('formbuilder/fields/templates/prevpanel_recaptcha', $data, true);
                break;
            case 28:
                //prepended text
                $str_output.=$this->load->view('formbuilder/fields/templates/prevpanel_preptext', $data, true);
                break;
            case 29:
                //appended text
                $str_output.=$this->load->view('formbuilder/fields/templates/prevpanel_appetext', $data, true);
                break;
            case 30:
                //prep app text
                $str_output.=$this->load->view('formbuilder/fields/templates/prevpanel_prepapptext', $data, true);
                break;
            case 32:
                //divider
                $str_output.=$this->load->view('formbuilder/fields/templates/prevpanel_divider', $data, true);
                break;
            case 33:
                //heading 1
                $str_output.=$this->load->view('formbuilder/fields/templates/prevpanel_heading1', $data, true);
                break;
            case 34:
                //heading 2
                $str_output.=$this->load->view('formbuilder/fields/templates/prevpanel_heading2', $data, true);
                break;
            case 35:
                //heading 3
                $str_output.=$this->load->view('formbuilder/fields/templates/prevpanel_heading3', $data, true);
                break;
            case 36:
                //heading 4
                $str_output.=$this->load->view('formbuilder/fields/templates/prevpanel_heading4', $data, true);
                break;
            case 37:
                //heading 5
                $str_output.=$this->load->view('formbuilder/fields/templates/prevpanel_heading5', $data, true);
                break;
            case 38:
                //heading 6
                $str_output.=$this->load->view('formbuilder/fields/templates/prevpanel_heading6', $data, true);
                break;
            case 39:
                //wizard buttons
                $str_output.=$this->load->view('formbuilder/fields/templates/prevpanel_wizardbtn', $data, true);
                break;
            case 40:
                //switch
                $str_output.=$this->load->view('formbuilder/fields/templates/prevpanel_switch', $data, true);
                break;
            case 41:
                //dyn checkbox
                $str_output.=$this->load->view('formbuilder/fields/templates/prevpanel_dyncheckbox', $data, true);
                break;
            case 42:
                //dyn radiobtn
                $str_output.=$this->load->view('formbuilder/fields/templates/prevpanel_dynradiobtn', $data, true);
                break;
            case 43:
                //date
                $str_output.=$this->load->view('formbuilder/fields/templates/prevpanel_datetime_2', $data, true);
                break;
            default:
                break;
        }

        $return = array();
        $return['output_html'] = $str_output;
        return $return;
    }

    /**
     * Forms::getChildren_innerGrid()
     * 
     * @return 
     */
    protected function getChildren_innerGrid($type) {

        $str_output = '';
        switch (intval($type)) {
            case 1:
                ob_start();
                ?>
                <td  data-maxpercent="100" data-blocks="12" width="100%">
                    <div class="uiform-items-container uiform-grid-inner-col">
                    </div>
                </td> 
                <?php
                $str_output.=ob_get_contents();
                ob_end_clean();
                break;
            case 2:
                ob_start();
                ?>
                <td  data-maxpercent="50" data-blocks="6" width="50%">
                    <div class="uiform-items-container uiform-grid-inner-col rkfm-bend-fcontainer-wrap">

                    </div>
                </td>
                <td  data-maxpercent="100" data-blocks="6" width="50%">
                    <div class="uiform-items-container uiform-grid-inner-col">

                    </div>
                </td>
                <?php
                $str_output.=ob_get_contents();
                ob_end_clean();
                break;
            case 3:
                ob_start();
                ?>
                <td  data-maxpercent="33.3" data-blocks="4" width="33.3%">
                    <div class="uiform-items-container uiform-grid-inner-col rkfm-bend-fcontainer-wrap">

                    </div>
                </td>
                <td  data-maxpercent="66.6" data-blocks="4" width="33.3%">
                    <div class="uiform-items-container uiform-grid-inner-col rkfm-bend-fcontainer-wrap">

                    </div>
                </td>
                <td  data-maxpercent="100" data-blocks="4" width="33.3%">
                    <div class="uiform-items-container uiform-grid-inner-col">

                    </div>
                </td>
                <?php
                $str_output.=ob_get_contents();
                ob_end_clean();
                break;
            case 4:
                ob_start();
                ?>
                <td data-maxpercent="25" data-blocks="3" width="25%">
                    <div class="uiform-items-container uiform-grid-inner-col rkfm-bend-fcontainer-wrap">

                    </div>
                </td>
                <td  data-maxpercent="50" data-blocks="3" width="25%">
                    <div class="uiform-items-container uiform-grid-inner-col rkfm-bend-fcontainer-wrap">

                    </div>
                </td>
                <td data-maxpercent="75" data-blocks="3" width="25%">
                    <div class="uiform-items-container uiform-grid-inner-col rkfm-bend-fcontainer-wrap">

                    </div>
                </td>
                <td  data-maxpercent="100" data-blocks="3" width="25%">
                    <div class="uiform-items-container uiform-grid-inner-col">

                    </div>
                </td>
                <?php
                $str_output.=ob_get_contents();
                ob_end_clean();
                break;
            case 5:
                ob_start();
                ?>
                <td  data-maxpercent="16.6" data-blocks="2" width="16.6%">
                    <div class="uiform-items-container uiform-grid-inner-col rkfm-bend-fcontainer-wrap">

                    </div>
                </td>
                <td  data-maxpercent="33.3" data-blocks="2" width="16.6%">
                    <div class="uiform-items-container uiform-grid-inner-col rkfm-bend-fcontainer-wrap">

                    </div>
                </td>
                <td  data-maxpercent="50" data-blocks="2" width="16.6%">
                    <div class="uiform-items-container uiform-grid-inner-col rkfm-bend-fcontainer-wrap">

                    </div>
                </td>
                <td  data-maxpercent="66.6" data-blocks="2" width="16.6%">
                    <div class="uiform-items-container uiform-grid-inner-col rkfm-bend-fcontainer-wrap">

                    </div>
                </td>
                <td  data-maxpercent="83.3" data-blocks="2" width="16.6%">
                    <div class="uiform-items-container uiform-grid-inner-col rkfm-bend-fcontainer-wrap">

                    </div>
                </td>
                <td  data-maxpercent="100" data-blocks="2" width="16.6%">
                    <div class="uiform-items-container uiform-grid-inner-col">

                    </div>
                </td>
                <?php
                $str_output.=ob_get_contents();
                ob_end_clean();
                break;
        }
        return $str_output;
    }

    /**
     * Forms::getChildren_genCol()
     * 
     * @return 
     */
    protected function getChildren_genCol($type, $key, $cols) {
        $content = '';
        /* grid type */
        switch (intval($type)) {
            case 1:
                switch (intval($key)) {
                    case 0:
                        $grid_maxpercent = '100';
                        $grid_blocks = '12';
                        $grid_width = '100%';
                        break;
                }
                break;
            case 2:
                switch (intval($key)) {
                    case 0:
                        $grid_maxpercent = '50';
                        $grid_blocks = '6';
                        $grid_width = '50%';
                        break;
                    case 1:
                        $grid_maxpercent = '100';
                        $grid_blocks = '6';
                        $grid_width = '50%';
                        break;
                }
                break;
            case 3:
                switch (intval($key)) {
                    case 0:
                        $grid_maxpercent = '33.3';
                        $grid_blocks = '4';
                        $grid_width = '33.3%';
                        break;
                    case 1:
                        $grid_maxpercent = '66.6';
                        $grid_blocks = '4';
                        $grid_width = '33.3%';
                        break;
                    case 2:
                        $grid_maxpercent = '100';
                        $grid_blocks = '4';
                        $grid_width = '33.3%';
                        break;
                }
                break;
            case 4:
                switch (intval($key)) {
                    case 0:
                        $grid_maxpercent = '25';
                        $grid_blocks = '3';
                        $grid_width = '25%';
                        break;
                    case 1:
                        $grid_maxpercent = '50';
                        $grid_blocks = '3';
                        $grid_width = '25%';
                        break;
                    case 2:
                        $grid_maxpercent = '75';
                        $grid_blocks = '3';
                        $grid_width = '25%';
                        break;
                    case 3:
                        $grid_maxpercent = '100';
                        $grid_blocks = '3';
                        $grid_width = '25%';
                        break;
                }
                break;
            case 5:
                switch (intval($key)) {
                    case 0:
                        $grid_maxpercent = '16.6';
                        $grid_blocks = '2';
                        $grid_width = '16.6%';
                        break;
                    case 1:
                        $grid_maxpercent = '33.3';
                        $grid_blocks = '2';
                        $grid_width = '16.6%';
                        break;
                    case 2:
                        $grid_maxpercent = '50';
                        $grid_blocks = '2';
                        $grid_width = '16.6%';
                        break;
                    case 3:
                        $grid_maxpercent = '66.6';
                        $grid_blocks = '2';
                        $grid_width = '16.6%';
                        break;
                    case 4:
                        $grid_maxpercent = '83.3';
                        $grid_blocks = '2';
                        $grid_width = '16.6%';
                        break;
                    case 5:
                        $grid_maxpercent = '100';
                        $grid_blocks = '2';
                        $grid_width = '16.6%';
                        break;
                }
                break;
        }

        ob_start();
        ?>
        <td  data-maxpercent="<?php echo $grid_maxpercent; ?>" data-blocks="<?php echo $grid_blocks; ?>" width="<?php echo $grid_width; ?>">
        <?php
        $content.=ob_get_contents();
        ob_end_clean();

        return $content;
    }

    protected function generate_previewpanel_getChildren($child_field) {
                    
    }
    
    protected function generate_form_getChildren($child_field) {
        $str_output = '';
        $str_output_2 = '';
        switch (intval($child_field['type'])) {
            case 1:
            case 2:
            case 3:
            case 4:
            case 5:
                
                if (intval($child_field['count_children']) >= 0) {
                    $str_output .= '<div id="zgfb_' . $child_field['id'] . '" class="zgpbf-gridsystem-cont">';
                    $str_output .= '<div class="sfdc-container-fluid">';
                    $str_output .= '<div class="sfdc-row">';
                    $count_str = 0;
                    if(isset($child_field['inner'])){
                    foreach ($child_field['inner'] as $key => $value) {
                        $str_output .= '<div data-zgpb-blocknum="' . $value['num_tab'] . '" class="zgpb-fl-gs-block-style sfdc-col-sm-' . $value['cols'] . '">';
                        if ($count_str === $key) {
                            $str_output .= '<div class="zgpb-fl-gs-block-inner">';
                        } else {
                            $str_output .= '<div class="zgpb-fl-gs-block-inner">';
                        }
                        if (!empty($value['children'])) {
                            foreach ($value['children'] as $key2 => $value2) {
                                //get field
                                $get_data = array();
                                $str_output .= '<div class="">';
                                if (isset($value2['iscontainer']) && intval($value2['iscontainer']) === 1) {
                                    $get_data = $this->generate_form_getChildren($value2);
                                    $str_output .= $get_data['output_html'];
                                    $str_output_2 .= $get_data['output_css'];
                                } else {
                                    $get_data = $this->generate_form_getField($value2);
                                    $str_output .= $get_data['output_html'];
                                    $str_output_2 .= $get_data['output_css'];
                                }
                                $str_output .= '</div>';
                            }
                        }
                        $str_output .= '</div>';
                        $str_output .= '</div>';
                    }
                }
                    $str_output .= '</div>';
                    $str_output .= '</div>';
                    $str_output .= '</div>';
                }
            
                $data = array();
                $data = $this->gen_post_src[$child_field['num_tab']][$child_field['id']];
                $str_output_2 .= modules::run('formbuilder/fields/posthtml_gridsystem_css', $data);
                
                break;
            case 31:
                /*panel*/
                $temp_str_output='';
                if (isset($child_field['count_children']) && intval($child_field['count_children']) > 0) {
                    $count_str=0;
                    if(isset($child_field['inner'])){
                    foreach ($child_field['inner'] as $key => $value) {
            
                        if (!empty($value['children'])) {
                            foreach ($value['children'] as $key2 => $value2) {
                                //get field
                                $get_data = array();
            
                                if (isset($value2['iscontainer']) && intval($value2['iscontainer']) === 1) {
                                    $get_data = $this->generate_form_getChildren($value2);
                                    $temp_str_output.=$get_data['output_html'];
                                    $str_output_2.=$get_data['output_css'];
                                } else {
                                    $get_data = $this->generate_form_getField($value2);
                                    $temp_str_output.=$get_data['output_html'];
                                    $str_output_2.=$get_data['output_css'];
                                }
            
                            }
                        }
            
                    }
                    
                }
                }
                
                $temp_content='';
                    $data_tmp = array();
                    $data_tmp = $this->current_data_form[intval($child_field['num_tab'])][$child_field['id']];
                    $temp_content=$this->load->view('formbuilder/fields/formhtml_panelfld',$data_tmp, true);
                    $str_output.= str_replace("[[%%fields%%]]", $temp_str_output, $temp_content);
                    $str_output_2.=$this->load->view('formbuilder/fields/formhtml_panelfld_css', $data_tmp, true);
                break;
            default:
                break;
        }
        $return = array();
        $return['output_html'] = $str_output;
        $return['output_css'] = $str_output_2;

        return $return;
    }
    
    protected function generate_admin_form_getChildren($child_field) {
        $str_output = '';
        $str_output_2 = '';
        
        $grid_order = array(1 => 'one', 2 => 'two', 3 => 'three', 4 => 'four', 5 => 'five', 6 => 'six');
        
        switch (intval($child_field['type'])) {
            case 1:
            case 2:
            case 3:
            case 4:
            case 5:

                if (intval($child_field['count_children']) >= 0) {

                    ob_start();
                    ?>
                    <div id="<?php echo $child_field['id']; ?>" data-typefield="<?php echo intval($child_field['type']); ?>" data-iscontainer="1"  class="zgpb-gridsytem-box zgpb-field-template uiform-field zgpb-gridsystem-<?php echo $grid_order[intval($child_field['type'])]; ?>">
                        <div class="sfdc-container-fluid">
                            <div class="sfdc-row">
                    <?php
                    $str_output .= ob_get_contents();
                    ob_end_clean();
                    if (isset($child_field['inner'])) {

                        $count_str = 1;
                        $count_total = count($child_field['inner']);
                        
                        $tmp_col_rest=12;
                        
                        
                        foreach ($child_field['inner'] as $key => $value) {
                            
                            //controling 12 cols
                            $tmp_col_rest2 = $tmp_col_rest - abs($value['cols']);
                            if($tmp_col_rest2<12 && $tmp_col_rest2 > 0){
                                
                                $tmp_col = abs($value['cols']);
                                $tmp_col_rest = $tmp_col_rest2;
                                
                            }else{
                                $tmp_col = $tmp_col_rest;
                            }
                            
                            $str_output .= '<div class="zgpb-fl-gs-block-style sfdc-col-sm-' . $tmp_col . '" data-zgpb-blocknum="' . $value['num_tab'] . '" data-zgpb-width="" data-zgpb-blockcol="' . $tmp_col . '">';
                            $str_output .= '<div class="uiform-items-container zgpb-fl-gs-block-inner">';


                            if (!empty($value['children'])) {
                                foreach ($value['children'] as $key2 => $value2) {
                                    //get field
                                    $get_data = array();
            
                                    if (isset($value2['iscontainer']) && intval($value2['iscontainer']) === 1) {
                                        $get_data = $this->generate_admin_form_getChildren($value2);
                                        $str_output .= $get_data['output_html'];
                                    } else {
                                        $get_data = $this->generate_previewpanel_getField($value2);
                                        $str_output .= $get_data['output_html'];
                                    }
            
                                }
                            }

                            $str_output .= '</div>';

                            if ($count_str < $count_total) {
                                ob_start();
                                ?>
                                            <div class="zgpb-fl-gridsystem-opt">
                                                <div data-zgpb-side="1" class="zgpb-fl-gd-drag-line zgpb-fl-gd-drag-line-right">
                                                    <div class="zgpb-fl-gd-opt-icon-handler"></div>
                                                </div>
                                            </div>
                                <?php
                                $str_output .= ob_get_contents();
                                ob_end_clean();
                            } else {
                                ob_start();
                                ?>
                                            <div class="zgpb-fl-gridsystem-opt"></div>
                                <?php
                                $str_output .= ob_get_contents();
                                ob_end_clean();
                            }

                            $str_output .= '</div>';
                            $count_str++;
                        }
                        
                    }

                    ob_start();
                    ?>
                            </div>
                        </div>
                    </div>
                                <?php
                                $str_output .= ob_get_contents();
                                ob_end_clean();
                            }
                            break;
            case 31:
                /*panel*/
                 ob_start();
                    ?>
                    <div id="<?php echo $child_field['id'];?>"  data-typefield="31" data-iscontainer="1" class="uiform-panelfld uiform-field  uiform-field-childs zgpb-field-template">
            <div class="uiform-field-wrap">
                 <div class="uifm-input31-wrap">
                                <div class="uifm-input31-container">
                                     <div class="rkfm-inp18-row">
                                         <div class="rkfm-inp18-col-sm-2">
                                             <div class="uifm-inp31-txthtml-content"></div>
                                         </div>
                                         <div class="rkfm-inp18-col-sm-10">
                                             <div class="uifm-input31-main-wrap">
                                                 <div class="uiform-items-container uiform-grid-inner-col zgpb-fl-gs-block-inner">
                                                   
                    <?php
                    $str_output.=ob_get_contents();
                    ob_end_clean();
                    if(isset($child_field['inner'])){
                    $count_str=0;
                    $count_total=count($child_field['inner']);
                    if(isset($child_field['inner'])){
                    foreach ($child_field['inner'] as $key => $value) {
            
                        if (!empty($value['children'])) {
                            foreach ($value['children'] as $key2 => $value2) {
                                //get field
                                $get_data = array();
            
                                if (isset($value2['iscontainer']) && intval($value2['iscontainer']) === 1) {
                                    $get_data = $this->generate_admin_form_getChildren($value2);
                                    $str_output.=$get_data['output_html'];
                                   
                                } else {
                                    $get_data = $this->generate_previewpanel_getField($value2);
                                    $str_output.=$get_data['output_html'];
                                    
                                }
                               
                            }
                        }
            
                        $count_str++;
                    }
                    }
                    }
                    ob_start();
                    ?>
                                                        </div>
                                                 
                                                        </div>
                                                    </div>
                                                </div>
                                           </div>
                                       </div>
                           
                       </div>
                   </div>
                    <?php
                    $str_output.=ob_get_contents();
                    ob_end_clean();
                    
                break;
            default:
                break;
        }
        $return = array();
        $return['output_html'] = $str_output;
            

        return $return;
    }

     /**
     * Forms::generate_form_container()
     * 
     * @return 
     */
    public function generate_form_container($id, $numtab, $str_output) {
        $data = array();
        if (intval($numtab) > 1) {
            $data1 = array();
            $data1['tab_title'] = $this->current_data_steps['tab_title'];
            $data1['tab_theme'] = $this->current_data_wizard;
            $data['form_tab_head'] = $this->load->view('formbuilder/forms/formhtml_tabheader', $data1,true);
            $data2 = array();
            $data['form_tab_footer'] = $this->load->view('formbuilder/forms/formhtml_tabfooter', $data2,true);
        }

        $data['tab_count'] = $numtab;
        $data['form_content'] = $str_output;
        $data['form_id'] = $id;
        $data['wizard'] = $this->current_data_wizard;
        $data['onsubm'] = $this->current_data_onsubm;
        $data['main'] = $this->current_data_main;
        $data['clogic'] = $this->saveform_clogic;
        return $this->load->view('formbuilder/forms/formhtml_form', $data,true);
    }

    public function generate_admin_form_container($id, $numtab, $str_output) {
            
    }
    
    public function generate_previewpanel_container($id,$numtab, $str_output) {
        $data = array();
        if (intval($numtab) > 1) {
            $data1 = array();
            $data1['tab_title'] = $this->current_data_steps['tab_title'];
            $data1['tab_theme'] = $this->current_data_wizard;
            $data['form_tab_head'] = $this->load->view('formbuilder/forms/previewpanel_tabheader', $data1,true);
            $data2 = array();
            $data['form_tab_footer'] = $this->load->view('formbuilder/forms/previewpanel_tabfooter', $data2,true);
            }
        $data['tab_count'] = $numtab;
        $data['form_content'] = $str_output;
        $data['form_id'] = $id;
        $data['wizard'] = $this->current_data_wizard;
        $data['onsubm'] = $this->current_data_onsubm;
        $data['main'] = $this->current_data_main;
        $data['clogic'] = $this->saveform_clogic;
        return $this->load->view('formbuilder/forms/previewpanel_form', $data,true);
    }
    
    /**
     * Forms::generate_previewpanel_tabContent()
     * 
     * @return 
     */
    public function generate_previewpanel_tabContent($tab_cont_num, $tabindex, $str_output) {
        $output = '';
        $data = array();
        $data['tabindex'] = $tabindex;
        $data['tab_html_fields'] = $str_output;
        //if (intval($tab_cont_num) > 1) {
        //apply function
        $output.=$this->load->view('formbuilder/forms/previewpanel_tabcontainer', $data, true);
        /* } else {
          $output.=$str_output;
          } */
        return $output;
    }

    /**
     * Forms::generate_form_tabContent()
     * 
     * @return 
     */
    public function generate_form_tabContent($tab_cont_num, $tabindex, $str_output) {
        $output = '';
        $data = array();
        $data['tabindex'] = $tabindex;
        $data['tab_html_fields'] = $str_output;
        if (intval($tab_cont_num) > 1) {
            //apply function
            $output.=$this->load->view('formbuilder/forms/formhtml_tabcontainer', $data, true);
        } else {
            $output.=$str_output;
        }
        return $output;
    }

    /**
     * Forms::save_form_clogic()
     * 
     * @return 
     */
    public function save_form_clogic() {
        $clogic_src = $this->saveform_clogic;
        if (!empty($clogic_src)) {
            //get fires
            $fields_fire = array();
            foreach ($clogic_src['cond'] as $key => $value) {
                foreach ($value['list'] as $key2 => $value2) {
                    if (!empty($value2)) {
                        if (!isset($fields_fire[$value2['field_fire']]['list'][$value['field_cond']])) {
                            $fields_fire[$value2['field_fire']]['list'][] = $value['field_cond'];
                        }
                    } else {
                        unset($clogic_src['cond'][$key]['list'][$key2]);
                    }
                }
            }
            $this->saveform_clogic = $clogic_src;
            // field fires
            $logic_field_fire = array();
            foreach ($fields_fire as $key => $value) {
                $temp_logic = array();
                $temp_logic['field_fire'] = $key;
                $tmp_list = array();
                foreach ($value['list'] as $value2) {
                    $tmp_list[] = array('field_cond' => $value2);
                }
                $temp_logic['list'] = $tmp_list;
                $logic_field_fire[$key] = $temp_logic;
            }

            $clogic_src['fire'] = $logic_field_fire;
            $this->saveform_clogic = $clogic_src;
        }
    }

    /**
     * Forms::save_data_fields()
     * 
     * @return 
     */
    public function save_data_fields($form_id = null) {

        /* check for enabled field for reports */
        $check_rec_querys = $this->model_fields->queryGetQtyFieldsEnabled($form_id);
        if (intval($check_rec_querys) === 1) {
            
            //getting ones enabled for showing on list records
            $tmp_query_list = array();
            $rec_querys_list = $this->model_fields->queryGetListFieldsEnabled($form_id);
            foreach ($rec_querys_list as $value) {
                $tmp_query_list[] = $value->fmf_uniqueid;
                
            }
            //storing rec orders
            $tmp_recorder_list = array();
            $rec_querys_list = $this->model_fields->queryGetListFieldsById($form_id);
            foreach ($rec_querys_list as $value) {
                
                $tmp_recorder_list[$value->fmf_uniqueid] = $value->order_rec;
            }
                
        }

        //deleting form
        $this->db->where('form_fmb_id', $form_id)->delete($this->model_fields->table);
        //creating again
        $data_form = $this->model_forms->getFormById($form_id);
        $fmb_data = json_decode($data_form->fmb_data, true);
        //$tab_cont=$fmb_data['steps']['tab_cont'];
        $steps_src = $fmb_data['steps_src'];


        $set_rec_querys = 0;
        if (!empty($steps_src)) {
            foreach ($steps_src as $tabindex => $fields) {
                if (!empty($fields)) {
                    foreach ($fields as $key => $value) {
                        $data = array();
                        $data['fmf_uniqueid'] = $value['id'];
                        switch (intval($value['type'])) {
                            case 6:
                            case 7:
                            case 8:
                            case 9:
                            case 10:
                            case 11:
                            case 12:
                            case 13:
                            case 15:
                            case 16:case 17:case 18:
                            case 21:case 22:case 23:case 24:case 25:case 26:
                            case 28:case 29:case 30:case 40:case 41:case 42:
                                //assign selected fields to the report
                                if (intval($check_rec_querys) === 0 && $set_rec_querys < 5) {
                                    $data['fmf_status_qu'] = 1;
                                    $set_rec_querys++;
                                } elseif (intval($check_rec_querys) === 1) {
                                    if (in_array($value['id'], $tmp_query_list)) {
                                        $data['fmf_status_qu'] = 1;
                                    }
                                }
                                $data['fmf_fieldname'] = $value['field_name'];
                                $data['order_frm'] = $value['order_frm'];
                                
                                if(isset($tmp_recorder_list[$value['id']]) && intval($tmp_recorder_list[$value['id']])>0){
                                    $data['order_rec'] = $tmp_recorder_list[$value['id']];
                                }else{
                                    $data['order_rec'] = $value['order_frm'];
                                }
                                break;
                            case 19:case 20:case 27:
                                //asigning order to fields
                                $data['order_frm'] = $value['order_frm'];
                                break;    
                        }

                        $data['fmf_data'] = json_encode($value);
                        $data['type_fby_id'] = $value['type'];
                        $data['form_fmb_id'] = $form_id;
                        /* clean previous field */

                        $this->db->set($data);
                        $this->db->insert($this->model_fields->table);

                        if (isset($value['clogic']) && intval($value['clogic']['show_st']) === 1) {
                            $tmp_clogic = array();
                            $tmp_clogic['field_cond'] = $value['id'];
                            $tmp_clogic['action'] = $value['clogic']['f_show'];

                            foreach ($value['clogic']['list'] as $key2 => $value2) {
                                if (empty($value2)) {
                                    unset($value['clogic']['list'][$key2]);
                                }
                            }
                            $tmp_clogic['list'] = array_filter($value['clogic']['list']);
                            $tmp_clogic['req_match'] = (intval($value['clogic']['f_all']) === 1) ? count($value['clogic']['list']) : 1;
                            $this->saveform_clogic['cond'][] = $tmp_clogic;
                        }
                    }
                }
            }
        }
    }

    /**
     * Forms::generate_form_html()
     * 
     * @return 
     */
    public function generate_form_html($form_id = null) {
        /*$data_form = $this->model_forms->getFormById($form_id);
        $fmb_data = json_decode($data_form->fmb_data, true);*/
        //all fields position
        $tab_cont = $this->current_data_tab_cont;
        $this->gen_post_src = $this->current_data_form;
        $tab_cont_num = $this->current_data_num_tabs;
        //generating

        $str_output_2 = '';
        $str_output_tab = '';
        
        foreach ($tab_cont as $key => $value) {
            //tabs
            $str_output = '';
            if (!empty($value['content'])) {
                foreach ($value['content'] as $key2 => $value2) {
                    $get_data = array();

                    //fields
                    if (isset($value2['iscontainer']) && intval($value2['iscontainer']) === 1) {
                        $get_data = $this->generate_form_getChildren($value2);
                        $str_output.=$get_data['output_html'];
                        $str_output_2.=$get_data['output_css'];
                    } else {
                        $get_data = $this->generate_form_getField($value2);
                        $str_output.=$get_data['output_html'];
                        $str_output_2.=$get_data['output_css'];
                    }
                }
            }

            //set tab container
            $str_output_tab.=$this->generate_form_tabContent($tab_cont_num, $key, $str_output);
            //jump if it is one
            if (intval($tab_cont_num) === 1) {
                break 1;
            }
        }

        //generate form css
        $str_output_2.=$this->generate_form_css($form_id);
        if ($tab_cont_num > 1) {
            $str_output_2.=$this->generate_form_tab_css($form_id);
        }

        $return = array();
        $return['output_html'] = $this->generate_form_container($form_id, $tab_cont_num, $str_output_tab);
        $return['output_css'] = $str_output_2;

        return $return;
    }
    
    
    public function generate_admin_form_html($form_id = null) {
        $data_form = $this->model_forms->getFormById($form_id);
        if(empty($data_form)){
            return;
        }
        $fmb_data = json_decode($data_form->fmb_data, true);
        //all fields position
        $tab_cont = $fmb_data['steps']['tab_cont'];
       
        //generating

                            
        $str_output_tab = '';
        $tab_cont_num = $fmb_data['num_tabs'];
        
        if(!empty($tab_cont)){
            foreach ($tab_cont as $key => $value) {
                //tabs
                $str_output = '';
                if (!empty($value['content'])) {
                    foreach ($value['content'] as $key2 => $value2) {
                        $get_data = array();

                        //fields
                        if (isset($value2['iscontainer']) && intval($value2['iscontainer']) === 1) {
                            $get_data = $this->generate_admin_form_getChildren($value2);
                            $str_output.=$get_data['output_html'];

                        } else {
                            $get_data = $this->generate_previewpanel_getField($value2);
                            $str_output.=$get_data['output_html'];
                        }
                    }
                }

                //set tab container
                $str_output_tab.=$this->generate_previewpanel_tabContent($tab_cont_num, $key, $str_output);
                //jump if it is one
                if (intval($tab_cont_num) === 1) {
                    break 1;
                }
            }
        }else{
            //set tab container
                $str_output_tab.=$this->generate_previewpanel_tabContent(0, 0, "");
        }
        
                            
        $return = array();
        $return['output_html'] = $this->generate_previewpanel_container($form_id, $tab_cont_num, $str_output_tab);
                            

        return $return['output_html'];
    }
    
    
    
    /**
     * Forms::generate_previewpanel_html()
     * 
     * @return 
     */
    public function generate_previewpanel_html($data) {
        $fmb_data = $data['fmb_data'];
        //all fields position
        $tab_cont = $fmb_data['steps']['tab_cont'];
        // all data fields
        $steps_src = $fmb_data['steps_src'];
        $this->current_data_form = $steps_src;
        $this->current_data_steps = $fmb_data['steps'];
        $this->current_data_skin = $fmb_data['skin'];
        $this->current_data_wizard = ($fmb_data['wizard']) ? $fmb_data['wizard'] : array();
        $this->current_data_onsubm = ($fmb_data['onsubm']) ? $fmb_data['onsubm'] : array();
        $this->current_data_main = ($fmb_data['main']) ? $fmb_data['main'] : array();
        //generating
       
        $str_output_tab = '';
        $tab_cont_num = $fmb_data['num_tabs'];
                            
        if(!empty($tab_cont)){
            foreach ($tab_cont as $key => $value) {
                //tabs
                $str_output = '';
                if (!empty($value['content'])) {
                    foreach ($value['content'] as $key2 => $value2) {
                        $get_data = array();
                        //fields
                        if (isset($value2['iscontainer']) && intval($value2['iscontainer']) === 1) {
                            //$get_data = $this->generate_previewpanel_getChildren($value2);

                            $get_data = $this->generate_admin_form_getChildren($value2);

                            $str_output.=$get_data['output_html'];
                        } else {
                            $get_data = $this->generate_previewpanel_getField($value2);
                            $str_output.=$get_data['output_html'];

                        }

                    }
                }

                //set tab container
                $str_output_tab.=$this->generate_previewpanel_tabContent($tab_cont_num, $key, $str_output);
                //jump if it is one
                if (intval($tab_cont_num) === 1) {
                    break 1;
                }
            } 
        }else{
            //there nos data
             //set tab container
                $str_output_tab.=$this->generate_previewpanel_tabContent(0, 0, "");
        }
        
         
            
        
        $return = array();
        $return['output_html'] = $this->generate_previewpanel_container(null, $str_output_tab,$str_output_tab);
       

        return $return;
    }
    
    /**
     * Forms::export_form()
     * 
     * @return 
     */
    public function export_form() {
        $data = array();
        $data['list_forms'] = $this->model_forms->getListForms();
        $this->template->loadPartial('layout', 'forms/export_form', $data);
    }

    /**
     * Forms::ajax_load_export_form()
     * 
     * @return 
     */
    public function ajax_load_export_form() {
        $form_id = (isset($_POST['form_id']) && $_POST['form_id']) ? Uiform_Form_Helper::sanitizeInput($_POST['form_id']) : 0;
        $data_form = $this->model_forms->getFormById($form_id);
        $data_exp = array();
        $data_exp['fmb_data'] = $data_form->fmb_data;
        $data_exp['fmb_html_backend'] = $data_form->fmb_html_backend;
        $data_exp['fmb_name'] = $data_form->fmb_name;
        $code_export = Uiform_Form_Helper::base64url_encode(serialize($data_exp));
        echo $code_export;
        die();
    }

    /**
     * Forms::generate_form_css()
     * 
     * @return 
     */
    public function generate_form_css($form_id = null) {
        $data = array();
        $data['idform'] = $form_id;
        $data['skin'] = $this->current_data_skin;
        return $this->load->view('formbuilder/forms/formhtml_css_form', $data, true);
    }

    /**
     * Forms::generate_form_tab_css()
     * 
     * @return 
     */
    public function generate_form_tab_css($form_id = null) {
        $data = array();
        $data['idform'] = $form_id;
        $data['wizard'] = $this->current_data_wizard;
        return $this->load->view('formbuilder/forms/formhtml_css_wizard', $data, true);
    }

    /**
     * Forms::ajax_load_form()
     * 
     * @return 
     */
    public function ajax_load_form() {
        $json = array();
        $form_id = (isset($_POST['form_id'])) ? Uiform_Form_Helper::sanitizeInput(trim($_POST['form_id'])) : '';

        $data_form = $this->model_forms->getFormById($form_id);
        $data_form->fmb_data = json_decode($data_form->fmb_data);
        $json['data'] = $data_form;
         //temp
        $tmp_addon_names = $this->model_addon->getActiveAddonsNamesOnBack($form_id);
        
        $tmp_addon = array();
        
        foreach ($tmp_addon_names as $key => $value) {
            $tmp_data = $this->model_addon_details->getAddonDataByForm($value,$form_id);
            if(!empty($tmp_data)){
                $tmp_addon[$value] = json_decode($tmp_data->adet_data,true);
            }
            
        }
        
        $json['addons'] = $tmp_addon; 
        header('Content-Type: application/json');
        echo json_encode($json);
        die();
    }

    /**
     * Forms::list_uiforms()
     * 
     * @return 
     */
    public function list_uiforms($offset = 0) {
        //generate cache
            //check if cache exist
            
        $this->load->library('cache');
            $data1 = $this->cache->get('addon_back');
            $data2 = $this->cache->get('addon_front');
            $data3 = $this->cache->get('addon_routes');
                            
            if( !file_exists(FCPATH .'application/cache/addon_back.cache') 
                    &&  !file_exists(FCPATH .'application/cache/addon_front.cache') 
                    && !file_exists(FCPATH .'application/cache/addon_routes.cache')   
                    ){
               if (empty($data1) ) {
                    $tmp_addon=$this->addon->load_addonsbyBack();
                    $this->cache->write($tmp_addon, 'addon_back');
               }

              if (empty($data2) ) {
                   $tmp_addon=$this->addon->load_addonsByFront();
                    $this->cache->write($tmp_addon, 'addon_front');
               } 

               if (empty($data3) ) {
                   $tmp_addon=$this->addon->load_addRoutes();
                    $this->cache->write($tmp_addon, 'addon_routes');
               } 
                            
               redirect(site_url() . 'formbuilder/forms/list_uiforms');
               
            } 
        
        
       $filter_data= get_option('zgfm_listform_searchfilter',true);
      
        $data2=array();
        if(empty($filter_data)){
            $data2['per_page'] = intval($this->per_page);
            $data2['search_txt']='';
            $data2['orderby']='asc';
        }else{
            $data2['per_page']=intval($filter_data['per_page']);
            $data2['search_txt']=$filter_data['search_txt'];
            $data2['orderby']=$filter_data['orderby'];
        }
        
        $offset = (isset($_GET['offset'])) ? Uiform_Form_Helper::sanitizeInput($_GET['offset']) : 0;
        $data2['offset']=$offset;
        
        $this->template->loadPartial('layout', 'forms/list_forms', $data2);                 
    }
    
    
    
     function ajax_formlist_sendfilter() {
      
        $data_filter = (isset($_POST['data_filter']) && $_POST['data_filter']) ? $_POST['data_filter'] : '';
        
        $opt_save = (isset($_POST['opt_save']) && $_POST['opt_save']) ? Uiform_Form_Helper::sanitizeInput($_POST['opt_save']) : 0;
        $opt_offset = (isset($_POST['opt_offset']) && $_POST['opt_offset']) ? Uiform_Form_Helper::sanitizeInput($_POST['opt_offset']) : 0;
        
        
        parse_str($data_filter, $data_filter_arr);
        
        $per_page=isset($data_filter_arr['zgfm-listform-pref-perpage'])?$data_filter_arr['zgfm-listform-pref-perpage']:'';
        $search_txt=isset($data_filter_arr['zgfm-listform-pref-search'])?$data_filter_arr['zgfm-listform-pref-search']:'';
        $orderby=isset($data_filter_arr['zgfm-listform-pref-orderby'])?$data_filter_arr['zgfm-listform-pref-orderby']:'';
        
        
        $data=array();
        $data['per_page']=$per_page;
        $data['search_txt']=$search_txt;
        $data['orderby']=$orderby;
        
        if(intval($opt_save)===1){
            update_option( 'zgfm_listform_searchfilter', $data);
        }
        
        
        $data['segment']=0;
        $data['offset']=$opt_offset;
        
        //self::$_models['formbuilder']['form']->getListFormsFiltered($data);
        
        $result=$this->ajax_formlist_refresh($data);
        
        $json = array();
        $json['content']=$result;
        
        header('Content-Type: application/json');
        echo json_encode($json);
        die();
    }
    
    function ajax_formlist_refresh($data){
        
        $this->load->library('pagination');
         
        $offset = $data['offset'];
         
        //list all forms
        $config = array();
        $config['base_url'] = site_url() . 'formbuilder/forms/list_uiforms';
        $config['total_rows'] = $this->model_forms->CountForms();
        $config['per_page'] = $data['per_page'];
        $config['first_link'] = 'First';
        $config['last_link'] = 'Last';
        $config['full_tag_open'] = '<ul class="pagination pagination-sm">';
        $config['full_tag_close'] = '</ul>';
        $config['first_tag_open'] = '<li>';
        $config['first_tag_close'] = '</li>';
        $config['last_tag_open'] = '<li>';
        $config['last_tag_close'] = '</li>';
        $config['cur_tag_open'] = '<li class="zgfm-pagination-active"><span>';
        $config['cur_tag_close'] = '</span></li>';
        $config['next_tag_open'] = '<li>';
        $config['next_tag_close'] = '</li>';
        $config['prev_tag_open'] = '<li>';
        $config['prev_tag_close'] = '</li>';
        $config['num_tag_open'] = '<li>';
        $config['num_tag_close'] = '</li>';
        $config['page_query_string'] = true;
        $config['query_string_segment'] = 'offset';

        $this->pagination->initialize($config);
        // If the pagination library doesn't recognize the current page add:
        $this->pagination->cur_page = $offset;
        
        
        $data2=array();
        $data2['per_page']=$data['per_page'];
        $data2['segment']=$offset;
        $data2['search_txt']=$data['search_txt'];
        $data2['orderby']=$data['orderby'];
        
        $data3=array();
        $data3['query'] = $this->model_forms->getListFormsFiltered($data2);
        $data3['pagination'] = $this->pagination->create_links();
        return $this->load->view('formbuilder/forms/list_forms_table', $data3, true);                   
        
    }
    
    
    /**
     * Forms::edit_uiform()
     * 
     * @return 
     */
    public function edit_uiform() {
        $data = array();
        echo $this->load->view('formbuilder/forms/edit_form', $data, true);
    }

    /**
     * Forms::create_uiform()
     * 
     * @return 
     */
    public function create_uiform() {
        require_once( FCPATH . 'libs/styles-font-menu/plugin.php');
        $objsfm = new SFM_Plugin();

        $data = array();
        $data['form_id'] = (isset($_GET['form_id']) && $_GET['form_id']) ? Uiform_Form_Helper::sanitizeInput(trim($_GET['form_id'])) : 0;
        $data['action'] = 'create_uiform';
        $data['obj_sfm'] = $objsfm;
        $this->template->loadPartial('layout-editform', 'forms/create_form', $data);
    }

    /**
     * Forms::preview_fields()
     * 
     * @return 
     */
    public function preview_fields() {
        $data = array();
        echo $this->load->view('formbuilder/forms/preview_fields', $data, true);
    }

    /**
     * Forms::getcode()
     * 
     * @return 
     */
    public function getcode() {

                            
        $id_form = (isset($_POST['form_id'])) ? Uiform_Form_Helper::sanitizeInput(trim($_POST['form_id'])) : '';
                
        $query = $this->db->get_where($this->model_forms->table, array('fmb_id' => $id_form), 1);
        if ($query->num_rows() === 1) {
                            
            $data = array();
            //get data from form
            $form_data = $this->model_forms->getFormById_2($id_form);
            $form_data_onsubm = json_decode($form_data->fmb_data2, true);
                
            $onload_scroll = (isset($form_data_onsubm['main']['onload_scroll'])) ? $form_data_onsubm['main']['onload_scroll'] : '1';
                            
            $preload_noconflict = (isset($form_data_onsubm['main']['preload_noconflict'])) ? $form_data_onsubm['main']['preload_noconflict'] : '1';    
                            
            $temp=array();
            $temp['id_form']=$id_form;
            $temp['site_url']=site_url();
            $temp['base_url']=base_url();
            $temp['onload_scroll']=$onload_scroll;
            $temp['preload_noconflict']=$preload_noconflict;
            $data_addon_front = $this->cache->get('addon_front');
            $temp['addon']= json_encode($data_addon_front);
            $data['script'] = escape_text($this->load->view('formbuilder/forms/get_code_widget', $temp, true));

            $content = '';
            $content = site_url() . 'formbuilder/frontend/viewform/?form=' . $id_form;
            $data['url'] = escape_text($content);

            $temp=array();
            $temp['url_form']=$data['url'].'&lmode=1';
            $temp['base_url']=base_url();
            $temp['form_id']=$id_form;
            $data_addon_front = $this->cache->get('addon_front');
            $temp['addon']= json_encode($data_addon_front);
            $data['iframe'] = escape_text($this->load->view('formbuilder/forms/get_code_iframe', $temp, true));
                            
            $json = array();
            $json['html_title'] = __('Shortcodes','FRocket_admin');
            $json['html'] = $this->load->view('formbuilder/forms/getcode', $data, true);
            //return data to ajax callback
            header('Content-type: text/html');
            echo json_encode($json);                
        } else {

        }
    }

    /**
     * Forms::form_success()
     * 
     * @return 
     */
    public function form_success() {


        $data = array();
                $id_form = (isset($_POST['form_id'])) ? Uiform_Form_Helper::sanitizeInput(trim($_POST['form_id'])) : '';
        
        $query = $this->db->get_where($this->model_forms->table, array('fmb_id' => $id_form), 1);
        if ($query->num_rows() === 1) {
            $data = array();
             //get data from form
            $form_data = $this->model_forms->getFormById_2($id_form);
            $form_data_onsubm = json_decode($form_data->fmb_data2, true);
                
            $onload_scroll = (isset($form_data_onsubm['main']['onload_scroll'])) ? $form_data_onsubm['main']['onload_scroll'] : '1';
                            
            $preload_noconflict = (isset($form_data_onsubm['main']['preload_noconflict'])) ? $form_data_onsubm['main']['preload_noconflict'] : '1';    
                            
            $temp=array();
            $temp['id_form']=$id_form;
            $temp['site_url']=site_url();
            $temp['base_url']=base_url();
            $temp['onload_scroll']=$onload_scroll;
            $temp['preload_noconflict']=$preload_noconflict;
            $data_addon_front = $this->cache->get('addon_front');
            $temp['addon']= json_encode($data_addon_front);
            $data['script'] = escape_text($this->load->view('formbuilder/forms/get_code_widget', $temp, true));
            $data['id_form'] = $id_form;
            $content = '';
            $content = site_url() . 'formbuilder/frontend/viewform/?form=' . $id_form;
            $data['url'] = escape_text($content);
             
            $temp=array();
            $temp['url_form']=$data['url'].'&lmode=1';
            $temp['base_url']=base_url();
            $temp['form_id']=$id_form;
            $data_addon_front = $this->cache->get('addon_front');
            $temp['addon']= json_encode($data_addon_front);
            $data['iframe'] = escape_text($this->load->view('formbuilder/forms/get_code_iframe', $temp, true));

          //  echo $this->load->view('formbuilder/forms/form_success', $data, true);
            
            
            $json = array();
            $json['html_title'] = __('Shortcodes','FRocket_admin');
            $json['html'] = $this->load->view('formbuilder/forms/form_success', $data, true);

            //return data to ajax callback
            header('Content-type: text/html');
            echo json_encode($json);
            die();
        } else {

        }
    }

}

