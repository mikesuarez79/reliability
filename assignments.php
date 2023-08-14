<?php

  global $wpdb;

  // Get all the list of groups or teams
  $groups = learndash_get_groups();

  usort($groups, function($grp_name_a, $grp_name_b){
          return $grp_name_a->post_name <=> $grp_name_b->post_name;
  });

  $grp_name = array();
  $course_name = array();

  foreach($courses as $ld_course_id) {
      $course_name[] = get_post($ld_course_id);
  }

  // // These are lessons under a course
  $ajax_course_id = $_POST['course_id'];

  // get all assignments
  //$assignments = array('apple', 'oranges', 'grapes');
  $my_assignments = array();
  $my_assignments = learndash_get_user_assignments('19','591');
  //$assignments = learndash_get_course_assignments();

  //print_r($my_assignments);

;

  if (isset($_POST['btn-filter'])) {

    // Table names
    $assignments_table = $wpdb->prefix . 'learndash_user_activity';
    $courses_table = $wpdb->prefix . 'posts';

    $course_id = $_POST['courses'];
    $team_id = $_POST['teams'];
    
   // echo $team_id;

    $user_ids = array();
    $user_ids[] = learndash_get_groups_user_ids( $team_id );
    $user_ids = $user_ids[0];

  //  print_r($user_ids);

    $query = "SELECT
    wp_posts.ID,
    wp_posts.post_title,
    wp_posts.post_content,
    wp_posts.post_type,
    wp_users.user_nicename,
    wp_users.ID AS my_user_id,
    wp_posts.post_author,
    wp_posts.post_date,
    wp_posts.post_status, 
    (SELECT course_id FROM wp_learndash_user_activity where user_id = my_user_id AND activity_type = 'course' LIMIT 1) as course_id 
    FROM
    wp_users
    JOIN wp_posts
    ON wp_users.ID = wp_posts.post_author
    WHERE
    wp_posts.post_type = 'sfwd-assignment' AND wp_posts.post_status = 'publish' AND wp_users.ID IN (" . implode(',', array_map('intval', $user_ids)) . ")". "
    ORDER BY
    wp_posts.post_date DESC";

    //echo $query;
    $assignments = $wpdb->get_results($query);

  }



  if (isset($_POST['btn-download'])) {
   
    function download_compiled_files($urlArray, $zipFilename) {
        $downloaded_files = array();
        $tempDir = wp_upload_dir()['path']; // Get the WordPress temporary upload directory

        foreach ($urlArray as $url) {
            $response = wp_remote_get($url);
            
            if (is_array($response) && !is_wp_error($response)) {
                $filename = basename($url);
                $file_path = $tempDir . '/' . $filename;

                // Check if the file already exists
                if (!file_exists($file_path)) {
                    $file_content = wp_remote_retrieve_body($response);

                    // Save the file in the temporary directory
                    if ($file_content && file_put_contents($file_path, $file_content)) {
                        // File downloaded and saved successfully
                        $downloaded_files[] = $file_path;
                    }
                }
            }
        }

        // Create a zip file
        if (!empty($downloaded_files)) {
            $zip_path = $tempDir . '/' . $zipFilename;

            $zip = new ZipArchive();
            if ($zip->open($zip_path, ZipArchive::CREATE | ZipArchive::OVERWRITE)) {
                foreach ($downloaded_files as $file) {
                    $zip->addFile($file, basename($file));
                }

                $zip->close();

                // Set headers to trigger the download
                header('Content-Type: application/zip');
                header('Content-Disposition: attachment; filename="' . $zipFilename . '"');
                header('Content-Length: ' . filesize($zip_path));
                header('Pragma: no-cache');
                readfile($zip_path);
                exit;
            }
        }
    }


    // Table names
    $assignments_table = $wpdb->prefix . 'learndash_user_activity';
    $courses_table = $wpdb->prefix . 'posts';

    $course_id = $_POST['courses'];
    $team_id = $_POST['teams'];

    $user_ids = array();
    $user_ids[] = learndash_get_groups_user_ids( $team_id );
    $user_ids = $user_ids[0];


   /* $query = "SELECT
    wp_posts.ID,
    wp_posts.post_title,
    wp_posts.post_content,
    wp_posts.post_type,
    wp_users.user_nicename,
    wp_users.ID AS my_user_id,
    wp_posts.post_author,
    wp_posts.post_date,
    wp_posts.post_status, 
    (SELECT course_id FROM wp_learndash_user_activity where user_id = my_user_id AND activity_type = 'course' LIMIT 1) as course_id 
    FROM
    wp_users
    JOIN wp_posts
    ON wp_users.ID = wp_posts.post_author
    WHERE
    wp_posts.post_type = 'sfwd-assignment' AND wp_posts.post_status = 'publish'
    ORDER BY
    reliability.wp_posts.post_date DESC LIMIT 10";*/

    $query = "SELECT
    wp_posts.ID,
    wp_posts.post_title,
    wp_posts.post_content,
    wp_posts.post_type,
    wp_users.user_nicename,
    wp_users.ID AS my_user_id,
    wp_posts.post_author,
    wp_posts.post_date,
    wp_posts.post_status, 
    (SELECT course_id FROM wp_learndash_user_activity where user_id = my_user_id AND activity_type = 'course' LIMIT 1) as course_id 
    FROM
    wp_users
    JOIN wp_posts
    ON wp_users.ID = wp_posts.post_author
    WHERE
    wp_posts.post_type = 'sfwd-assignment' AND wp_posts.post_status = 'publish' AND wp_users.ID IN (" . implode(',', array_map('intval', $user_ids)) . ")". "
    ORDER BY
    wp_posts.post_date DESC";

    $assignments = $wpdb->get_results($query);

    // puts them to array 
    $upload_dir = wp_upload_dir();
    $urls_array = array();
    $updirpath = $upload_dir['baseurl'].'/assignments/';

    foreach($assignments as $links){
      $completeFilePath = $updirpath.$links->post_title;
      $urls_array[] = $completeFilePath;
      $user_names[] = $links->user_nicename;
    }
      // Usage example
      /*$urls = array(
          'http://localhost:8888/reliability/wp-content/uploads/assignments/assignment_24845_167878004116_Assignment_Lesson_3.3.docx',
          'http://localhost:8888/reliability/wp-content/uploads/assignments/assignment_33804_167877855064_Assignment.pdf'
          // Add more URLs here
      );*/

     // print_r($urls_array);

      //$upload_dir = wp_upload_dir();
      $updir = $upload_dir['basedir'].'/assignments/';

      // Prepare zip file
      $zip = new ZipArchive();
      $zip_name = time().".zip"; // Zip name
      $zip->open($zip_name,  ZipArchive::CREATE);

      $ctr = 0;
      foreach ($urls_array  as $url) {
        //$file_contents = file_get_contents($url);
        $file_name = basename($url);
        $new_name = $user_names[$ctr] .'-'. basename($url);
        //$contentType = mime_content_type($url);

          if (file_exists($updir.$file_name)) {
            $zip->addFromString($new_name,  file_get_contents($url)); 
          } else {
            //$image_path = $upload_dir['path']
            echo $file_name. " - does not exist! <br />";
          }
         // sleep(2);
         $ctr++;
     }

      $zip->close();
      header("Content-type: application/zip"); 
      header("Content-Disposition: attachment; filename=$zip_name");
      header("Content-length: " . filesize($zip_name));
      header("Pragma: no-cache"); 
      header("Expires: 0"); 
      ob_end_clean();
      readfile("$zip_name");
    
  }

?>


<div>
    <form method="post" action="">
      <div class="filter-form">
          <div class="input-block">
            <label><b>Team:</b></label>
            <select class="sel-teams" name="teams" id="teams">
            <?php foreach($groups as $ld_group_name) { ?>
              <option value="<?php echo $ld_group_name->ID; ?>"><?php echo $ld_group_name->post_name; ?></option>
            <?php } ?>
            </select>
          </div>
          
          <div class="input-block">
          <label><b>Courses:</b></label>
          <select class="sel-courses" name="courses" id="courses">
          </select>
          </div>

          <div class="input-block">
          <label><b>Modules:</b></label><select class="sel-modules" name="modules" id="modules" style="min-width: 200px;">
          </select>
          </div>

          <div style="display:none;">
          <label><b>Lessons:</b></label><select class="sel-lessons" name="lessons" id="lessons">
          </select>
          </div>

          <div class="input-block">
            <br />
            <button type="submit" name="btn-filter" id="btn-filter" class="button" >Filter</button>
            <button type="submit" name="btn-download" id="btn-download" class="button" >Download</button>
          </div>
      </div>
      
      
    </form>
</div>


<div>
  <table class="wp-list-table widefat striped table-view-list">
    <thead>
      <tr>
        <td>Id</td>
        <td>Author</td>
        <td>Assignment Title</td>
        <td>Date Posted</td>
      </tr>
    </thead>
    <?php foreach ($assignments as $assignment) { ?>
      <?php //if( $assignment->course_id == $course_id ): ?>
        <?php // if (in_array($assignment->my_user_id, $user_ids)) : ?>
          <tr>
            <td><?php echo $assignment->my_user_id; ?></td>
            <td><?php echo $assignment->user_nicename; ?></td>
            <td><?php echo $assignment->post_content; ?></td>
            <td><?php echo $assignment->post_date; ?></td>
          </tr>
     
      <?php //endif; ?>
  <?php } ?>
  </table>
</div>

<script type="text/javascript">
    jQuery(document).ready(function() {

      jQuery('.sel-teams').select2({
        placeholder: 'Teams',
        width: '20%',
        allowClear: true,
        dropdownAutoWidth: true
      });

      jQuery('.sel-courses').select2({
        placeholder: 'Course',
        width: '20%',
        allowClear: true,
        dropdownAutoWidth: true
      });

      jQuery('.sel-modules').select2({
        placeholder: 'Modules',
        width: '20%',
        allowClear: true,
        dropdownAutoWidth: true
      });

      jQuery('.sel-lessons').select2({
        placeholder: 'Lessons',
        width: '20%',
        allowClear: true
      });

     console.log('document ready');
    });


    jQuery('#teams').change(function(){
      var team_id = jQuery(this).val();

      jQuery.ajax({
        data: {
          'team_id': team_id,
          'action': 'list_courses'
        },
        url: '../wp-admin/admin-ajax.php',
        success: function(data){
          jQuery('#courses').html(data);
          console.log('success');
          console.log('Team ID:' + team_id);
          if (!jQuery.trim(data)){   
              //alert("What follows is blank: " + data);
          }
          else{   
              //alert("What follows is not blank: " + data);
              jQuery("#courses").prop("selectedIndex", 0);
          }
          
        }
      });

    }); 

    jQuery('#courses').change(function(){
      var course_id = jQuery(this).val();

      jQuery.ajax({
        data: {
          'course_id': course_id ,
          'action': 'list_modules', 
        },
       // datatype: 'json',
        url: '../wp-admin/admin-ajax.php',
        success: function(data){
          //var opts = jQuery.parseJSON(data);

         /* $.each(data, function(i, d) {
                    // You will need to alter the below to get the right values from your json object.  Guessing that d.id / d.modelName are columns in your carModels data
                    $('#emptyDropdown').append('<option value="' + d.ModelID + '">' + d.ModelName + '</option>');
                });
              */
          //jQuery('#modules').append('<option>'+ data +'</option>');
          
          jQuery('#modules').html(data);
          console.log('success');
          console.log('Courses ID:' + course_id);
          
        }
      });

      

    });

    jQuery('#modules').change(function(){
      var course_id = jQuery('#courses').val();
      var lesson_id = jQuery(this).val();

      jQuery.ajax({
        data: {
          'lesson_id': lesson_id ,
          'course_id': course_id ,
          'action': 'list_lessons', 
        },
       // datatype: 'json',
        url: '../wp-admin/admin-ajax.php',
        success: function(data){
          
          jQuery('#lessons').html(data);
          console.log('success');
          console.log('Lesson ID:' + lesson_id);
          
        }
      });

      

    });
  </script>


<style>
  .filter-form {
    display: flex;
    /* justify-content: space-evenly;*/
    margin: 20px 0px 20px;
  }

  .filter-form .select2-container {
    width: 100% !important;
  }

  .input-block {
    margin-right: 20px;
  }
</style>

