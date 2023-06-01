<?php

// Get all the list of groups or teams
$groups = learndash_get_groups();

usort($groups, function($grp_name_a, $grp_name_b){
        return $grp_name_a->post_name <=> $grp_name_b->post_name;
});

$grp_name = array();
/*foreach($groups as $ld_group_name) {
     $grp_name[] = $ld_group_name->post_name;
}*/


//$courses = learndash_get_all_courses_with_groups();
//$courses = learndash_get_group_courses_list('34688');

//$courses = learndash_user_get_enrolled_courses( get_current_user_id());
//print_r($courses);
$course_name = array();
foreach($courses as $ld_course_id) {
    $course_name[] = get_post($ld_course_id);

   // $modules = learndash_get_course_lessons_list($ld_course_id);
}

// // These are lessons under a course
$ajax_course_id = $_POST['course_id'];

// if(isset($ajax_course_id)) {
//   echo "Hello:" . $ajax_course_id;
//   print_r($_POST);
// }

// $modules = learndash_get_course_lessons_list( int|WP_Post|null $course_id = null,  int|null $user_id = null,  array $query_args = array() )



//print_r($course_name);


// get all assignments
//$assignments = array('apple', 'oranges', 'grapes');
$my_assignments = array();
$my_assignments = learndash_get_user_assignments('19','591');
//$assignments = learndash_get_course_assignments();

//echo "what the" . $my_assignments;
//print_r($my_assignments);
?>

<h1>Assignments</h1>
<div>
    <label><b>Team:</b></label><select class="sel-teams" name="teams" id="teams">
    <?php foreach($groups as $ld_group_name) { ?>
    <option value="<?php echo $ld_group_name->ID; ?>"><?php echo $ld_group_name->post_name; ?></option>

    <?php } ?>
    </select>

    <label><b>Courses:</b></label>
    <select class="sel-courses" name="courses" id="courses">
    </select>

    <label><b>Modules:</b></label><select class="sel-modules" name="modules" id="modules" style="min-width: 200px;">
    </select>

    <label><b>Lessons:</b></label><select class="sel-lessons" name="lessons" id="lessons">
    </select>

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

