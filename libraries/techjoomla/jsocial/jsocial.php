<?php
/**
 * @package     Techjoomla.Libraries
 * @subpackage  JSocial
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2019 Techjoomla. All rights reserved.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

defined('JPATH_BASE') or die;

use Joomla\CMS\User\User;

/**
 * Interface to handle Social Extensions
 *
 * @package     Joomla.Libraries
 * @subpackage  JSocial
 * @since       3.1
 */

interface JSocial
{
	/**
	 * The function to get profile data of User
	 *
	 * @param   MIXED  $user  JUser Objcet
	 *
	 * @return  JUser Objcet
	 *
	 * @since   1.0
	 */
	public function getProfileData(User $user);

	/**
	 * The function to get profile link User
	 *
	 * @param   MIXED    $user      JUser Objcet
	 * @param   BOOLEAN  $relative  returns relative URL if true
	 *
	 * @return  STRING
	 *
	 * @since   1.0
	 */
	public function getProfileUrl(User $user, $relative = false);

	/**
	 * The function to get profile AVATAR of a User
	 *
	 * @param   MIXED    $user           JUser Objcet
	 *
	 * @param   INT      $gravatar_size  Size of the AVATAR
	 *
	 * @param   BOOLEAN  $relative       returns relative URL if true
	 *
	 * @return  STRING
	 *
	 * @since   1.0
	 */
	public function getAvatar(User $user, $gravatar_size = '', $relative = false);

	/**
	 * The function to get friends of a User
	 *
	 * @param   MIXED  $user      JUser Objcet
	 * @param   INT    $accepted  Optional param, bydefault true to get only friends with request accepted
	 * @param   INT    $options   Optional array.. Extra options to pass to the getFriends Query
	 *
	 * @return  StdClass[] objects
	 *
	 * @since   1.0
	 */
	public function getFriends(User $user, $accepted=true, $options = array());

	/**
	 * The function to add provided users as Friends
	 *
	 * @param   MIXED  $connect_from_user  User who is requesting connection
	 * @param   INT    $connect_to_user    User whom to request
	 *
	 * @return  void
	 *
	 * @since   1.0
	 */
	public function addFriend(User $connect_from_user, User $connect_to_user);

	/**
	 * Add activity stream
	 *
	 * @param   INT     $actor_id         User against whom activity is added
	 * @param   STRING  $act_type         type of activity
	 * @param   STRING  $act_subtype      sub type of activity
	 * @param   STRING  $act_description  Activity description
	 * @param   STRING  $act_link         LInk of Activity
	 * @param   STRING  $act_title        Title of Activity
	 * @param   STRING  $act_access       Access level
	 *
	 * @return  true
	 *
	 * @since  1.0
	 */
	public function pushActivity($actor_id, $act_type, $act_subtype, $act_description, $act_link, $act_title, $act_access);

	/**
	 * The function to set status of a user
	 *
	 * @param   MIXED   $user     User whose status is to be set
	 * @param   STRING  $status   status to be set
	 * @param   MIXED   $options  status to be set
	 *
	 * @return  void
	 *
	 * @since   1.0
	 */
	public function setStatus(User $user, $status, $options);

	/**
	 * Send Notification
	 *
	 * @param   OBJECT  $sender        User who is sending notification
	 * @param   OBJECT  $receiver      User to whom notification is to send
	 * @param   STRING  $content       Main content of the notification
	 * @param   STRING  $options       Optional options
	 * @param   STRING  $emailOptions  Email options. If you do not want to send email, $emailOptions should be set to false
	 *
	 * @return  boolean
	 *
	 * @since  1.0
	 */
	public function sendNotification(User $sender, User $receiver, $content = "JS Notification", $options = array(), $emailOptions = false);

	/**
	 * The function to get registartion link for Easysocial
	 *
	 * @param   ARRAY  $options  options
	 *
	 * @return  void
	 *
	 * @since   1.0
	 */
	public function getRegistrationLink($options);

	/**
	 * The function to check if Easysocial is installed
	 *
	 * @return  boolean
	 *
	 * @since   1.0
	 */
	public function checkExists();

	/**
	 * The function add points to user
	 *
	 * @param   MIXED  $receiver  User to whom points to be added
	 * @param   ARRAY  $options   is array
	 *
	 * $options[command] for example invites sent
	 * options[extension] for example com_invitex
	 *
	 * @return ARRAY success 0 or 1
	 */
	public function addpoints(User $receiver, $options=array());

	/**
	 * The function to get Easysocial toolbar
	 *
	 * @return  string  toolbar HTML
	 *
	 * @since   1.0
	 */
	public function getToolbar();

	/**
	 * The function to create a group
	 *
	 * @param   ARRAY  $data     Data
	 * @param   ARRAY  $options  Additional data
	 *
	 * @return  void
	 *
	 * @since   1.0
	 */
	public function createGroup($data, $options=array());

	/**
	 * The function to add member to a group
	 *
	 * @param   ARRAY   $groupId      Data
	 * @param   OBJECT  $groupmember  User object
	 *
	 * @return  false|true could be false, could be a true
	 *
	 * @since   1.0
	 */
	public function addMemberToGroup($groupId, User $groupmember);

	/**
	 * The function to add stream
	 *
	 * @param   Array  $streamOption  Stram array
	 *
	 * @return  true|string could be true, could be a string
	 *
	 * @since   1.0
	 */
	public function advPushActivity($streamOption);

	/**
	 * The function to update the custom fields
	 *
	 * @param   ARRAY   $fieldsArray  Custom field array
	 * @param   OBJECT  $userId       User Id
	 *
	 * @return  true|string could be true, could be a string
	 *
	 * @since   1.0
	 */
	public function addUserFields($fieldsArray, $userId);
}
