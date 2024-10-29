<?php
/**
 * Plugin Name: B Forms
 * Plugin URI: https://bforms.app
 * Description: Add your custom built form in bforms.app to your wordpress projects
 * Version:     1.0.0
 * Author:      Arun Thomas
 * Text Domain: b-forms
 * License:     GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 */

add_shortcode('b_form' , 'b_form_render_form');
add_shortcode('b_form_data' , 'b_form_render_form_data');

function b_form_render_form_data($atts){ 
	$table_domain = "https://bforms.app";
	$table_url = $table_domain."/form-submissions-for-plugin/".$atts['token'];
	$table = wp_remote_retrieve_body(wp_remote_get($table_url));
	$table = json_decode($table, true);

	$submissions = $table['submissions'];
	$form = $table['form'];
	$fields = $table['fields'];
	$output = '';

	if(count($submissions) > 0){
	$output.='<style type="text/css">
		.table{
			background-color: white !important;
		}
	</style>
	<div class="card-body wp-block-table table-responsive">
		<table class="table table-striped">
			<thead>
				<tr>
					<th>Sl. No.</th>
					<th>Submission Time</th>';
					foreach($fields as $field){
					$output.='<th>'.$field['field_label'].'</th>';
					}
				$output.='</tr>
			</thead>
			<tbody>';
				foreach(array_chunk($submissions, count($fields)) as $key => $submission_chunk){
				$output.='<tr>
					<td>'.($key+1).'</td>
					<td>'.$submission_chunk[0]['created_at'].'</td>';
					foreach($submission_chunk as $submission){
					$output.='<td>'.$submission['submission_value'].'</td>';
					}
				$output.='</tr>';
				}
			$output.='</tbody>
		</table>
	</div>';
	}else{
	$output.='<div class="container" style="background-color: blue !important; color: white !important; padding: 10px !important">
		<h4>No Submissions found for this form.</h4>
	</div>';
	}

	return $output;
}

function b_form_render_form($atts){ 
	$domain = "https://bforms.app";
	$url = $domain."/form-data/".$atts['token'];
	$data = wp_remote_retrieve_body(wp_remote_get($url));
	$data = json_decode($data, true);

	$fields = $data['fields'];
	$form = $data['form'];
	$c_codes = $data['c_codes'];
	$csrf_token = $data['csrf_token'];
	$output = '';
	
	if(count($fields) > 0){
		if($form['status'] == 1){
			$output.='<style type="text/css">
				.hide{
					display: none !important;
				}
				.form-header, .form-body{
					padding: 10px !important;
				}
			</style>';

			if($_GET['form-submitted'] == 1){
			$output.='<div class="container" style="background-color: green !important; color: white !important; padding: 10px !important; text-align: center !important">
				<h4>Form Submitted</h4>
			</div>';
			}
			$current_url = get_permalink(get_the_ID());
			$current_url = str_replace("form-submitted=1", "", $current_url);			 

			$output.='<script type="text/javascript">
				window.history.pushState("", "", "'.$current_url.'");
			</script>

			<div class="card-header form-header" style="background-color: '.$form['h_bg_color'].' !important; color: '.$form['h_text_color'].' !important">';
				if($form['logo_file'] != null){
				$output.='<div class="row">
					<div class="col-md-4">';
						if($form['logo_position'] == 'left'){
							$output.='<img src="'.$domain.'/img/logo/'.$form['logo_file'].'" class="position-relative float-'.$form['logo_position'].'" width="'.$form['logo_width'].'">';
						}
					$output.='</div>
					<div class="col-md-4 text-center">';
						if($form['logo_position'] == 'center'){
							$output.='<img src="'.$domain.'/img/logo/'.$form['logo_file'].'" width="'.$form['logo_width'].'">';
						}
						$output.='<h2>'.$form['form_name'].'</h2>
						<h6>'.$form['form_subtitle'].'</h6> 
					</div>
					<div class="col-md-4">';
						if($form['logo_position'] == 'right'){
							$output.='<img src="'.$domain.'/img/logo/'.$form['logo_file'].'" class="position-relative float-'.$form['logo_position'].'" width="'.$form['logo_width'].'">';
						}
					$output.='</div>
				</div>';
				}
				else{
				$output.='<h2 class="text-center">'.$form['form_name'].'</h2>
				<h6 class="text-center">'.$form['form_subtitle'].'</h6>';
				}
			$output.='</div>

			<div class="card-body form-body" style="background-color: '.$form['b_bg_color'].' !important; color: '.$form['b_text_color'].' !important">
				<div class="row">

					<div class="col-lg-12"> 
						<form role="form" method="get" action="'.$domain.'/submit-form-from-plugin">
							<input type="text" class="hide" name="form_id" value="'.$form['id'].'">';

							foreach($fields as $key => $field){
								$output.='<input type="text" class="hide" name="field_ids[]" value="'.$field['id'].'">
								<input type="text" class="hide" name="submission_labels[]" value="'.$field['field_label'].'">
								<input type="text" class="hide" name="submission_types[]" value="'.$field['field_type'].'">
								<input type="text" class="hide" name="submission_input_types[]" value="'.$field['field_input_type'].'">';

								switch($field['field_type']){
									case 'Input':
										$output.='<div class="form-group row">
											<label class="col-lg-3 text-lg-right">';
												$output.=$field['field_label'];
												if($field['field_input_type'] == 'number'){
												$output.='<small><i>('.$field['field_input_type'].')</i></small>';
												}
												if($field['field_mandatory'] == 0){
												$output.='<small><i>(optional)</i></small>';
												}
												$output.=':
												<br>
											</label>';

											if($field['field_input_type'] == 'phone'){
												$output.='<select class="form-control col-lg-2 selectpicker" data-live-search="true"'; if($field['field_mandatory'] == 1){ $output.=' required ';} $output.='name="submission_code_values[]" style="width: 29%">';
													foreach($c_codes as $code){
													$output.='<option value="'.$code['dial_code'].'">'.$code['dial_code'].' ('.$code['name'].')</option>';
													}
												$output.='</select>
												<input type="number" class="form-control col-lg-5"'; if($field['field_mandatory'] == 1){ $output.=' required ';} $output.='name="submission_phone_values[]" style="width: 69%">

												<input type="text" name="submission_values[]" class="hide">';
											}
											else{
												$output.='<input type="text" name="submission_code_values[]" class="hide">
												<input type="text" name="submission_phone_values[]" class="hide">

												<input type="'.$field['field_input_type'].'" name="submission_values[]" class="form-control col-lg-7"'; if($field['field_input_type'] == 'date'){ $output.=' value="'.date('Y-m-d').'" ';} if($field['field_mandatory'] == 1){ $output.=' required ';} $output.='style="width: 100%">';
											}
										$output.='</div>';
										break;
									case 'Single Selection':
									case 'Multiple Selection':
										$output.='<div class="form-group row">
											<label class="col-lg-3 text-lg-right">';
												$output.=$field['field_label'];
												if($field['field_mandatory'] == 0){
												$output.='<small><i>(optional)</i></small>';
												}
												$output.=':
											</label>';
												$field_values = explode(",", $field['field_values']);

											$output.='<select class="selectpicker form-control col-lg-7" data-live-search="true" '; if($field['field_type'] == 'Multiple Selection'){ $output.=' multiple name="submission_values_'.$key.'[] "'; }else{ $output.=' name="submission_values[]" '; } $output.='data-size="5" '; if($field['field_mandatory'] == 1){ $output.=' required ';} $output.=' style="width: 100%">';
												foreach($field_values as $field_value){
													$output.='<option value="'.$field_value.'">'.$field_value.'</option>';
												}
											$output.='</select>
										</div>';
										break;
									case 'Radio Buttons':
										$output.='<div class="form-group row">
											<label class="col-lg-3 text-lg-right">';
												$output.=$field['field_label'];
												if($field['field_mandatory'] == 0){
												$output.='<small><i>(optional)</i></small>';
												}
												$output.=':
											</label>';
												$field_values = explode(",", $field['field_values']);
											
											foreach($field_values as $field_value){
											$output.='<div class="form-check mx-1">
					                          	<input class="form-check-input" type="radio" name="submission_values[]" value="'.$field_value.'" '; if($field['field_mandatory'] == 1){ $output.=' required ';} $output.='>
					                          	<label class="form-check-label">'.$field_value.'</label>
					                        </div>';
											}
										$output.='</div> ';
										break;
									default:
										break;
								}

								if($field['field_type'] == 'Multiple Selection'){
									$output.='<input type="text" name="submission_values[]" value="dummy" class="hide">';
								}

								if($field['field_type'] != 'Input'){
									$output.='<input type="text" name="submission_code_values[]" class="hide">
									<input type="text" name="submission_phone_values[]" class="hide">';
								}
							}

							$output.='<input type="text" name="redirect_url" value="'.get_permalink(get_the_ID()).'" class="hide">

							<div class="form-group offset-lg-3">
								<br>
								<button class="btn btn-success" style="background-color: '.$form['b_text_color'].' !important; color: '.$form['b_bg_color'].' !important; padding: 10px !important">'.$form['submit_btn_text'].'</button>
							</div>
						</form>
					</div>
				</div>
			</div>';

		}
		else{
			$output.='<div class="container" style="background-color: orange !important; color: white !important; padding: 10px !important">
					<h4>Added form is disabled. Please goto bforms.app and enable this form.</h4>
				</div>';
		}
	}
	else{
		$output.='<div class="container" style="background-color: orange !important; color: white !important; padding: 10px !important">
				<h4>There was an error rendering the form. Please check and confirm the form token.</h4>
			</div>';
	}

	return $output;
 
}