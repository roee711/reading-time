<?php
if(!function_exists('the_reading_time')){
    function the_reading_time($id=null){
        global $post;
        $format ="html";
        ob_start();
        if($id){
            $post=get_post($id);
            $format ="text";
        }
        if($post) {
            $readingTimeOption = get_option('readingtime_option');
            $label = (!empty($readingTimeOption['label_output'])) ?
                $readingTimeOption['label_output'] : __("Read Time", "reading-time");
            $readingTime=get_transient('reading_time');
            if (in_array($post->post_type, $readingTimeOption['supported_post_types'])) {
                if (!$readingTime || empty($readingTime[$post->ID])) {
                    $readingTimeValue = ReadingTime::readingtime_calculation($post->ID);
                    $readingTime[$post->ID] =$readingTimeValue;
                    set_transient('reading_time',$readingTime,0);
                }else{
                    $readingTimeValue =$readingTime[$post->ID];
                }

                if ($format == "html") {
                    echo '<div>
                    <label>' . $label . ':</label>
                   <span> ' . $readingTimeValue . '</span>
                </div>';
                } else {
                    echo $label . ":" . $readingTimeValue;
                }

            }
        }
        $output = ob_get_contents();
        ob_end_clean();
        echo  $output;

    }
}
if(!function_exists('get_reading_time')){

    function get_reading_time($id=null){
        return the_reading_time($id);
    }
}
