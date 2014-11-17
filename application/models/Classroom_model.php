<?php
/**
 * Sharif Judge online judge
 * @file Classroom_model.php
 * @author Saeed Zareian
 */
defined('BASEPATH') OR exit('No direct script access allowed');

class Classroom_model extends CI_Model
{

	public function __construct()
	{
		parent::__construct();
	}


	// ------------------------------------------------------------------------


	/**
	 * Returns all notifications
	 */
	public function get_all_classroom()
	{
		return $this->db
			->order_by('id', 'desc')
			->get('classrooms')
			->result_array();
	}


	// ------------------------------------------------------------------------


	/**
	 * Add a new classroom
	 */
	public function add_classroom(	$name,
							$shortname,
							$description,
							$enrollment_key,
							$semester,
							$instructor,
							$assistant,
							$visible){
		$now = shj_now_str();
		var_dump($now);
		$this->db->insert('classrooms',
			array(
				'name' => $name,
				'shortname' => $shortname,
				'description' => $description,
				'enrollment_key' => $enrollment_key,
				'semester' => $semester,
				'instructor' => $instructor,
				'assistant' => $assistant,
				'visible' => $visible,
				'createdat' => $now
			)
		);
	}


	// ------------------------------------------------------------------------


	/**
	 * Update (edit) a classroom
	 */
	public function update_classroom($id,$name,
							$shortname,
							$description,
							$enrollment_key,
							$semester,
							$instructor,
							$assistant,
							$visible)
	{
		$this->db
			->where('id', $id)
			->update('classrooms',
				array(
					'name' => $name,
					'shortname' => $shortname,
					'description' => $description,
					'enrollment_key' => $enrollment_key,
					'semester' => $semester,
					'instructor' => $instructor,
					'assistant' => $assistant,
					'visible' => $visible
				)
			);
	}


	// ------------------------------------------------------------------------


	/**
     * Delete a notification
	 */
	public function delete_classroom($id)
	{
		$this->db->delete('classrooms', array('id' => $id));
	}


	// ------------------------------------------------------------------------


	/**
	 * Returns a classroom
	 */
	public function get_classroom($classroom_id)
	{
		$query = $this->db->get_where('classrooms', array('id' => $classroom_id));
		if ($query->num_rows() != 1)
			return FALSE;
		return $query->row_array();
	}



}