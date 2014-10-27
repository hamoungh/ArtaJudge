<?php
/**
 * Sharif Judge online judge
 * @file Dashboard.php
 * @author Mohammad Javad Naderi <mjnaderi@gmail.com>
 */
defined('BASEPATH') OR exit('No direct script access allowed');

class Dashboard extends CI_Controller
{
	public $messages;

	public function __construct()
	{
		parent::__construct();
		if ( ! $this->db->table_exists('sessions'))
			redirect('install');
		if ( ! $this->session->userdata('logged_in')) // if not logged in
			redirect('login');
		$this->load->model('notifications_model')->helper('text');
	}


	// ------------------------------------------------------------------------


	public function index()
	{
		$data = array(
			'all_assignments'=>$this->assignment_model->all_assignments(),
			'week_start'=>$this->settings_model->get_setting('week_start'),
			'wp'=>$this->user->get_widget_positions(),
			'notifications' => $this->notifications_model->get_latest_notifications()
		);

		// detecting errors:
		if($this->messages != null)//summing up with the errors from enrollment
			$data['errors'] =$this->messages;
		else 
			$data['errors'] = array();
		if($this->user->level === 3){
			$path = $this->settings_model->get_setting('assignments_root');
			if ( ! file_exists($path))
				array_push($data['errors'], 'The path to folder "assignments" is not set correctly. Move this folder somewhere not publicly accessible, and set its full path in Settings.');
			elseif ( ! is_writable($path))
				array_push($data['errors'], 'The folder <code>"'.$path.'"</code> is not writable by PHP. Make it writable. But make sure that this folder is only accessible by you. Codes will be saved in this folder!');

			$path = $this->settings_model->get_setting('tester_path');
			if ( ! file_exists($path))
				array_push($data['errors'], 'The path to folder "tester" is not set correctly. Move this folder somewhere not publicly accessible, and set its full path in Settings.');
			elseif ( ! is_writable($path))
				array_push($data['errors'], 'The folder <code>"'.$path.'"</code> is not writable by PHP. Make it writable. But make sure that this folder is only accessible by you.');
		}
		if($this->user->level >= 0){
			//$data['classrooms'] = $this->classroom_model->all_classrooms();	
		}
		$enrollments = $this->enrollment_model->get_all_enrollments_by_user($this->user->id);
		$c = count($enrollments);
		for( $i=0; $i<$c;$i++){
			if($enrollments[$i] === FALSE || $enrollments[$i]['type'] != "enroll"){
				unset($enrollments[$i]);
			}else{
				$enrollments[$i]= $this->classroom_model->get_classroom($enrollments[$i]['classroomid']);
				
			}
		}
		$enrollments= array_values($enrollments);
		$data['courses'] = $enrollments;
		//showing list of courses to let user enroll
		$data['all_courses']= $this->classroom_model->get_all_classroom();
		//-> removing the courses that are enrolled before
		//first make a clean list of enrollments 
		for($i=0; $i<count($enrollments); $i++){
			$enrolled_course[]= $enrollments[$i]['id'];
		}
		//pulling out 
		$c= count($data['all_courses']);
		for($i=0; $i<$c; $i++){
			if( in_array($data['all_courses'][$i]['id'], $enrolled_course)){
				unset($data['all_courses'][$i]);
			}
		}
			
		
		$this->twig->display('pages/dashboard.twig', $data);
	}


	// ------------------------------------------------------------------------

	/**
	 * Used by ajax request, for saving the user's Dashboard widget positions
	 */
	public function widget_positions()
	{
		if ( ! $this->input->is_ajax_request() )
			show_404();
		if ($this->input->post('positions') !== NULL)
			$this->user->save_widget_positions($this->input->post('positions'));
	}
	/**
	 * Course enrollment
	 */
	public function enroll()
	{
		$this->form_validation->set_rules('course_id', 'Course Name', 'required');
		$this->form_validation->set_rules('enrollment_key', 'Enrollment Key', 'required'); /* todo: xss clean */
	
		if($this->form_validation->run()){
			$course = $this->classroom_model->get_classroom($this->input->post('course_id'));
			if(!$course){
				$this->messages[] = "Error: Course not exists.";
			}else if($course['enrollment_key'] !== $this->input->post('enrollment_key')){
				$this->messages[] = "Error: wrong enrollment key.";	
			}else if($this->enrollment_model->get_user_enrollment($this->input->post('course_id'),'enroll')){
				$this->messages[] = "Error: You have enrolled before.";
			}else{
				$ret = $this->enrollment_model->add_enrollment($this->user->id, $course['id'],'enroll');
			}
			
		}
		//redirect('dashboard');
		$this->index();
	
	}
}