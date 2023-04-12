<?php
$groups = learndash_get_groups();
//print_r($groups);
usort($groups, function($grp_name_a, $grp_name_b){
        return $grp_name_a->post_name <=> $grp_name_b->post_name;
});
$grp_name = array();
/*foreach($groups as $ld_group_name) {
     $grp_name[] = $ld_group_name->post_name;
}*/


//$courses = learndash_get_all_courses_with_groups();
$courses = learndash_user_get_enrolled_courses( get_current_user_id());
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
?>

<h1>Assignments</h1>
<div>
    <label><b>Team:</b></label><select class="sel-teams" name="team">
    <?php foreach($groups as $ld_group_name) { ?>
    <option value="<?php echo $ld_group_name->ID; ?>"><?php echo $ld_group_name->post_name; ?></option>

    <?php } ?>
    </select>

    <label><b>Courses:</b></label>
    <select class="sel-courses" name="courses" id="courses">
    <?php foreach($course_name as $cvalue) { ?>
    <option value="<?php echo $cvalue->ID; ?>"><?php echo $cvalue->post_title; ?></option>

    <?php } ?>
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
          console.log('Courses ID:' + lesson_id);
          
        }
      });

      

    });
  </script>

