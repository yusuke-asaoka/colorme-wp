<?php 
/*
Plugin Name: colorme-check
Description: カラーミーショップのアイテムをチェックするプラグインです。
Author: Yusuke Asaoka
Version: 1.5
Author URI: http://cinca.asis/
*/

$Colorme_check = new Colorme_check();

class Colorme_check
{
    public $colome_token;

    public function __construct() {
        
        add_action('admin_menu', array($this, 'add_menu'));
        
        add_action('admin_init', array($this, 'colorme_update'));

        add_action('admin_enqueue_scripts', array($this, 'include_script'));

        add_action('add_meta_boxes', array($this, 'add_colorme_meta_boxes'));

        add_action('edit_post', array($this, 'save_colorme_meta_boxes'));

        add_action('admin_init', array($this, 'colorme_ajax'));

        add_action('admin_init', array($this, 'set_token'));

        register_deactivation_hook( __FILE__, array($this, 'deactivation') );
        
    }

    public function add_menu() {
        
        add_options_page( __('カラーミーショップ設定','my-custom-admin'),__('カラーミーショップ設定','my-custom-admin'),'administrator', 'colorme-setting', array($this, 'colorme_setting'));
    }

    public function colorme_setting(){
        
        $colorme_token = get_option("colorme_token");

        $html="";
        $html .= '<h2>アクセストークンを入力してください。</h2>';
        $html .= '<div class="colorme-api">';
        $html .= '<form action="" method="post">';
        $html .= '<p><input type="text" name="colorme_token" value="'.$colorme_token.'"></p>';
        $html .= '<p><input type="submit" value="登録" class="button button-primary" /></p>';
        $html .= wp_nonce_field("my-nonce-colorme","colorme_token-nonce");
        $html .= '<input type="hidden" name="colorme_check" value="colorme_check" />';
        $html .= '</div>';
        $html .= '</form>';

        echo $html;
    }

    public function colorme_update() {
        
        if($_SERVER["REQUEST_METHOD"] == "POST"){
            
            if (isset($_POST["colorme_token"]) && $_POST["colorme_check"] == "colorme_check") {
                $colorme_token = $_POST["colorme_token"];
                
                if((check_admin_referer( 'my-nonce-colorme','colorme_token-nonce' ))){

                    update_option("colorme_token", $colorme_token);
                                        
                }
                
            }
        }
    }

    public function set_token(){
        $this->colome_token = get_option("colorme_token");
    }
    public function get_token(){
        return $this->colome_token;
    }

    public function include_script(){
    	wp_enqueue_script( 'jquery' );

        $nonce = wp_create_nonce('colorme_ajax');

    	wp_register_script( 'colorme_js',plugins_url( 'js/colorme.js' , __FILE__),array("jquery") );
    	wp_enqueue_script('colorme_js');

        $config_array = array(
            'ajaxURL' => admin_url('admin-ajax.php'),
            'ajaxActions' => 'wp_ajax_colorme_api',
            'ajaxNonce' => $nonce
        );

        wp_localize_script('colorme_js','colorme_conf',$config_array);


        wp_register_style( 'colorme_css',plugins_url( 'css/colorme.css' , __FILE__),array() );
        wp_enqueue_style('colorme_css');

        
    }
    
    public function add_colorme_meta_boxes(){
        add_meta_box("colorme-projects-meta","カラーミーショップ商品ID",array($this,'display_meta'),"post");
    }

    public function display_meta(){
        global $post;
        $item_id = get_post_meta( $post->ID,'colorme_id',true );

        $html = '<table id="colorme-table">';
        $html .= '<tr>';
        $html .= '<th><label>商品ID</label></th>';
        $html .= '<td><input id="colorme-item_id" name="colorme_id" type="text" value="'.$item_id.'"></td>';
        $html .= '</tr>';
        $html .= '</table>';
        $html .= '<div id="colorme-check" class="colorme-check"><a href="">商品を確認する</a></div>';
        $html .= '<div class="colorme-box">';
        $html .= '<p id="colorme-box-title"></p>';
        $html .= '<p id="colorme-box-price"></p>';
        $html .= '<div id="colorme-box-image"></div>';
        $html .= '</div>';
        $html .= wp_nonce_field("my-nonce-colorme","colorme-nonce");

        echo $html;
    }

    

    public function save_colorme_meta_boxes(){
        global $post;
        if('post' == $_POST['post_type'] && isset($_POST['post_type'])){
            if(check_admin_referer( 'my-nonce-colorme','colorme-nonce' )){
                update_post_meta($post->ID, 'colorme_id', $_POST['colorme_id']);
            }
        }
    }

    public function colorme_ajax(){
        add_action("wp_ajax_nopriv_colorme_api",array($this,'colorme_api'));
        add_action("wp_ajax_colorme_api",array($this,'colorme_api'));
    }

    function colorme_api() {
        $nonce = $_POST['nonce'];
        $item_id = $_POST['item_id'];

        if ( !wp_verify_nonce($nonce, 'colorme_ajax') ){
            die('Unauthorized request!');
        }

        $colorme = $this->get_colorme_item($item_id);
        
        header('content-Type: application/json; charset=utf-8');
        echo json_encode( $colorme );
        exit;
    }

    public function get_colorme_item($item_id){

        $token = get_option("colorme_token");


        $content = null;
        
        if($item_id){
            $request_options = array(
                'http' => array(
                    'method'  => 'GET',
                    'header'=> "Authorization: Bearer $token\r\n",
                    'ignore_errors' => true
                )
            );

            $context = stream_context_create($request_options);

            $url = "https://api.shop-pro.jp/v1/products/$item_id.json";   
            $response_body = file_get_contents($url, false, $context);

            $content = json_decode($response_body);

        }
        return $content;

    }  

    public function deactivation(){
        delete_option("colorme_token");
    }  
    
}

function colorme(){
    global $post;

    $item_id = get_post_meta($post->ID, "colorme_id", true); 

    $Colorme_check = new Colorme_check;
    $colorme_check->set_token;
    
    $colorme_obj = $Colorme_check->get_colorme_item($item_id);
    if($colorme_obj){
        return $colorme_obj->product;
    }
    
}