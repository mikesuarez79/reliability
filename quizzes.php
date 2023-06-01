<?php

global $wpdb;


//$course_ids = array();

//$course_ids = learndash_get_all_course_ids();

//print_r($course_ids);

//echo 'hello';


// get all course ids

// function learndash_get_all_course_ids() {
// 	$query_args = array(
// 		'post_type'			=>	'sfwd-courses',
// 		'post_status'		=>	'publish',
// 		'fields'			=>	'ids',
// 		'orderby'			=>	'title',
// 		'order'				=>	'ASC',
// 		'nopaging'			=>	true	// Turns OFF paging logic to get ALL courses
// 	);

// 	$query = new WP_Query( $query_args );
// 	if ( $query instanceof WP_Query) {
// 		return $query->posts;
// 	}
// }


// Query to get all the quizzes
$qry = "SELECT id, quiz_id, `online`, title, question FROM wp_wp_pro_quiz_question where `online` = 1";
$sql_query    = $wpdb->prepare($qry, 1) ;
$rows         = $wpdb->get_results($sql_query, ARRAY_A);

$ctr = 0; 
?>
<table class="widefat striped">
	<thead>
		<tr>
			<th>No.</th><th>Title</th><th>Question ID</th><th>Question</th><th>No. of Correct</th><th>No. of Incorrect</th>
		</tr>
	</thead>

<tbody>
<?php foreach($rows as $Record):?>

<?php
	$q_id = $Record['id'];
	$qry_sql_correct = "SELECT count(question_id) as correct_ans FROM wp_wp_pro_quiz_statistic where question_id = $q_id AND correct_count = 1";
	$qry_correct    = $wpdb->prepare($qry_sql_correct, 1) ;
	$correct = $wpdb->get_results($qry_correct, ARRAY_A);


	$qry_sql_incorrect = "SELECT count(question_id) as incorrect_ans FROM wp_wp_pro_quiz_statistic where question_id = $q_id AND correct_count = 0";
	$qry_incorrect    = $wpdb->prepare($qry_sql_incorrect, 1) ;
	$incorrect = $wpdb->get_results($qry_incorrect, ARRAY_A);
	
	$ctr++;
?>	
	<tr>
		<td><?php echo $ctr; ?></td>
		<td style="width: 70%;"><?php echo $Record['title']; ?></td>
		<td style="width: 10%;"><?php echo $Record['id']; ?></td>
		<td style="width: 70%;"><?php echo $Record['question']; ?></td>
		<td style="width: 10%;"><?php echo $correct[0]['correct_ans']; ?></td>
		<td style="width: 10%;"><?php echo $incorrect[0]['incorrect_ans']; ?></td>
	</tr>
<?php endforeach; ?>
</tbody>
</table>