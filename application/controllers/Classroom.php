<?php
/**
 * Sharif Judge online judge
 * @file Classroom.php
 * @author Mohammad Javad Naderi <mjnaderi@gmail.com>
 */
defined('BASEPATH') OR exit('No direct script access allowed');

class Classroom extends CI_Controller
{

	private $classroom_edit;


	// ------------------------------------------------------------------------


	public function __construct()
	{
		parent::__construct();
		if ( ! $this->session->userdata('logged_in')) // if not logged in
			redirect('login');
		$this->load->model('classroom_model');
		$this->classroom_edit = FALSE;
		
		if ( ! $this->db->table_exists('sessions'))
			redirect('install');
	
		$this->load->model('notifications_model')->helper('text');
		
		
	}


	// ------------------------------------------------------------------------


	public function index($classroom_id)
	{
		$classroom = $this->classroom_model->get_classroom($classroom_id);
		if(!$classroom)
			show_404();
		$this->user->set_selected_assignment($classroom_id); 
		$data = array(
			//'all_assignments' => $this->assignment_model->all_assignments(),
			'notifications' => $this->notifications_model->get_all_notifications(),
			'week_start'=>$this->settings_model->get_setting('week_start'),
			'classroom'=>$classroom,
			'wp'=>$this->user->get_widget_positions(),
		);
		$this->user->set_selected_assignment($classroom_id);
		
		/*
		//->showing assignments that are related to enrolled courses.
		//-> get enrolled courses.
		$enrollments = $this->enrollment_model->get_all_enrollments_by_user($this->user->id);
			
		//-> get assignments for all endrolled courses
		if($enrollments){
			$enrollments_classrooms = array();
			for($i=0; $i<count($enrollments); $i++){
				$enrollments_classrooms[]= $enrollments[$i]['classroom_id'];
			}
				
			$data['all_assignments'] = $this->assignment_model->all_my_assignments($enrollments_classrooms);
		}*/
		$data['all_assignments'] = $this->assignment_model->all_assignments_by_classroom($classroom['id']);
		
		$this->twig->display('pages/classroom.twig', $data);

	}
	
	public function showall()
	{
		
		$data = array(
				'all_classrooms' => $this->classroom_model->get_all_classroom(),
				'notifications' => $this->notifications_model->get_all_notifications(),
		);
		for($i=0; $i< count($data['all_classrooms']); $i++){
			$instructor = $this->user_model->get_user($data['all_classrooms'][$i]['instructor']);
		
			$data['all_classrooms'][$i]['instructor'] = $instructor->name." ".$instructor->family;
		}
	
		$this->twig->display('pages/admin/classrooms.twig', $data);
	
	}


	// ------------------------------------------------------------------------


	public function add()
	{
		
		if ( $this->user->level <=1) // permission denied
			show_404();
		
		if($this->classroom_edit != null){
			$classroom = $this->classroom_edit;
		}
		if($_POST){
			$this->form_validation->set_rules('id', 'ID', 'integer');
			$this->form_validation->set_rules('name', 'Name', 'required');
			$this->form_validation->set_rules('shortname', 'Short Name', 'required');
		//	$this->form_validation->set_rules('description', 'Description', 'trim');
			$this->form_validation->set_rules('instructor', 'Instructor', 'required|integer');
			$this->form_validation->set_rules('assistant', 'Assistant', 'required|integer');
			$this->form_validation->set_rules('semester', 'Semester', 'required');
			$this->form_validation->set_rules('enrollment_key', 'Enrollment Key', 'required');
			if($this->form_validation->run()){
				if ($this->input->post('id') == ""){
					$this->classroom_model->add_classroom(
							$this->input->post('name'),
							$this->input->post('shortname'),
							strip_tags($this->input->post('description')),
							$this->input->post('enrollment_key'),
							$this->input->post('semester'),
							$this->input->post('instructor'),
							$this->input->post('assistant'),
							$this->input->post('visible') == null ? 0: $this->input->post('visible')
							
					);
				}else{
						$this->classroom_model->update_classroom(
							$this->input->post('id'),
							$this->input->post('name'),
							$this->input->post('shortname'),
							strip_tags($this->input->post('description')),
							$this->input->post('enrollment_key'),
							$this->input->post('semester'),
							$this->input->post('instructor'),
							$this->input->post('assistant'),
							$this->input->post('visible') == null ? 0: $this->input->post('visible')
					);
				}
				redirect('classrooms');
			}else{
				$classroom = array(
					"id"=>$this->input->post('id'),
					"name"=>$this->input->post('name'),
					"shortname"=>$this->input->post('shortname'),
					"description"=>strip_tags($this->input->post('description')),
					"enrollment_key"=>$this->input->post('enrollment_key'),
					"instructor"=>$this->input->post('instructor'),
					"assistant"=>$this->input->post('assistant'),
					"visible"=>$this->input->post('visible')
						
				);
				
			}
		}
		echo validation_errors('<div>', '</div>');
		
		$semesters = array(
			array("id"=> "F14", "name"=>"Fall 2014"),
			array("id"=> "W15", "name"=>"Winter 2015"),
			array("id"=> "S15", "name"=>"Summer 2015"),
			array("id"=> "F15", "name"=>"Fall 2015"),
			array("id"=> "W16", "name"=>"Winter 2016")
		);
		
		$data = array(
			'semesters'=>$semesters,
			'users' => $this->user_model->get_all_users_by_role(array("instructor","admin","head_instructor")),
			'classroom_edit' => $this->classroom_edit,
			'classroom'=>$classroom
		);
		
	
		$this->twig->display('pages/admin/add_classroom.twig', $data);

	}


	// ------------------------------------------------------------------------


	public function edit($id = FALSE)
	{
		if ($this->user->level <= 1) // permission denied
			show_404();
		if ($id === FALSE || ! is_numeric($id))
			show_404();
		$this->classroom_edit = $this->classroom_model->get_classroom($id);
		$this->add();
	}


	// ------------------------------------------------------------------------


	public function delete()
	{
		if ( ! $this->input->is_ajax_request() )
			show_404();
		if ($this->user->level <= 1) // permission denied
			$json_result = array('done' => 0, 'message' => 'Permission Denied');
		elseif ($this->input->post('id') === NULL)
			$json_result = array('done' => 0, 'message' => 'Input Error');
		else
		{
			$this->notifications_model->delete_notification($this->input->post('id'));
			$json_result = array('done' => 1);
		}

		$this->output->set_header('Content-Type: application/json; charset=utf-8');
		echo json_encode($json_result);
	}


	// ------------------------------------------------------------------------


	public function check()
	{
		if ( ! $this->input->is_ajax_request() )
			show_404();
		$time  = $this->input->post('time');
		if ($time === NULL)
			exit('error');
		if ($this->notifications_model->have_new_notification(strtotime($time)))
			exit('new_notification');
		exit('no_new_notification');
	}
	// ------------------------------------------------------------------------

	
}