<?php
/**
 * Sharif Judge online judge
 * @file User_model.php
 * @author Mohammad Javad Naderi <mjnaderi@gmail.com>
 */
defined('BASEPATH') OR exit('No direct script access allowed');

class User extends CI_Model
{
	public $id;
	public $username;
	public $selected_assignment;
	public $selected_classroom;
	public $level;
	public $email;

	public function __construct()
	{
		parent::__construct();
		$this->username = $this->session->userdata('username');
		if ($this->username === NULL)
			return;

		$user = $this->db
			->select('selected_assignment, selected_classroom, role, email, id')
			->get_where('users', array('username' => $this->username))
			->row();

		$this->email = $user->email;
		$this->id = $user->id;
		
		//$query = $this->db->get_where('assignments', array('id' => $user->selected_assignment));
		
		$query = $this->db->get_where('classrooms', array('id' => $user->selected_classroom));
		if ($query->num_rows() != 1)
			$this->selected_classroom = array(
					'id' => 0,
					'name' => 'Not Selected',
			);
			else
				$this->selected_classroom = $query->row_array();
		
		switch ($user->role)
		{
			case 'admin': $this->level = 3; break;
			case 'head_instructor': $this->level = 2; break;
			case 'instructor': $this->level = 1; break;
			case 'student': $this->level = 0; break;
		}
	}
	public function set_selected_assignment($classroom_id){
		$selected = $this->session->userdata('selected_assignment');
		if(isset($selected[$classroom_id])){
			$query = $this->db->get_where('assignments', array('id' => $selected[$classroom_id]));
			if ($query->num_rows() == 1){
				$this->selected_assignment = $query->row_array();
				return;
			}
		}
		$this->selected_assignment = array(
				'id' => 0,
				'name' => 'Not Selected',
				'finish_time' => 0,
				'extra_time' => 0,
				'problems' => 0
		);
	}
	// ------------------------------------------------------------------------


	/**
	 * Select Assignment
	 *
	 * Sets selected assignment for $username
	 *
	 * @param $assignment_id
	 */
	public function select_assignment($classroom_id, $assignment_id)
	{
		//$this->db->where('username', $this->username)->update('users', array('selected_assignment'=>$assignment_id));
		$this->session->set_userdata('selected_assignment', array($classroom_id=> $assignment_id));
	}


	// ------------------------------------------------------------------------


	/**
	 * Save Widget Positions
	 *
	 * Updates position of dashboard widgets in database
	 *
	 * @param $positions
	 */
	public function save_widget_positions($positions)
	{
		$this->db
			->where('username', $this->username)
			->update('users', array('dashboard_widget_positions'=>$positions));
	}


	// ------------------------------------------------------------------------


	/**
	 * Get Widget Positions
	 *
	 * Returns positions of dashboard widgets from database
	 *
	 * @param none
	 * @return mixed
	 */
	public function get_widget_positions()
	{
		return json_decode(
			$this->db->select('dashboard_widget_positions')
			->get_where('users', array('username' => $this->username))
			->row()
			->dashboard_widget_positions,
			TRUE
		);
	}

}