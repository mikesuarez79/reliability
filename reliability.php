<?php
/*
Plugin Name: Reliability
Description: A simple plugin that allows to export students progress
Author: Michael Suarez
Version: 0.1
*/
register_activation_hook(__FILE__, 'crudOperationsTable');

function crudOperationsTable() {
  global $wpdb;
  $charset_collate = $wpdb->get_charset_collate();
  $table_name = $wpdb->prefix . 'userstable';
  /*$sql = "CREATE TABLE `$table_name` (
  `user_id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(220) DEFAULT NULL,
  `email` varchar(220) DEFAULT NULL,
  PRIMARY KEY(user_id)
  ) ENGINE=MyISAM DEFAULT CHARSET=latin1;
  ";
  if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
  }
  */
}

add_action('admin_menu', 'addAdminPageContent');

function addAdminPageContent() {
  add_menu_page('Reliability', 'Reliability', 'manage_options' ,__FILE__, 'crudAdminPage', 'dashicons-wordpress');
}

function list_csv_files($directory) {
  $files = array();
  $urlPath = plugin_dir_path( __FILE__ );
  if ($handle = opendir($directory)) {
    while (false !== ($file = readdir($handle))) {
      if ($file != "." && $file != ".." && preg_match('/\.csv$/', $file)) {
        if (is_file($directory.'/'.$file)) {
          $exportDate = substr($file, strpos($file, "_") +1);
          $files[] = '<td><a href="'.plugin_dir_url( __FILE__ ).$file.'">'.$file.'</a></td> <td>'.date('m/d/Y',substr($exportDate,0,-4)).'</td> <td><form method="post" action=""><input type="hidden" name="delete_file" value="/'.$directory.'/'.$file.'"><button type="submit">Delete</button></form></td>';
        }
      }
    }
    closedir($handle);
  }
  return $files;
}



function crudAdminPage() {
  global $wpdb;

  $local_time  = current_datetime();
    $current_time = $local_time->getTimestamp() + $local_time->getOffset();

  $table_name = $wpdb->prefix . 'userstable';

  if (isset($_POST['delete_file'])) {
    $file_path = $_POST['delete_file'];
    if (is_file($file_path)) {
      unlink($file_path);
    }
    echo "<script>alert('Delete - ". $file_path."');</script>";
  }

  if (isset($_POST['btncron'])) {
    export_students_progress();
  }
  
  if (isset($_POST['btnexport'])) {

    // include the headers for the csv format file
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="students.csv"');
    
    // clean output buffer
    ob_end_clean();


    $fp = fopen('php://output', 'w');

    // Headers for the CSV file

    $header_row = array(
      0 => 'user_id',
      1 => 'name',
      2 => 'email',
      3 => 'course_id',
      4 => 'course_title', 
      5 => 'steps_completed',
      6 => 'steps_total', 
      7 => 'course_completed',
      8 => 'course_completed_on',
      9 => 'course_started_on',
      10 => 'course_total_time_on',
      11 => 'course_last_step_id',
      12 => 'course_last_step_type',
      13 => 'course_last_step_title',
      14 => 'course_farthest_step_title',
      15 => 'last_login_date' );
    fputcsv($fp, $header_row); 


    // Prepare the query for records 

    $qry = "SELECT
    wp_users.ID,
    wp_users.display_name,
    wp_users.user_email,
    wp_users.user_login,
    wp_learndash_user_activity.user_id,
    wp_learndash_user_activity.course_id,
    wp_posts.post_title,
    wp_learndash_user_activity.activity_completed,
    wp_learndash_user_activity.activity_updated,
    wp_learndash_user_activity.activity_started,
    wp_learndash_user_activity.activity_status,
    wp_learndash_user_activity.activity_id AS activity_id_0,
    wp_learndash_user_activity.post_id,
    wp_learndash_user_activity.activity_type,
    wp_learndash_user_activity_meta.activity_meta_key,
    wp_learndash_user_activity_meta.activity_meta_value,
    wp_learndash_user_activity_meta.activity_meta_id
    FROM
    wp_learndash_user_activity
    JOIN wp_learndash_user_activity_meta
    ON wp_learndash_user_activity.activity_id = wp_learndash_user_activity_meta.activity_id 
    JOIN wp_posts
    ON wp_learndash_user_activity.course_id = wp_posts.ID 
    JOIN wp_users
    ON wp_users.ID = wp_learndash_user_activity.user_id
    GROUP BY
    wp_learndash_user_activity.user_id, wp_learndash_user_activity.activity_type
    ORDER BY
    wp_users.display_name ASC";


    $sql_query    = $wpdb->prepare($qry, 1) ;
    $rows         = $wpdb->get_results($sql_query, ARRAY_A);
    $steps_total = 0;

    // If there is data, then Iterate records
    if(!empty($rows)) {

      foreach($rows as $Record)
        {  
          //Prepare variables for record display
          $far_step = array();
          $course_completed = ($Record['activity_status'] > 0 ) ? "YES" : "NO";
          $course_completed_on = ($Record['activity_completed'] > 0 ) ? date('m/d/Y', $Record['activity_completed']) : 0;
          $course_started_on = ($Record['activity_started'] > 0 ) ? date('m/d/Y', $Record['activity_started']) : 0;
          $last_login = date('m/d/Y', get_user_meta( $Record['ID'], '_ld_notifications_last_login', true));
          $header_output =  '';

          // get the total time course 
          if ( ( !empty( $Record['activity_started'] ) ) && ( !empty( $Record['activity_updated']) ) ) {
          
            $course_time_diff = $Record['activity_updated']- $Record['activity_started'] ;
            if ( $course_time_diff > 0) {

              if ( $course_time_diff > 86400 ) {
                if ( !empty( $header_output ) ) $header_output .= ' ';
                $header_output .= floor($course_time_diff / 86400) .'d';
                $course_time_diff %= 86400;
              }

              if ( $course_time_diff > 3600 ) {
                if ( !empty( $header_output ) ) $header_output .= ' ';
                $header_output .= floor( $course_time_diff / 3600 ) .'h';
                $course_time_diff %= 3600;
              }
            
              if ( $course_time_diff > 60 ) {
                if ( !empty( $header_output ) ) $header_output .= ' ';
                $header_output .= floor( $course_time_diff / 60 ) .'m';
                $course_time_diff %= 60;
              }
    
              if ( $course_time_diff > 0 ) {
                if ( !empty( $header_output ) ) $header_output .= ' ';
                $header_output .= $course_time_diff .'s';
              }
            
            }
          } // end for each


          $str = "to be fix";

          // Will return post_id /activity_completed
          $far_step[] = learndash_activity_course_get_latest_completed_step( $Record['ID'], $Record['course_id'] );
          $course_far_step_id = $far_step[0]['post_id'];
          $course_farthest_step_title = esc_html( get_the_title($course_far_step_id) );

          $last_step = learndash_user_course_last_step( $Record['ID'], $Record['course_id']);
          $last_step_title = esc_html( get_the_title($last_step) );
          $steps_completed = learndash_course_get_completed_steps( $Record['ID'], $Record['course_id'] );
          $steps_total = learndash_course_get_steps_count( $Record['course_id'] );

      
          // output records to csv
          $OutputRecord = array(
            $Record['ID'],
            $Record['display_name'],
            $Record['user_email'],
            $Record['course_id'],
            $Record['post_title'],
            $steps_completed,
            $steps_total,
            $course_completed,
            $course_completed_on,
            $course_started_on,
            $header_output,
            $last_step,
            $Record['activity_type'],
            $last_step_title,
            $course_farthest_step_title,
            $last_login
          );

          // file created and for download
          fputcsv($fp, $OutputRecord);     
      }
    }
  
   
    fclose( $fp );
    exit;
  } // End of function


  // check user capabilities
  if ( ! current_user_can( 'manage_options' ) ) {
    return;
  }

  //Get the active tab from the $_GET param
  $default_tab = null;
  $tab = isset($_GET['tab']) ? $_GET['tab'] : $default_tab;


  ?>



  <div class="wrap">
    <h2>Reliability Export</h2>
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

    <!-- Here are our tabs -->
    <nav class="nav-tab-wrapper">
      <a href="?page=reliability/reliability.php" class="nav-tab <?php if($tab===null):?>nav-tab-active<?php endif; ?>">Students</a>
      <a href="?page=reliability/reliability.php&tab=assignments" class="nav-tab <?php if($tab==='assignments'):?>nav-tab-active<?php endif; ?>">Assignments</a>
      <!-- <a href="?page=my-plugin&tab=tools" class="nav-tab <?php if($tab==='tools'):?>nav-tab-active<?php endif; ?>">Tools</a> -->
    </nav>

    <div class="tab-content">
    <?php switch($tab) :
      case 'assignments':
         include 'assignments.php'; 
         //Put your HTML here
        break;
      default:
    ?>
    <br />
      <form action="" method="post">
          <button id="btnexport" name="btnexport" type="submit" class="button">Export Student's Data</button>
      </form>

      <?php 

        // List the csv files created 
        $directory = __DIR__;
        $files = list_csv_files($directory);
        echo '<h4>Scheduled Exports</h4>';
        echo '<table class="wp-list-table widefat striped table-view-list crontrol-events" style="max-width: 95%;">';
        echo '<thead><tr><th>Filename</th><th>Date exported</th><th>Action</th></tr></thead>';
        foreach ($files as $file) {
          echo '<tr>'.$file.'</tr>';
        }
        echo '</table>';

      ?>


    <?php
        break;
    endswitch; ?>
    </div>

   
  
  </div>

  

  
  <?php

}


function list_my_courses() {

}

function list_my_modules() {
  if(isset($_REQUEST)) {

  $course_id = $_REQUEST['course_id'];

  $module_array = array();
  $modules = learndash_course_get_lessons( $course_id );

  foreach($modules as $modules_title) {
    //$course_name[] = get_post($ld_course_id);

    echo "<option value='$modules_title->ID'>$modules_title->post_title</option>";
     //$modules_array[] = $modules_title->post_name;
    //echo "<options>$modules_title->post_name</options>";
  }
  //$my_post = $_REQUEST;
  // var_dump($modules_array);
  //echo $_REQUEST['course_id'];
  // echo json_encode($modules_array);
  }

}

add_action("wp_ajax_list_modules", 'list_my_modules');

function list_my_lessons() {
  if(isset($_REQUEST)) {

    $course_id = $_REQUEST['course_id'];
    $lesson_id = $_REQUEST['lesson_id'];

    $lessons_array = array();
    $lessons = learndash_course_get_topics($course_id, $lesson_id);

    foreach($lessons as $lessons_title) {

      //$lessons_array[] = $lessons_title->post_title;
      echo "<option value='$lessons_title->ID'>$lessons_title->post_title</option>";

    }

    var_dump($lessons_array);

  }
}

add_action('wp_ajax_list_lessons', 'list_my_lessons');

