<?php

//  (copyleft) 2014 martti info@martti.be

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
	private $admin_functions_enable = false;

	private $parameters = array();
	private $render_keys = array('type', 'value', 'size', 'maxlength', 'style', 
		'label', 'checked', 'onchange', 'onkeyup', 'autocomplete', 'options', 'option_set', 
		'disabled', 'cols', 'rows', 'admin', 'placeholder', 'class');
	private $validation_keys = array('not_empty', 'match', 'min_length', 'max_length', 
		'unique', 'email', 'url', 'date', 'recaptcha');
	
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
		'wrong_recaptcha' => 'recaptcha fout ingevuld.',
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
		$this->admin_functions_enable = (isset($_SESSION['admin_functions_enable'])) ? true : false;	
			
		if (!$security_level || (!in_array($security_level, array('admin', 'user', 'guest', 'anonymous')))){
			header(' ', true, 500);
			include '500.html';
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
			include '403.html';
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
		if (!is_array($this->item)){
			return $this;
		}
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
		if (!is_array($this->item)){
			return $this;
		}		
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
			$this->item = $db->fetchAssoc('select * from '.$this->entity.' where id = ?', array($this->get('id')));
			if (!$this->item){
				header('HTTP/1.0 404 Not Found', true, 404);
				include '404.html';
				exit;
			}
			$this->dataTransform();
		}
		return $this;
	}
	
	public function delete(){
		global $db;
		if ($this->get('id') && $this->entity){
			$this->success = $db->delete($this->entity, array('id' => $this->get('id')));
			$this->renderStatusMessage('delete')->reset('id');
		}
		return $this;		
	}
	
	public function create($params = array()){
		global $db;
		if ($this->entity){
			$this->success = $db->insert($this->entity, $this->dataReverseTransform($this->get($params)));
			if ($this->success){
				$this->set('id', $db->LastInsertId());
			} 
			$this->renderStatusMessage('create');
		}
		return $this;			
	}
	
	public function update($params = array()){
		global $db;
		if ($this->get('id') && $this->entity){
			$this->success = $db->update($this->entity, $this->dataReverseTransform($this->get($params)), array('id' => $this->get('id')));
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
	
	public function cancel($keep_id = true){
		if ($this->isPost() && $this->get('cancel')){
			header('location: '.$this->url.(($keep_id) ? '?id='.$this->get('id') : ''));
			exit;
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
		return $this->admin_functions_enable;
	}
	
	public function getAdminLabel(){
		return ($this->isAdmin())? '[admin] ' : '';
	}	
	
	public function toggleAdmin(){
		if ($this->admin_functions_enable){
			unset($_SESSION['admin_functions_enable']);
			$this->admin_functions_enable = false;
		} else {
			$this->admin_functions_enable = $_SESSION['admin_functions_enable'] = true;
		}	
		return $this;	
	}	
	
	public function isAdminAccountrole(){
		return ($this->s_accountrole == 'admin') ? true : false;
	}	
	
	
	
	public function isUser(){
		return ($this->s_accountrole == 'admin' || $this->accountrole == 'user') ? true : false;
	}
	
	public function isGuest(){
		return (in_array($this->s_accountrole, array('guest', 'user', 'admin'))); 
	}	
	
	public function isAdminPage(){
		return ($this->security_level == 'admin') ? true : false;
	}
	
	public function queryOwner($active_only = true){
		global $db;
		if (!($this->item || $this->owner_param)){
			return $this;
		}
		$this->owner =  $db->fetchAssoc('select * from users 
			where id = ?', array($this->item[$this->owner_param]));  //
		return $this;
	}
	
	public function getOwner(){
		return $this->owner;
	}
	
	public function getOwnerId(){
		return $this->owner['id'];
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
	
	public function getSName(){
		return $this->s_name;
	}
	
	public function getSCode(){
		return $this->s_letscode;
	}				

	public function get_label($name){
		return ($this->parameters[$name]['label']) ? $this->parameters[$name]['label'] : null;
	}	

	public function add($name, $default = null, $method = null, $rendering = array(), $validators = array()) {
		$method = strtolower($method);
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
		$this->parameters[$name]['method'] = $method;
		
		if ($this->parameters[$name]['type'] == 'checkbox'){
			
					
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
		$this->add('create', '', 'post', array('type' => 'submit', 'label' => 'Toevoegen', 'class' => 'btn btn-success'))
			->add('create_plus', '', 'post', array('type' => 'submit', 'label' => 'Toevoegen en nog Eén', 'class' => 'btn btn-success'))
			->add('edit', '', 'post', array('type' => 'submit', 'label' => 'Aanpassen', 'class' => 'btn btn-primary'))
			->add('cancel', '', 'post', array('type' => 'submit', 'label' => 'Annuleren', 'class' => 'btn btn-default'))
			->add('delete', '', 'post', array('type' => 'submit', 'label' => 'Verwijderen', 'class' => 'btn btn-danger'))
			->add('send', '', 'post', array('type' => 'submit', 'label' => 'Verzend', 'class' => 'btn btn-primary'))
			->add('filter', '', 'get', array('type' => 'submit', 'label' => 'Toon', 'class' => 'btn btn-default'));
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
			return $this;
		}
		$this->parameters[$name]['disabled'] = (isset($this->parameters[$name])) ? 'disabled' : '';
		return $this;
	}

	public function setLabel($name, $label){
		$this->parameters[$name]['label'] = (isset($this->parameters[$name])) ? $label : '';
		return $this;
	}
	
	public function getLabel($name){
		return $this->parameters[$name]['label'];
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
				if ($data['method'] != 'get'){
					continue;
				}
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

	private function render_formgroup($name){
		echo '<div class="form-group"><label class="col-sm-2 control-label">';
		echo $this->getLabelString($name).'</label><div class="col-sm-10">';
		echo $this->getInputString($name).'</div></div>';
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
		global $parameters;
		$parameter = $this->parameters[$name];
		$out = '';
		$parameter['checked'] = ($parameter['type'] == 'checkbox' && $parameter['value']) ? 'checked' : null;
		if ($parameter['type'] == 'recaptcha'){
			$out .= recaptcha_get_html($parameters['recaptcha_public']);
		} elseif ($parameter['type'] == 'select'){
			$out .= '<select name="'.$name.'">';
			$out .= $this->getSelectOptionsString($name);
			$out .=  '</select>';
		} elseif ($parameter['type'] == 'textarea'){
			$cols = ($parameter['cols']) ? ' cols="'.$parameter['cols'].'"' : '';
			$rows = ($parameter['rows']) ? ' rows="'.$parameter['rows'].'"' : '';
			$disabled = ($parameter['disabled']) ? ' disabled="'.$parameter['disabled'].'"' : '';
			$out .= '<textarea name="'.$name.'" '.$cols.$rows.$disabled.'>';
			$out .= $parameter['value'].'</textarea>';
		} else {						
			$out .= '<input name="'.$name.'"';
			$out .= ($parameter['type'] == 'submit' && $parameter['label']) ? ' value="'.$parameter['label'].'"' : '';	
			foreach($this->render_keys as $val){		
				if (!$parameter[$val] || in_array($val, array('label', 'options', 'option_set', 'entity_id', 'admin', 'method'))){
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
			case 'active_users_without_interlets': 
				$ary = $this->get_active_users(false);
				$options[0] = '';
				foreach ($ary as $val){
					$options[$val['id']]['text'] = $val['letscode'].'&nbsp;&nbsp;'.$val['fullname'];
				}			
				break;					
			case 'categories':
				$ary = $this->get_categories();
				$options[0]['text'] = '';
				foreach ($ary as $val){
					$suffix = ($val['msg_num']) ? ' ('.$val['msg_num'].')' : '';
					$options[$val['id']]['disabled'] = ($val['msg_num']) ? false : true;
					$prefix = ($val['id_parent']) ? '&nbsp;&nbsp;&nbsp;&nbsp;' : '';			
					$options[$val['id']]['text'] = $prefix.$val['name'].$suffix;
				}
				break;
			case 'subcategories':
				$ary = $this->get_categories();
				$options[0]['text'] = '';
				foreach ($ary as $val){
					$options[$val['id']]['disabled'] = ($val['id_parent']) ? false : true;
					$prefix = ($val['id_parent']) ? '&nbsp;&nbsp;&nbsp;&nbsp;' : '';			
					$options[$val['id']]['text'] = $prefix.$val['name'];
				}
				break;
			case 'maincategories':
				$ary = $this->get_categories();
				$options[0]['text'] = '';
				foreach ($ary as $val){
					if (!$val['id_parent']){			
						$options[$val['id']]['text'] = $prefix.$val['name'];
					}
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
				
			} else if ($parameter['unique'] && !$this->isUnique($param_name)){
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
						case 'positive': if (!(in_array($parmeter['value'], array(0, '0', '')) || ctype_digit($parameter['value']))){
								$mismatch = true;
							}
							break;
						case 'positive_and_not_zero': if (!ctype_digit($parameter['value'])){
								$mismatch =true;
						 
							}
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
						case 'active_user_without_interlets': if(!$this->active_user($parameter['value'], false)){
								$mismatch = true;
							}
							break;
					}
				}
				if ($mismatch){								
					$parameter['error'] = $this->error_messages['mismatch'];					
				}
			} else if ($parameter['recaptcha']){
				$resp = recaptcha_check_answer ($privatekey, $_SERVER['REMOTE_ADDR'], $_POST['recaptcha_challenge_field'], $_POST['recaptcha_response_field']);
				if (!$resp->is_valid){
					$parameter['error'] = $this->error_messages['wrong_recaptcha'];						
				}
			}
			$error = (isset($parameter['error'])) ? true : $error;
		}
		return $error;
	}
	
	

	private function confirm_password($confirm_password){  
        global $db;
        $password = $db->fetchColumn('select password from users where id = ?', array($this->s_id)); //
        $pass = ($password == hash('sha512', $confirm_password) 
			|| $password == md5($confirm_password) 
			|| $password == sha1($confirm_password)) ? true : false;
		return	$pass;
	}

	private function existing_letscode($letscode){
		global $db;
        $id = $db->fetchColumn('select id from users where letscode = ?', array($letscode)); //
		return	($id) ? true : false;	
	}
	
	private function active_letscode($letscode){
		global $db;
		$letscode = getLocalLetscode($letscode);
        $id = $db->fetchColumn('select id from users where letscode = ? and status in (1, 2, 4, 7)', array($letscode));  	//
		return	($id) ? true : false;	
	}	
	
	
	private function get_active_users($include_interlets = true){
		global $db;
		$status = '1, 2, 4'.(($include_interlets) ? ', 7' : '');
		return $db->fetchAll('select id, fullname, letscode from users where status in ('.$status.')');
	}
	
	private function get_categories(){
		global $db;
		$rows = $db->fetchAll('select * from categories order by name');	//
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
		global $db;
		$qb = $db->createQueryBuilder();
			$qb->select('u.id')
				->from('users', 'u')
				->join('u', 'contact', 'c', 'u.id = c.id_user')
				->join('c', 'type_contact', 't', 'c.id_type_contact = t.id')
				->where($qb->expr()->eq('c.value', $value))
				->andWhere('t.abbrev = \'mail\'')
				->andWhere('u.status in (1, 2, 4, 7)');
				
		return ($db->fetchColumn($query)) ? true : false;
	}
	
	private function active_user($id, $include_interlets = true){
		global $db;
		$interlets = ($include_interlets) ? ', 7' : '';
        $id = $db->fetchColumn('select id from users where id = ? and status in (1, 2, 4'.$interlets.')', array($id)); 	
		return	($id) ? true : false;	
	}
	
	private function subcategory($id){
		global $db;
		if (!$id){
			return false;
		}		
		$id = $db->fetchColumn('SELECT id FROM categories WHERE id = ? AND id_parent <> 0', array($id)); //
		return	($id) ? true : false;
	}
	
	private function isUnique($param_name){
		global $db;
		$val = $parameter['value'];
		$qb = $db->createQueryBuilder();
		$qb->select('count(id)')
			->from($this->entity, 'x')
			->where($qb->expr()->eq($param_name, '\''.$this->parameters[$param_name]['value'].'\''));
		return ($db->fetchColumn($qb) == 0) ? true : false;		
	}

	function isDate($date){
		if(preg_match("/^(\d{4})-(\d{2})-(\d{2})$/", $date, $matches)){
			if(checkdate($matches[2], $matches[3], $matches[1])){
				return true;
			}
		}
		return false;
	}	
							
}
	
?>
