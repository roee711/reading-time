# reading-time
The plugin writer is :roee parash

The plugin writer's email is :roee711@gmail.com

The plugin should allow user to add a “Reading Time” feature to his blog  posts

Features implemented in plugin:

* Admin Settings Page
    1) No. of Words Per Minute (default 200) – will be used for calculations
    2) Supported Post Types (default – only “post”)
       A list of checkboxes for each post type. Checked post types will have support for 
       the Reading Time feature
    3)Rounding behavior – (default “round up”) – one of “Round Up”, “Round Down”, 
    “Round up in ½ minute steps”, “Round down in ½ minute steps”
    4)[BONUS] Clear Previous calculations Button – once clicked, all values will be 
    recalculated as needed.

* Reading Time calculation process
* The ”Reading Time” value should be calculated and cached (stored for future use) on the following events:
    1) Once the post is created
    2) When a post is updated (by using either the Admin, or the official WordPress php functions - such as wp_update_post
    3) When the reading time is requested (e.g. for showing in theme), and no previous value exists (To support reading time for posts that are already in the system once the            plugin is activated, or after settings change)
    4) In any other case when there is a chance that the value is no longer correct (e.g. when the admin changed the No. of
  
  * Show Reading Time in Theme
      1) Using the shortcode [reading_time] in post content
      2) By calling a php function named `the_reading_time()
      3) By echoing the return value of a php function named `get_reading_time()`
      5) When embedded with shortcode – the value should be rendered wrapped in
          HTML, including a label. The classes used in the HTML should be filterable 
          using custom hooks
      5) [BONUS] Make the label managed in the admin settings page

  * More Bonus Feature
  
     1)Make the plugin’s admin fully translated using WordPress’ localization 
      guidelines
      
    2) Create the following custom commands for managing the plugin using WP CLI
      a )wp reading-time config get – Show the values of the setting
      
      b) wp reading-time config set CONFIG VALUE – Update the value of a setting
      
      c)  wp reading-time clear-cache – Clear previous calculation and force 
recalculation for all posts

    d) wp reading-time get PID – Show the calculated reading time value for a 
specific post


   
