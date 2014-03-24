<?php
/**
 * copyleft 2014 martti <info@martti.be>
 * 
 * Class to perform eLAS saldo operations
 *
 * This file is part of eLAS http://elas.vsbnet.be
 *
 * Copyright(C) 2009 Guy Van Sanden <guy@vsbnet.be>
 *
 * eLAS is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 3
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the  * GNU General Public License for more details.
*/
/** Provided functions:
 * update_saldo($userid)
*/



function update_saldo($userid){
	global $db;
	
	$query_min = 'select sum(amount) as summin
		from transactions 
		where id_from = '.$userid;
	$min = $db->GetRow($query_min);
	$min = $min['summin'];

	$query_plus = 'select sum(amount) as sumplus
		from transactions 
		where id_to = '.$userid;
	$plus = $db->GetRow($query_plus);
	$plus = $plus['sumplus'];

	$balance = $plus - $min;

	$query = 'update users set saldo = '.$balance.' where id = '.$userid;
	$db->execute($query);
}

?>
