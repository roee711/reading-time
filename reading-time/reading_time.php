<?php

/*

Plugin Name: Reading Time

Plugin URI: roeeparash.co.il

Description: The plugin should allow user to add a “Reading Time” feature to his blog
posts.

Version: 1.0

Author: roee parash

Author URI: roeeparash.co.il

License: GPLv2 or later

Text Domain: reading-time

*/
define('NUMBER_MINUTH',200);
define( 'PLUGIN_DIR', dirname(__FILE__).'/' );
require_once PLUGIN_DIR.'/functions.php';
class ReadingTime
{
    /**
     * Holds the values to be used in the fields callbacks
     */
    private $options;
    private static    $roundingBehavior= array("round_up"=>"Round Up","round_down"=>"Round Down",
            "round_up_half"=>"Round up in ½ minute steps",
            "round_down_half"=>"Round down in ½ minute steps");


    /**
     * Start up
     */
    public function __construct()
    {
        add_action( 'admin_menu', array( $this, 'readingtime_add_plugin_page' ) );
        add_action( 'admin_init', array( $this, 'readingtime_page_init' ) );
        add_action( 'save_post', array( $this,'readingtime_save_post') );
        add_shortcode('reading_time',array($this,'readingtime_get_reading'));
        add_filter ('shortcode_atts_reading_time', array( $this,'readingtime_filter_attributes'),10, 3);
        add_action( 'admin_enqueue_scripts', array($this,'readingtime_scripts') );
        add_action( 'wp_ajax_set_calculations',array($this,'readingtime_set_calculations' ));
        add_action( 'wp_ajax_nopriv_set_calculations', array($this,'readingtime_set_calculations' ));
        add_action( 'cli_init', array($this,'readingtime_cli_register_commands' ));
        add_action( 'init', array($this, 'readingtimeload_text_domain' ));

    }

    // translated domain
    public function readingtimeload_text_domain(){
        load_plugin_textdomain( 'reading-time', false, basename( dirname( __FILE__ ) ) . '/languages/' );
    }
    /* register commends cli */
    public function  readingtime_cli_register_commands(){
        WP_CLI::add_command( 'reading-time config get',  array($this,'readingtime_get_option')  );
        WP_CLI::add_command( 'reading-time config set',  array($this,'readingtime_set_option')  );
        WP_CLI::add_command( 'reading-time clear-cache',  array($this,'readingtime_update_post_calculation')  );
        WP_CLI::add_command( 'reading-time get',  array($this,'readingtime_get_calculation_post')  );
    }
    /*  commends cli get calculation post id */
    public  function readingtime_get_calculation_post($args=array()){
        if(count($args)){
            WP_CLI::line(get_reading_time($args[0]));
        }
    }
    /*  commends cli  calculation all post */
    public  function  readingtime_update_post_calculation(){
        $this->readingtime_calculation_all_post();
        WP_CLI::line("Reading time calculation in all posts updated");
    }
    /*  commends cli set option plugin */
    public function readingtime_set_option(array $args ){
        $readingtimeOption = get_option( 'readingtime_option' );
        $flag=true;
        if(count($args)==2){
            if(isset($readingtimeOption[$args[0]])){
                $valueOption=$args[1];
                $keyOption =$args[0];
                switch ($keyOption){
                    case 'number_minute':
                        if(!is_numeric($valueOption)){
                            $flag=false;
                            $msg="Updating this option should get a number";
                        }
                        break;
                    case 'supported_post_types':
                        $flagType=$this->checkTypePost($valueOption);
                        if(!$flagType){
                            $flag=false;
                            $msg="There are no posts in the type she wanted to update";
                        }else{
                            $valueOption =explode(",",$valueOption);
                        }
                        break;
                    case 'rounding_behavior':
                        $flagRoundingBehavior=$this->checkRoundingBehavior($valueOption);
                        if(!$flagRoundingBehavior){
                            $flag=false;
                            $msg="There is no such thing as a rounding behavior value";
                        }
                        break;
                }
                if($flag){
                    $readingtimeOption [$keyOption]=$valueOption;
                    update_option('readingtime_option',$readingtimeOption);
                    $this->readingtime_calculation_all_post();
                    WP_CLI::line("Updated value of this option:".$args[0]);
                }else{
                    WP_CLI::line($msg);
                }

            }else{
                WP_CLI::line("Option key missing or invalid value");
            }
        }else{
            WP_CLI::line("Missing or option key or option value");

        }

    }
    /*  commends cli get options plugin */
    public function readingtime_get_option() {
        $readingtimeOption = get_option( 'readingtime_option' );
        foreach ($readingtimeOption as $key =>$valueOption ){
            if(is_array($valueOption)){
                $valueOption =implode(",",$valueOption);
            }
            WP_CLI::line($key." = ".$valueOption );
        }
    }
    public  function  checkRoundingBehavior($valueOption){
        $arrRoundingRehavior=self::$roundingBehavior;
        foreach($arrRoundingRehavior as $key=>$value ){
            if($key ==$valueOption){
                return  true;
            }
        }
        return false;
    }
    public  function  checkTypePost($typePosts){
        $typePost=explode(",",$typePosts);
        $flag=true;
        for($i=0;$i<count($typePost);$i++){

            if(!in_array($typePost[$i],get_post_types()) ){
                $flag=false;
                break;
            }
        }
        return  $flag;
    }

    /**
     * Add options page
     */
    public function readingtime_add_plugin_page()
    {
        // This page will be under "Settings"
        add_options_page(
            'Settings Admin',
            'Reading Time Settings',
            'manage_options',
            'readingtime-setting-admin',
            array( $this, 'readingtime_create_admin_page' )
        );
    }

    /**
     * Options page callback
     */
    public function readingtime_create_admin_page()
    {
        // Set class property
        $this->options = get_option( 'readingtime_option' );
        ?>
        <div class="wrap">
            <h1>Reading Time Settings</h1>
            <form method="post" action="options.php">
                <?php
                // This prints out all hidden setting fields
                settings_fields( 'readingtime_option_group' );
                do_settings_sections( 'readingtime-setting-admin' );
                submit_button();
                ?>
            </form>
            <button id="clear-calculations" class="button button-secondary"> <?php echo __('Clear Previous calculations','reading-time')?></button>
            <div id="msg-calculations"></div>
        </div>
        <?php
    }

    /**
     * Register and add settings
     */
    public function readingtime_page_init()
    {
        register_setting(
            'readingtime_option_group', // Option group
            'readingtime_option', // Option name
            array( $this, 'readingtimes_sanitize' ) // Sanitize
        );

        add_settings_section(
            'readingtime_setting', // ID
            '', // Title
            array( $this, 'readingtime_section_info' ), // Callback
            'readingtime-setting-admin' // Page
        );

        add_settings_field(
            'number_minute', // ID
            'Number of words per minute', // Title
            array( $this, 'readingtime_number_minute' ), // Callback
            'readingtime-setting-admin', // Page
            'readingtime_setting' // Section
        );
        add_settings_field(
            'supported_post_types', // ID
            'Supported Post Types', // Title
            array( $this, 'readingtime_supported_post_types' ), // Callback
            'readingtime-setting-admin', // Page
            'readingtime_setting' // Section
        );
        add_settings_field(
            'rounding_behavior', // ID
            'Rounding Behavior', // Title
            array( $this, 'readingtime_rounding_behavior' ), // Callback
            'readingtime-setting-admin', // Page
            'readingtime_setting' // Section
        );
        add_settings_field(
            'label_output', // ID
            'Label Output', // Title
            array( $this, 'readingtime_label_output' ), // Callback
            'readingtime-setting-admin', // Page
            'readingtime_setting' // Section
        );
    }

    /**
     * Sanitize each setting field as needed
     *
     * @param array $input Contains all settings fields as array keys
     */
    public function readingtimes_sanitize( $input )
    {
        $new_input = array();


        if( !empty( $input['number_minute'] ) ) {
            $new_input['number_minute'] = intval($input['number_minute']);
        }

        if( !empty( $input['supported_post_types'] )&& is_array($input['supported_post_types']) ){
            $new_input['supported_post_types'] =$input['supported_post_types'];
        }
        if( !empty( $input['rounding_behavior'] )){
            $new_input['rounding_behavior'] =sanitize_text_field($input['rounding_behavior']);
        }
        if( !empty( $input['label_output'] )){
            $new_input['label_output'] =sanitize_text_field($input['label_output']);
        }
        $this->readingtime_calculation_all_post();


        return $new_input;
    }

    /**
     * Print the Section text
     */
    public function readingtime_section_info()
    {
        print 'Enter your settings below:';
    }

    /**
     * Get the settings option array and print one of its values
     */
    public function readingtime_number_minute()
    {

        $numberMinute =(!empty($this->options['number_minute']))?esc_attr($this->options['number_minute']):NUMBER_MINUTH;
        printf(
            '<input required type="text" id="number_minute" name="readingtime_option[number_minute]" value="%s" />',
            $numberMinute
        );
    }

    /**
     * Get the settings option array and print one of its values
     */
    public function readingtime_label_output()
    {

        $labelOutput =(!empty($this->options['label_output']))?esc_attr($this->options['label_output']):'';
        printf(
            '<input  type="text" id="label_output" name="readingtime_option[label_output]" value="%s" />',
            $labelOutput
        );
    }

    /**
     * Get the settings option array and print one of its values
     */
    public function readingtime_supported_post_types()
    {

        $supportedPostTypes=(!empty($this->options['supported_post_types']))?
            $this->options['supported_post_types']:array('post');

        foreach (get_post_types() as $key =>$typeName):
                $checked = (in_array($typeName,$supportedPostTypes))?"checked":"";
            ?>
            <label class="post-type">
            <input <?php echo $checked ?>  type="checkbox" name="readingtime_option[supported_post_types][]"
                   value="<?php echo $typeName?>">
                <?php echo $typeName ?>
            </label>
            <?php
        endforeach;
    }

    /**
     * Get the settings option array and print one of its values
     */
    public function readingtime_rounding_behavior()
    {
        $arrRoundingRehavior=self::$roundingBehavior;
         $roundingBehavior=(!empty($this->options['rounding_behavior']))?
        $this->options['rounding_behavior']:"round_up";
         ?>
            <select name="readingtime_option[rounding_behavior]">
                <?php
                foreach ($arrRoundingRehavior as $key =>$valueRoundingRehavior):
                    $selected = ($key==$roundingBehavior)?"selected":"";
                    ?>
                    <option <?php echo $selected ?> value="<?php echo $key ?>"><?php echo $valueRoundingRehavior ?></option>
                <?php
                 endforeach;
                 ?>
             </select>
                <?php
    }


    /* show  meta box post */
    public  function readingtime_show_fields_meta_box(){
        global $post;
        $readingTime=get_post_meta($post->ID, 'reading_time', true );
        ?>
        <p>
            <label><?php _e('Reading Time', 'reading-Time'); ?></label>
             <br>
            <input  type="text" name="reading_time" id="reading_time" value="<?php echo $readingTime ?>">
        </p>
    <?php
    }
    /* save  meta box post */
     public function readingtime_save_post($postId){
         $readingTimeOption =get_option('readingtime_option');
         $readingTime=get_transient('reading_time');
         $post = get_post($postId);
         if(in_array($post->post_type,$readingTimeOption['supported_post_types'])) {
             $readingTimeValue = ReadingTime::readingtime_calculation($post->ID);
             $readingTime[$post->ID] =$readingTimeValue;
             set_transient('reading_time',$readingTime,0);

         }
    }
    /* calculation post id */
    public  static  function readingtime_calculation($postId){
        $readingTimeOption =get_option('readingtime_option');
        $roundingBehavior = $readingTimeOption['rounding_behavior'];
        $numberMinute =$readingTimeOption['number_minute'];
        $post = get_post($postId);
        $postContent=$post->post_content;
        $wordCount = str_word_count(trim(strip_tags($postContent)));
        switch ($roundingBehavior){

            case 'round_up':
                $readingTime =round(60 *($wordCount/$numberMinute));
                break;
            case 'round_down':
                $readingTime =floor(60 *($wordCount/$numberMinute));

                break;
            case 'round_up_half':
                $readingTime =round(60 *($wordCount/$numberMinute))-0.5;
                break;
            case 'round_down_half':
                $readingTime =floor(60 *($wordCount/$numberMinute))+0.5;
                break;
        }
        return $readingTime;
    }
    /* shortcode post read time*/
    public  function  readingtime_get_reading( $atts, $content, $tag ){
        global $post;
        $readingTimeOption =get_option('readingtime_option');
        $label =(!empty(  $readingTimeOption['label_output']))?
            $readingTimeOption['label_output']: __("Read Time","reading-time");
        $atts = shortcode_atts( array(
            'class' => '',
        ), $atts,$tag );

        if(in_array($post->post_type,$readingTimeOption['supported_post_types'])){
            $readingTime=get_transient('reading_time');
            $readingTimeValue = ReadingTime::readingtime_calculation($post->ID);
            $readingTime[$post->ID] =$readingTimeValue;
            set_transient('reading_time',$readingTime,0);
            if(empty($readingTime)||empty($readingTime[$post->id])){
                $readingTimeValue= self::readingtime_calculation($post->ID);
                $readingTime[$post->ID]=$readingTimeValue;
                set_transient('reading_time',$readingTime,0);
            }else{
                $readingTimeValue =$readingTime[$post->ID];
            }
            $class=(!empty($atts['class']))?"class='".$atts['class']."'":"";
              echo '<div '.$class.'>
                        <label>'.$label. ':</label>
                        <span> '.$readingTimeValue.'</span>
                    </div>' ;
        }
        $output = ob_get_contents();
        ob_end_clean();
        return $output;
    }
    // filter attributes shortcode
    public  function readingtime_filter_attributes($out, $pairs, $atts ){

        return $out;

    }
    /* calculation all posts*/
    public function readingtime_calculation_all_post(){
        $readingTimeOption =get_option('readingtime_option');
        $supportedPostTypes=$readingTimeOption['supported_post_types'];
        $readingTime =array();
        $posts = get_posts([
            'post_type' =>   $supportedPostTypes,
            'post_status' => 'publish',
            'numberposts' => -1,
        ]);

        if(count($posts)) {
            for ($i = 0; $i < count($posts); $i++) {
                $readingTime[$posts[$i]->ID] =self::readingtime_calculation($posts[$i]->ID);
            }
            set_transient('reading_time',$readingTime,0);

            return true;
        }else{
            return false;
        }
    }
    /* script plugin*/
    public  function readingtime_scripts($screen) {

        if($screen !=="settings_page_readingtime-setting-admin"){
            return;
        }
        $translationArray = array(
            'ajax_url' => admin_url( 'admin-ajax.php' )
        );
        wp_enqueue_style("admin-css",plugin_dir_url(__FILE__) . 'assets/css/admin.css');
        wp_enqueue_script('admin-js', plugin_dir_url(__FILE__) . 'assets/js/admin.js', array(), null, true);
        wp_localize_script( 'admin-js', 'readingtime_object', $translationArray );
    }
    /* ajax calculations all post   */
    public  function  readingtime_set_calculations(){

        $result =$this->readingtime_calculation_all_post();

        if($result){
           echo "All posts were updated while reading";
        }else{
            echo "There are no posts of the type updated in plugin settings";
        }
        die();

    }

}
$readingTime = new ReadingTime();