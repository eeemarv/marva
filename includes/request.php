<?php

//  (copyleft) 2013 martti info@martti.be

class request {  
	private $s_id;
	private $s_name;
	private $s_letscode;		
	private $s_accountrole;
	private $rootpath = '../';
	private $url;
	private $security_level;
	private $method;
	private $entity;
	private $item;
	private $owner_param;
	private $owner;
	private $success = false;
	private $entity_translation;
	private $data_transformers = array();

	private $parameters = array();
	private $render_keys = array('type', 'value', 'size', 'maxlength', 'style', 
		'label', 'checked', 'onchange', 'onkeyup', 'autocomplete', 'options', 'option_set', 
		'disabled', 'cols', 'rows', 'admin', 'placeholder');
	private $validation_keys = array('not_empty', 'match', 'min_length', 'max_length', 
		'unique', 'email', 'url', 'date');
	
	private $output = 'tr';
	
	private $error_messages = array(
		'empty' => 'Gelieve in te vullen.',
		'mismatch' => 'Ongeldige waarde.',
		'too_short' => 'Het minimaal aantal tekens is %d.',
		'too_long' => 'Het maximaal aantal tekens is %d.',
		'not_unique' => 'De waarde is niet uniek.',
		'no_email' => 'Geen geldig email-adres.',
		'no_url' => 'Geen geldige url.',
		'no_date' => 'Geen geldige datum',
		);
		
	private $status_messages = array(
		'delete' => array('Fout: %s niet verwijderd.', '%s verwijderd.'),
		'update' => array('Fout: %s niet aangepast.', '%s aangepast.'),
		'create' => array('Fout: %s niet toegevoegd.', '%s opgeslagen.'));

	public function __construct($security_level = null, $allow_anonymous_post = false){
		session_start();
		$this->method = strtolower($_SERVER['REQUEST_METHOD']);		
		$this->setSecurityLevel($security_level, $allow_anonymous_post);	
		return $this;
	}
	
	public function setSecurityLevel($security_level = null, $allow_anonymous_post = false){
	
		global $s_id, $s_name, $s_letscode, $s_accountrole; //	backward compability		
		$s_id = $this->s_id = (isset($_SESSION['id'])) ? $_SESSION['id'] : null;
		$s_name = $this->s_name = (isset($_SESSION['name'])) ? $_SESSION['name'] : null;
		$s_letscode = $this->s_letscode = (isset($_SESSION['letscode'])) ? $_SESSION['letscode'] : null;
		$s_accountrole = $this->s_accountrole = (isset($_SESSION['accountrole'])) ? $_SESSION['accountrole'] : null;	
			
		if (!$security_level || (!in_array($security_level, array('admin', 'user', 'guest', 'anonymous')))){
			header(' ', true, 500);
			exit;
		}
				
		if ($security_level != 'anonymous' && (!isset($this->s_id) || !$this->s_accountrole || !$this->s_name)){
			header('Location: ../login.php?location=' . urlencode($_SERVER['REQUEST_URI']));
			exit;
		}
	
		if ((!$allow_anonymous_post && $s_accountrole == 'anonymous' && $this->method != 'get')
			|| ($s_accountrole == 'guest' && $this->method != 'get')
			|| ($security_level == 'admin' && $this->s_accountrole != 'admin')
			|| ($security_level == 'user' && !in_array($this->s_accountrole, array('admin', 'user')))
			|| ($security_level == 'guest' && !in_array($this->s_accountrole, array('admin', 'user', 'guest')))){
			header('HTTP/1.1 403 Unauthorized', true, 403);	
			//header(' ', true, 403);
			exit;			
		}	

		$this->security_level = $security_level;
			
		return $this;
	}
	
	public function setEntity($entity){
		$this->entity = $entity;
		return $this;
	}
	
	public function getEntity(){
		return $this->entity;
	}
	
	public function setEntityTranslation($trans){
		$this->entity_translation = $trans;
		return $this;
	}
	
	public function renderStatusMessage($db_action){
		$message = sprintf($this->status_messages[$db_action][(($this->success) ? 1 : 0)], $this->entity_translation);
		setstatus($message, (($this->success) ? 'success' : 'danger'));
		return $this;
	}
	
	public function renameItemParams($renames = array()){
		foreach($renames as $old => $new){
			if (array_key_exists($old, $this->item) && !array_key_exists($new, $this->item)){
				$this->item[$new] = $this->item[$old];
				unset($this->item[$old]);
			}
		}
		return $this;
	}
	
	public function setDataTransform($param, $transform){
		if (!is_array($transform)){
				return $this;
		}
		$this->data_transformers[$param] = $transform;		
		return $this;
	}
	
	public function dataTransform(){
		foreach($this->data_transformers as $param => $transform){
			if (array_key_exists($param, $this->item) && array_key_exists($this->item[$param], $transform)){
				$this->item[$param] = $transform[$this->item[$param]];
			}
		}
		return $this;
	}
	
	public function dataReverseTransform($params){
		foreach($this->data_transformers as $param => $transform){
			if (array_key_exists($param, $params) && in_array($params[$param], $transform)){
				$params[$param] = array_search($params[$param], $transform);
			}
		}
		return $params;		
	}

	public function query(){
		global $db;
		if ($this->get('id')){
			$this->item = $db->GetRow('select * from '.$this->entity.' where id = '.$this->get('id'));
			if (!$this->item){
				header('HTTP/1.0 404 Not Found');
				echo '<h1>404 Not Found</h1>';
				echo '<p>De gevraagde pagina kon niet gevonden worden.</p>';
				exit();
			}
			$this->dataTransform();
		}
		return $this;
	}
	
	public function delete(){
		global $db;
		if ($this->get('id') && $this->entity){
			$this->success = $db->Execute('delete from '.$this->entity.' where id = ' .$this->get('id'));
			$this->renderStatusMessage('delete')->reset('id');
		}
		return $this;		
	}
	
	public function create($params = array()){
		global $db;
		if ($this->entity){
			$this->success = $db->AutoExecute($this->entity, $this->dataReverseTransform($this->get($params)), 'INSERT');
			$new_id = $db->Insert_ID();
			if ($new_id){
				$this->set('id', $new_id);
			} 
			$this->renderStatusMessage('create');
		}
		return $this;			
	}
	
	public function update($params = array()){
		global $db;
		if ($this->get('id') && $this->entity){
			$this->success = $db->AutoExecute($this->entity, $this->dataReverseTransform($this->get($params)), 'UPDATE', 'id = '.$this->get('id'));
			$this->renderStatusMessage('update');
		}
		return $this;			
	}
	
	public function errorsCreate($params = array()){
		if ($this->errors($params)){
			return true;
		} 
		$this->create($params);	
		return false;
	}	
			
	public function errorsUpdate($params = array()){
		if ($this->errors($params)){
			return true;
		} 
		$this->update($params);	
		return false;		
	}
	
	public function cancel(){
		if ($this->isPost() && $this->get('cancel')){
			$this->reset(array('mode'));
		}
		return $this;	
	}				
			
	public function isSuccess(){
		return ($this->success) ? true : false;
	}
	
	public function setSuccess($success = true){
		$this->success = $success;
		return $this;
	}	
	
	public function getItem(){
		return $this->item;
	}
	
	public function getItemValue($name_param){
		return $this->item[$name_param];
	}
	
	public function setItemValue($name_param, $value){
		$this->item[$name_param] = $value;
		return $this;
	}
	
	public function setOwnerParam($owner_param){
		$this->owner_param = $owner_param;
		return $this;
	}
	
	public function getOwnerParam(){
		return $this->owner_param;
	}
	
	public function renderOwnerLink(){
		echo '<a href="users.php?id='.$this->owner['id'].'">';
		echo trim($this->owner['letscode']).' '.htmlspecialchars($this->owner['name'],ENT_QUOTES).'</a>';
	}	

	public function isOwner($object_user_id = 0){
		return (($this->s_id == $this->item[$this->owner_param])
			&& $this->s_id && $this->item && $this->owner_param) ? true : false;	
	}	
	
	public function isOwnerOrAdmin($object_user_id = 0){
		return ($this->isOwner() || $this->isAdmin()) ? true : false;
	}	

	public function isAdmin(){
		return ($this->s_accountrole == 'admin') ? true : false;
	}
	
	public function isUser(){
		return ($this->s_accountrole == 'admin' || $this->accountrole == 'user') ? true : false;
	}
	
	public function isAdminPage(){
		return ($this->security_level == 'admin') ? true : false;
	}
	
	public function queryOwner($active_only = true){
		global $db;
		if (!($this->item || $this->owner_param)){
			return $this;
		}
		$this->owner =  $db->GetRow('select * from users 
			where id = '.$this->item[$this->owner_param]);
		return $this;
	}
	
	public function getOwner(){
		return $this->owner;
	}
	
	public function getOwnerValue($name_param){
		return $this->owner[$name_param];
	}
	
	public function setOwnerValue($name_param, $value){
		$this->owner[$name_param] = $value;
		return $this;
	}	
	

	public function getMethod(){
		return $this->method;
	}	
	
	public function isPost(){
		return ($this->method == 'post') ? true : false;
	}
	
	public function isGet(){
		return ($this->method == 'get') ? true : false;
	}
	
	public function getSid(){
		return $this->s_id;
	}		

	public function get_label($name){
		return ($this->parameters[$name]['label']) ? $this->parameters[$name]['label'] : null;
	}	

	public function add($name, $default = null, $method = null, $rendering = array(), $validators = array()) {
		if (!in_array($method, array('post', 'get', 'post|get', 'get|post'))){
			return $this;
		}
		foreach($rendering as $key => $val){
			if (in_array($key, $this->render_keys)){
				$this->parameters[$name][$key] = $val;
			}
		}
		foreach($validators as $key => $val){
			if (in_array($key, $this->validation_keys)){
				$this->parameters[$name][$key] = $val;
			}
		}
		$this->parameters[$name]['default'] = $default;
		unset($value);
		if ($this->isGet() && in_array($method, array('get', 'get|post', 'post|get'))){						
			$value = $_GET[$name];
		}
		if ($this->isPost() && in_array($method, array('post', 'get|post', 'post|get'))){
			$value = $_POST[$name];
		}
		if (!isset($value)){
			$this->parameters[$name]['value'] = $default;
			return $this;
		}
		
		$type = gettype($default);
		settype($value, $type);
		if ($type == 'string'){
			$value = trim(htmlspecialchars(str_replace(array("\r\n", "\r", "\0"), array("\n", "\n", ''), $value), ENT_COMPAT, 'UTF-8'));
			$value = (empty($value)) ? null : preg_replace('/[\x80-\xFF]/', '?', $value);
			$value = stripslashes($value);
		}
			
		$this->parameters[$name]['value'] = $value;
		$this->parameters[$name]['value_type'] = $type;
		return $this;		
	}

	
	public function addSubmitButtons(){
		$this->add('create', '', 'post', array('type' => 'submit', 'label' => 'Toevoegen'))
			->add('create_plus', '', 'post', array('type' => 'submit', 'label' => 'Toevoegen en nog Eén'))
			->add('edit', '', 'post', array('type' => 'submit', 'label' => 'Aanpassen'))
			->add('cancel', '', 'post', array('type' => 'submit', 'label' => 'Annuleren'))
			->add('delete', '', 'post', array('type' => 'submit', 'label' => 'Verwijderen'))
			->add('filter', '', 'get', array('type' => 'submit', 'label' => 'Toon'));
		return $this;
	}	
	
	public function get($name){
		if (is_array($name)){
			return array_map(function ($parameter){ 
						return $parameter['value']; 
					},
					array_intersect_key($this->parameters, array_flip($name))); 
		}	
		return $this->parameters[$name]['value'];
	}
	
	public function set($name, $value){
		$this->parameters[$name]['value'] = (isset($this->parameters[$name])) ? $value : null;
		return $this;
	}
	
	public function setDisabled($name){
		if (is_array($name)){
			foreach ($name as $param_name){
				if (isset($this->parameters[$param_name])){
					$this->parameters[$param_name]['disabled'] = 'disabled';
				}
			}
		}
		$this->parameters[$name]['disabled'] = (isset($this->parameters[$name])) ? 'disabled' : null;
		return $this;
	}

	public function setLabel($name, $label){
		$this->parameters[$name]['label'] = (isset($this->parameters[$name])) ? $label : null;
		return $this;
	}	
	
	public function reset($name){
		if (is_array($name)){
			foreach($name as $par_name){
				$this->parameters[$par_name]['value'] = $this->parameters[$par_name]['default'];
			}
			return $this;
		}
		$this->parameters[$name]['value'] = $this->parameters[$name]['default'];
		return $this;
	}
	
	public function setUrl($url){
		$this->url = $url;
		return $this;
	}	
	
	public function get_link($overwrite_params = array()){
		$param_string = '';
		if (sizeof($this->parameters)){
			$param_string = '?';
			foreach ($this->parameters as $name => $data){
				$param_string .= $name.'=';
				$param_string .= (array_key_exists($name, $overwrite_params)) ? $overwrite_params[$name] : $this->parameters[$name]['value'];
				$param_string .= '&';
			}
			$param_string = trim($param_string, '&');	
		}
		return $this->url.$param_string;	
	}		



	public function resetFromDb($name = null)
	{
		if (!$this->item){
			return $this;
		}
		if (is_array($name)){
			foreach ($name as $param_name){
				$this->parameters[$param_name]['value'] = $this->item[$param_name];
			}
			return $this;
		} 
		$this->parameters[$name]['value'] = $this->item[$name];
		return $this;
	}

	public function set_output($output = 'tr'){
		$this->output = $output;
		return $this;
	}

	public function render($name = null, $reset_from_db = false){
		if (!method_exists($this, 'render_'.$this->output)){		
			return $this;
		}
		if ($reset_from_db){
			$this->resetFromDb($name);
		}		
		if (is_array($name)){
			foreach ($name as $val){
				$this->render_single($val);	
			}	
		} elseif (isset($name)){
			$this->render_single($name);
		}	
		return $this;
	}
		
	private function render_single($name){
		if (!$this->parameters[$name]){
			return;
		}
		if ($this->parameters[$name]['admin'] && !$this->isAdmin()){
			return;
		}	
		$func = 'render_'.$this->output;
		$this->$func($name);
		return $this;		
	}	
	
	private function render_tr($name){
		echo '<tr>';
		$this->render_td($name);
		echo '</tr>';
		return $this;	
	}	

	private function render_td($name){
		echo $this->getLabelString($name, 'td').'<td>'.$this->getInputString($name).'</td>';
		return $this;
	}
	
	private function render_trtr($name){
		echo '<tr>'.$this->getLabelString($name, 'td').'</tr><tr><td>'.$this->getInputString($name).'</td></tr>';
		return $this;
	}

	private function render_trtd($name){
		echo '<tr><td>'.$this->getLabelString($name).$this->getInputString($name).'</td></tr>';
		return $this;
	}
	
	private function render_nolabel($name){
		echo $this->getInputString($name).'&nbsp;&nbsp;';
		return $this;
	}	


	public function getLabelString($name, $tag = ''){
		$open_tag = ($tag) ? '<'.$tag.'>' : '';
		$close_tag = ($tag) ? '</'.$tag.'>' : '';
		$parameter = $this->parameters[$name];
		$admin = ($parameter['admin']) ? '[admin] ' : '';
		$required = ($parameter['not_empty']) ? '*' : '';	
		return ($parameter['type'] != 'submit' && $parameter['label']) ? $open_tag.$admin.$parameter['label'].$required.$close_tag : '';		
	}


	public function getInputString($name){
		$parameter = $this->parameters[$name];
		$out = '';
		$parameter['checked'] = ($parameter['type'] == 'checkbox' && $parameter['value']) ? 'checked' : null;	
		if ($parameter['type'] == 'select'){
			$out .= '<select name="'.$name.'">';
			$out .= $this->getSelectOptionsString($name);
			$out .=  '</select>';
		} elseif ($parameter['type'] == 'textarea'){
			$cols = ($parameter['cols']) ? ' cols="'.$parameter['cols'].'"' : '';
			$rows = ($parameter['rows']) ? ' rows="'.$parameter['rows'].'"' : '';
			$out .= '<textarea name="'.$name.'" '.$cols.$rows.'>';
			$out .= $parameter['value'].'</textarea>';
		} else {						
			$out .= '<input name="'.$name.'"';
			$out .= ($parameter['type'] == 'submit' && $parameter['label']) ? ' value="'.$parameter['label'].'"' : '';	
			foreach($this->render_keys as $val){		
				if (!$parameter[$val] || in_array($val, array('label', 'options', 'option_set', 'entity_id', 'admin'))){
					continue;
				}
				$out .= ' '.$val.'="'.$parameter[$val].'"';
			}
			$out .= '>';
		}	
		$out .= (isset($parameter['error'])) ? '<strong><font color="red">'.$parameter['error'].'</font></strong>' : '';	
		return $out;
	}

	public function getSelectOptionsString($name){
		$options = $this->parameters[$name]['options'];
		$out = '';
		$options = (is_array($options)) ? $options : array();
		$option_set = $this->parameters[$name]['option_set'];
		switch ($option_set){
			case 'active_users': 
				$ary = $this->get_active_users();
				$options[0] = '';
				foreach ($ary as $val){
					$options[$val['id']]['text'] = $val['letscode'].'&nbsp;&nbsp;'.$val['fullname'];
				}			
				break;
			case 'categories':
				$ary = $this->get_categories();
				$options[0] = '';
				foreach ($ary as $val){
					$suffix = ($val['msg_num']) ? ' ('.$val['msg_num'].')' : '';
					$options[$val['id']]['disabled'] = ($val['msg_num']) ? false : true;
					$prefix = ($val['id_parent']) ? '&nbsp;&nbsp;&nbsp;&nbsp;' : '';			
					$options[$val['id']]['text'] = $prefix.$val['name'].$suffix;
				}
				break;
			case 'subcategories':
				$ary = $this->get_categories();
				$options[0] = '';
				foreach ($ary as $val){
					$options[$val['id']]['disabled'] = ($val['id_parent']) ? false : true;
					$prefix = ($val['id_parent']) ? '&nbsp;&nbsp;&nbsp;&nbsp;' : '';			
					$options[$val['id']]['text'] = $prefix.$val['name'];
				}
				break;
			default :
				break;
		} 
		foreach ($options as $key => $val){
			$out .= '<option value="'.$key.'"';
			$out .= ($this->parameters[$name]['value'] == $key) ? ' selected="selected"' : '';
			$out .= ($val['disabled']) ? ' disabled="disabled"' : '';			
			$out .= '>'.$val['text'].'</option>';
		}
		return $out;		
	}


	public function errors($params = false){
		$error = false;
		foreach($this->parameters as $param_name => &$parameter){
			if (is_array($params) && !in_array($param_name, $params)){
				continue;
			}
			
			if($parameter['not_empty'] && empty($parameter['value'])){
				$parameter['error'] = $this->error_messages['empty'];
				
			} else if ($parameter['unique'] && !$this->isUnique($parameter)){
				$parameter['error'] = $this->error_messages['not_unique'];
				
			} else if ($parameter['email'] && !filter_var($parameter['value'], FILTER_VALIDATE_EMAIL)){
				$parameter['error'] = $this->error_messages['no_email'];	
							
			} else if ($parameter['url'] && !filter_var($parameter['value'], FILTER_VALIDATE_URL)){
				$parameter['error'] = $this->error_messages['no_url'];
				
			} else if ($parameter['date'] && !$this->isDate($parameter['value'])){
				$parameter['error'] = $this->error_messages['no_date'];
														
			} else if ($parameter['min_length'] && (strlen($parameter['value']) < $parameter['min_length'])){
				$parameter['error'] = sprintf($this->error_messages['too_short'], $parameter['min_length']);
				
			} else if ($parameter['max_length'] && (strlen($parameter['value']) > $parameter['max_length'])){
				$parameter['error'] = sprintf($this->error_messages['too_long'], $paramter['max_length']);	
						
			} else if ($parameter['match']){		 
				$mismatch = false;
				if (is_array($parameter['match'])){
					if (!in_array($parameter['value'], $parameter['match'])){
						$mismatch = true;
					}	
				} else {	 				
					switch ($parameter['match']){
						case 'positive': if (!eregi('^[0-9]+$', $parameter['value'])){
								$mismatch = true;
							}
							break;
						case 'password': if (!$this->confirm_password($parameter['value'])){
								$mismatch = true;
							}
							break;
						case 'subcategory': if (!$this->subcategory($parameter['value'])){
								$mismatch = true;
							}
							break;
						case 'existing_letscode': if (!$this->existing_letscode($parameter['value'])){
								$mismatch = true;
							}
							break;
						case 'active_letscode': if (!$this->active_letscode($parameter['value'])){
								$mismatch = true;
							}
							break;										
						case 'email_active_user': if (!$this->email_active_user($parameter['value'])){
								$mismatch = true;
							}
							break;
						case 'active_user': if (!$this->active_user($parameter['value'])){
								$mismatch = true;
							}
							break;
					}
				}
				if ($mismatch){								
					$parameter['error'] = $this->error_messages['mismatch'];					
				}
			}
			$error = (isset($parameter['error'])) ? true : $error;
		}
		return $error;
	}
	
	

	private function confirm_password($confirm_password){  
        global $db;
        $query = 'SELECT password FROM users WHERE id = '.$this->s_id;
        $row = $db->GetRow($query);
        $pass = ($row['password'] == hash('sha512', $confirm_password) || $row['password'] == md5($confirm_password) || $row['password'] == sha1($confirm_password)) ? true : false;
		return	$pass;
	}

	private function existing_letscode($letscode){
		global $db;
        $query = 'SELECT id FROM users WHERE letscode = "'.$letscode.'"';
        $row = $db->GetRow($query);
		return	($row['id']) ? true : false;	
	}
	
	private function active_letscode($letscode){
		global $db;
        $query = 'SELECT id FROM users WHERE letscode = "'.$letscode.'"';
        $query .= 'and (users.status = 1 or users.status = 2 or users.status = 3)';
        $row = $db->GetRow($query);
		return	($row['id']) ? true : false;	
	}	
	
	
	private function get_active_users(){
		global $db;
        $query = 'SELECT id, fullname, letscode FROM users ';
		$query .= 'WHERE (status = 1 OR status =2 OR status = 3) AND users.accountrole <> "guest" ';
		$query .= 'ORDER BY letscode';
		return	$db->GetArray($query);	
	}
	
	private function get_categories(){
		global $db;
		$rows = $db->GetArray('SELECT * FROM categories ORDER BY name');
		$cats = $cat_children = array();
		foreach ($rows as $cat){
			$cat_children[$cat['id_parent']][] = $cat;	
		}	
		foreach ($cat_children[0] as $maincat){	
			$cats[] = $maincat;
			end($cats);
			$maincat_key = key($cats);
			$maincat_msg_num = 0;
			if (sizeof($cat_children[$maincat['id']])){
				foreach ($cat_children[$maincat['id']] as $cat){
					$cat['msg_num'] = $cat['stat_msgs_wanted'] + $cat['stat_msgs_offers'];
					$cats[] = $cat;
					$maincat_msg_num += $cat['msg_num'];
				}	
			}
			$cats[$maincat_key]['msg_num'] = $maincat_msg_num;	
		}
		return $cats;		
	}
	
	private function email_active_user($value){
		$query = 'select * from users, type_contact, contact
			where contact.id_type_contact = type_contact.id 
			and type_contact.abbrev =\'mail\' 
			and contact.id_user = users.id
			and (users.status = 1 or users.status = 2 or users.status = 3)
			and contact.value = '.$value;
		return ($db->GetRow($query)) ? true : false;
	}
	
	private function active_user($id){
		global $db;
        $query = 'SELECT id FROM users WHERE id = "'.$id.'"';
        $query .= 'and (users.status = 1 or users.status = 2 or users.status = 3)';
        $row = $db->GetRow($query);
		return	($row['id']) ? true : false;	
	}
	
	private function subcategory($id){
		global $db;
		if (!$id){
			return false;
		}		
		$row = $db->GetRow('SELECT id FROM categories WHERE id = '.$id.' AND id_parent <> 0');
		return	($row['id']) ? true : false;
	}
	
	private function isUnique($parameter){
		$val = $parameter['value'];		
		$query = 'select id from '.$this->entity.' where '.$param.' = ';
		$query .= ($parameter['type_value'] == 'string') ? '\''.$val.'\'' : $val;
		return (count($db->GetArray($query)) == 0) ? true : false;		
	}
	
	private function isEmailAddress($parameter){
		$val = $parameter['value'];		
		$query = 'select id from '.$this->entity.' where '.$param.' = ';
		$query .= ($parameter['type_value'] == 'string') ? '\''.$val.'\'' : $val;
		return (count($db->GetArray($query)) == 0) ? true : false;		
	}	
	

	function isDate($date)
	{
		if(preg_match("/^(\d{4})-(\d{2})-(\d{2})$/", $date, $matches)){
			if(checkdate($matches[2], $matches[3], $matches[1])){
				return true;
			}
		}
		return false;
	}	
							
}
	
?>