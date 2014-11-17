<?php
/**
 * Sharif Judge online judge
 * @file enrollment_model.php
 * @author Saeed Zareian
 */
defined('BASEPATH') OR exit('No direct script access allowed');

class Enrollment_model extends CI_Model
{

	public function __construct()
	{
		parent::__construct();
	}


	// ------------------------------------------------------------------------


	/**
	 * Returns all notifications
	 */
	public function get_all_enrollments()
	{
		return $this->db
			->order_by('id', 'desc')
			->get('enrollments')
			->result_array();
		
	}

	// ------------------------------------------------------------------------
	
	
	/**
	 * Returns all notifications
	 */
	public function get_all_enrollments_by_user($userid)
	{
		return $this->db
		->where('user_id', $userid)
		->order_by('id', 'desc')
		->get('enrollments')
		->result_array();
	
	}

	// ------------------------------------------------------------------------


	/**
	 * Add a new enrollment
	 */
	public function add_enrollment($userid, $classroomid, $type)
	{
		$now = shj_now_str();
		$this->db->insert('enrollments',
			array(
				'user_id' => $userid,
				'classroom_id' => $classroomid,
				'type' => $type,
				'createdat' => time()
			)
		);
	}


	// ------------------------------------------------------------------------


	/**
	 * Update (edit) a enrollment
	 */
	public function update_enrollment($id, $title, $text)
	{
		$this->db
			->where('id', $id)
			->update('enrollment',
				array(
					'title' => $title,
					'description' => $text,
					'major' => $text,
					'enrollkey' => $text
				)
			);
	}


	// ------------------------------------------------------------------------


	/**
     * Delete a notification
	 */
	public function delete_enrollment($id)
	{
		$this->db->delete('enrollments', array('id' => $id));
	}


	// ------------------------------------------------------------------------


	/**
	 * Returns a enrollment
	 */
	public function get_enrollment($enrollment_id)
	{
		$query = $this->db->get_where('enrollments', array('id' => $enrollment_id));
		if ($query->num_rows() != 1)
			return FALSE;
		return $query->row_array();
	}
	

	// ------------------------------------------------------------------------
	
	
	/**
	 * Returns a user enrollment for classroom
	 */
	public function get_user_enrollment($classroom_id)
	{
		$query = $this->db->get_where('enrollments', array('classroom_id' => $classroom_id));
		if ($query->num_rows() != 1)
			return FALSE;
		return $query->row_array();
	}


}