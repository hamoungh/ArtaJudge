<?php
/**
 * Sharif Judge online judge
 * @file Problems.php
 * @author Mohammad Javad Naderi <mjnaderi@gmail.com>
 */
defined('BASEPATH') OR exit('No direct script access allowed');

class Problems extends CI_Controller
{

	//private $all_assignments;


	// ------------------------------------------------------------------------


	public function __construct()
	{
		parent::__construct();
		if ( ! $this->session->userdata('logged_in')) // if not logged in
			redirect('login');

		//$this->all_assignments = $this->assignment_model->all_assignments();
	}


	// ------------------------------------------------------------------------


	/**
	 * Displays detail description of given problem
	 *
	 * @param int $assignment_id
	 * @param int $problem_id
	 */
	public function index($classroom_id = null, $assignment_id = NULL, $problem_id = 1)
	{
		if($classroom_id == null){
			show_error('Should choose a classroom');
		}
		$this->user->set_selected_assignment($classroom_id);
		
		// If no assignment is given, use selected assignment
		if ($assignment_id === NULL)
			$assignment_id = $this->user->selected_assignment['id'];
		if ($assignment_id == 0) {
			$this->session->set_flashdata('message', 'No assignment selected. Select one of assignments below.');
			redirect(site_url('classroom/'.$classroom_id.'/assignments'), 'refresh');
		}			
		$this->user->select_assignment($classroom_id, $assignment_id); // they may come with direct link from calendar
		
		

		$assignment = $this->assignment_model->assignment_info($assignment_id);
		
		if (!$assignment['published'] &&  $this->user->level == 0)
			show_error('Assignment not accessible anymore for students.');
					
		$data = array(
			'assignment' => $assignment,
			'all_assignments' => $this->assignment_model->all_assignments_by_classroom($classroom_id),
			'all_problems' => $this->assignment_model->all_problems($assignment_id),
			'description_assignment' => $assignment,
			'classroom' => $this->classroom_model->get_classroom($assignment['classroom_id']),
			'can_submit' => TRUE,
		);

		if ( ! is_numeric($problem_id) || $problem_id < 1 || $problem_id > $data['description_assignment']['problems'])
			show_404();

		$languages = explode(',',$data['all_problems'][$problem_id]['allowed_languages']);

		$assignments_root = rtrim($this->settings_model->get_setting('assignments_root'),'/');
		$problem_dir = "$assignments_root/assignment_{$assignment_id}/p{$problem_id}";
		$data['problem'] = array(
			'id' => $problem_id,
			'description' => '<p>Description not found</p>',
			'allowed_languages' => $languages,
			'has_pdf' => glob("$problem_dir/*.pdf") != FALSE
		);

		$path = "$problem_dir/desc.html";
		if (file_exists($path))
			$data['problem']['description'] = file_get_contents($path);

		if ( $assignment['id'] == 0
			OR ( $this->user->level == 0 && ! $assignment['open'] )
			OR ( $this->user->level == 0 && ! $assignment['published'] )
			OR shj_now() < strtotime($assignment['start_time'])
			OR shj_now() > strtotime($assignment['finish_time'])+$assignment['extra_time'] // deadline = finish_time + extra_time
			OR ! $this->assignment_model->is_participant($assignment['participants'], $this->user->username)
		)
			$data['can_submit'] = FALSE;

		$this->twig->display('pages/problems.twig', $data);
	}


	// ------------------------------------------------------------------------


	/**
	 * Edit problem description as html/markdown
	 *
	 * $type can be 'md', 'html', or 'plain'
	 *
	 * @param string $type
	 * @param int $assignment_id
	 * @param int $problem_id
	 */
	public function edit($type = 'md', $assignment_id = NULL, $problem_id = 1)
	{
		if ($type !== 'html' && $type !== 'md' && $type !== 'plain')
			show_404();

		if ($this->user->level <= 1)
			show_404();

		switch($type)
		{
			case 'html':
				$ext = 'html'; break;
			case 'md':
				$ext = 'md'; break;
			case 'plain':
				$ext = 'html'; break;
		}

		if ($assignment_id === NULL)
			$assignment_id = $this->user->selected_assignment['id'];
		if ($assignment_id == 0)
			show_error('No assignment selected.');
	
		$assignment = $this->assignment_model->assignment_info($assignment_id);
		
		$data = array(
			'all_assignments' => $this->assignment_model->all_assignments_by_classroom($assignment->classroom_id),
			'description_assignment' => $this->assignment_model->assignment_info($assignment_id),
			'classroom' => $this->classroom_model->get_classroom($assignment['classroom_id'])
				
		);

		if ( ! is_numeric($problem_id) || $problem_id < 1 || $problem_id > $data['description_assignment']['problems'])
			show_404();

		$this->form_validation->set_rules('text', 'text' ,''); /* todo: xss clean */
		if ($this->form_validation->run())
		{
			$this->assignment_model->save_problem_description($assignment_id, $problem_id, $this->input->post('text'), $ext);
			redirect('problems/'.$assignment_id.'/'.$problem_id);
		}

		$data['problem'] = array(
			'id' => $problem_id,
			'description' => ''
		);

		$path = rtrim($this->settings_model->get_setting('assignments_root'),'/')."/assignment_{$assignment_id}/p{$problem_id}/desc.".$ext;
		if (file_exists($path))
			$data['problem']['description'] = file_get_contents($path);


		$this->twig->display('pages/admin/edit_problem_'.$type.'.twig', $data);

	}


}