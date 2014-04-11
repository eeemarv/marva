<?php
/**
 * copyleft 2014 martti <info@martti.be>
 * 
 * Copyright(C) 2009 Guy Van Sanden <guy@vsbnet.be>
 * see LICENSE
*/

/** Provided functions:
 * get_user_maildetails($userid) 	Return the user with mailaddress if available

 * get_users() 				Get an array of all users
 * get_user($id) 			Get an array with userdetails
 * get_user_letscode($id) 		Get the user letscode

 * get_user_by_letscode($letscode)	Get the userarray from a letscode
 * get_user_by_name($name)		Get the user by fullname (should return 1 result)

 * get_contact($user)			Get all contact information for the user
 * get_contacttype($abbrev)		Get contacttype by abbreviation
 * get_letsgroups()			Get all the interlets Groups
 * get_letsgroup($id)			Get the letsgroup by id
*/

function getTypeAheadUsers($include_interlets = true){
	global $db, $parameters;	
	$query = 'select letscode as c, 
		name as n, 
		maxlimit as l,
		saldo as b,
		unix_timestamp(adate) as a,
		status
		from users 
		where status in (1, 2, 4'.(($include_interlets) ? ', 7' : '').')';
		
	$typeahead_users = $db->fetchAll($query); 

	$newUserTime = time() - 86400 * $parameters['new_user_days'];

	foreach ($typeahead_users as &$row){
		$row['a'] = ($row['a'] > $newUserTime) ? 1 : 0;
		$row['le'] = ($row['status'] == 2) ? 1 : 0;
		$row['s'] = ($row['status'] == 4) ? 1 : 0;
		$row['e'] = ($row['status'] == 7) ? 1 : 0;
	//	$row['c'] .= ($row['e']) ? '/' : '';
		unset($row['status']);						
	}	
	return $typeahead_users;
}


function get_letsgroups(){
	global $db;
	return $db->fetchAll('select * from letsgroups'); //
}

function get_letsgroup($id){
	global $db;
	return $db->fetchAssoc('select * from letsgroups where id = ?', array($id)); //
}

function get_contact_by_email($email){
        global $db;
        return $db->fetchAssoc('select * from contact where value = ?', array($email));  //
 }
/*
function get_contact($user_id, $abbrev){ // review where is this function used?
        global $db;
        $query = "SELECT *, ";
        $query .= " contact.id AS cid, users.id AS uid, type_contact.id AS tcid, ";
        $query .= " type_contact.name AS tcname, users.name AS uname ";
        $query .= " FROM users, type_contact, contact ";
        $query .= " WHERE users.id=".$user;
        $query .= " AND contact.id_type_contact = type_contact.id ";
        $query .= " AND users.id = contact.id_user ";
        $query .= " AND contact.flag_public = 1";
        $contact = $db->GetArray($query);
        return $contact;
}
*/
function get_contact($user_id, $abbrev){
	global $db;
	
	$qb = $db->createQueryBuilder();
	
	$qb->select('c.value')
		->from('contact', 'c')
		->join('c', 'type_contact', 't', 't.id = c.id_type_contact')
		->where($qb->expr()->eq('c.id_user', $user_id))
		->andWhere($qb->expr()->eq('t.abbrev', $abbrev));
	return $db->fetchColumn($qb);
}

function get_contacts($user_id, $public_only = true){
	global $db;
	$qb = $db->createQueryBuilder();
	$qb->select('c.value, t.abbrev, c.flag_public')
		->from('contact', 'c')
		->join('c', 'type_contact', 't', 't.id = c.id_type_contact')
		->where($qb->expr()->eq('c.id_user', $user_id));
	if ($public_only){
		$qb->andWhere('c.flag_public = 1');
	}
	return $db->fetchAll($qb);
}

function get_contacttype($abbrev){
	global $db;
	return $db->fetchAssoc('select * from where abbrev = ?', array($abbrev)); //
}

function get_user($id){
	global $db;
	return $db->fetchArray('select * from users where id = ?', array($id)); //
} 

function get_user_by_letscode($letscode){
	global $db;
	$letscode = trim($letscode);
    return $db->fetchAssoc('select * from users where letscode = ?', array($letscode));  //
}


function get_user_by_name($name){
	global $db;
	return $db->fetchAssoc('select * from users where fullname like \'%?%\'', array($name));  //
}


function get_user_maildetails($userid){   //// to review  (where is this function used?)
	global $db;
    
    $qb = $db->createQueryBuilder(); //
    $qb->select('u.*', 'c.value as emailaddress')
		->from('users', 'u')
		->leftJoin('u', 'contact', 'c', 'c.id_user = u.id')
		->leftJoin('c', 'type_contact', 't', 't.id = c.id_type_contact')
		->where($qb->expr()->eq('u.id', $userid))
		->andWhere('t.abbrev = \'mail\'');
    return $db->fetchAssoc($qb);          
}

function getEmailAddressFromUserId($userId){
	global $db;    
    $qb = $db->createQueryBuilder(); //
    $qb->select('c.value')
		->from('contact', 'c')
		->join('c', 'type_contact', 't', 't.id = c.id_type_contact')
		->where($qb->expr()->eq('c.id_user', $userId))
		->andWhere('t.abbrev = \'mail\'');
    return $db->fetchColumn($qb);         
}




function get_user_mailaddresses($userid){
	global $db;
	$mailaddresses = array();
	$query = "select c.value from contact, type_contact WHERE id_user = ? AND id_type_contact = type_contact.id and type_contact.abbrev = 'mail'";
    $qb = $db->createQueryBuilder(); //
    $qb->select('c.value')
		->from('contact', 'c')
		->join('c', 'type_contact', 't', 't.id = c.id_type_contact')
		->where($qb->expr()->eq('c.id_user', $userid))
		->andWhere('t.abbrev = \'mail\'');
    return $db->fetchAssoc($qb);  
	while ($mailaddresses[] =  $db->fetchColumn($qb)){
	}
	return implode(', ', $mailaddresses);
}


function get_users(){
	global $db;
	return $db->fetchAssoc('select * from users where status in (1, 2, 3, 4) and accountrole <> \'guest\' order by letscode'); //
}

function get_user_letscode($id){
	global $db;
	return $db->fetchColumn('select letscode from users where id = ?', array($id));  //
}
?>
