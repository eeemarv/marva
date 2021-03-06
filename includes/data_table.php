<?php

// (copyleft) 2014 martti info@martti.be


class data_table{   
	private $data = array();
	private $columns = array();
	private $header = false;
	private $footer = false;
	private $show_status = false;
	private $req = null;
	private $rootpath = '../';
	private $no_results_message = false;
	private $render_row_options;
	
/*	private $render_column_keys = array('title', 'title_suffix', 'title_params', 
		'footer', 'footer_text',
		'href_id', 'href_param', 'href_base', 'string_array', 'prefix', 
		'func', 'options_func'); */


	public function __construct(){
		$this->render_row_options = function($row){ return ''; };
	}
	
	public function set_data($data = array()){
		$this->data = $data;
		return $this;
	}
	
	public function set_input($req){
		$this->req = $req;
		return $this;
	}

	public function add_column($key, $options = array()){
		$this->show_status = ($options['render'] == 'status') ? true : $this->show_status; 
		$this->header = ($options['title']) ? true : $this->header;
		$this->footer = ($options['footer']) ? true : $this->footer;
		$this->footer = ($options['footer_text']) ? true : $this->footer;
		$this->columns[] = array_merge(array('key' => $key, 'count' => 0), $options);
		return $this;
	}

	public function render(){
		echo '<div class="table-responsive"><table class="table table-striped table-bordered table-condensed">';
		$this->render_header()->render_rows()->render_footer();
		echo '</table></div>';				
		return $this;
	}
	
	public function enable_no_results_message(){
		$this->no_results_message = true;
		return $this;
	}		

	private function render_header(){
		if (!$this->header){
			return $this;
		}
		echo '<tr>';
		foreach($this->columns as $val) { 
			$title = htmlspecialchars($val['title'], ENT_QUOTES);
			$href = $this->get_link($val['title_href'], $val['title_params']);
			echo '<td><strong>';
			if ($href){
				echo '<a href="'.$href.'">';
			}
			echo ($title) ? $title : '&nbsp;';
			if ($val['title_suffix']){
				echo '&nbsp;'.$val['title_suffix'];
			} 
			if ($href){
				echo '</a>';
			}					
			echo '</strong></td>';
		}
		echo '</tr>';
		return $this;
	}
	
	private function get_link($base_url, $param_array){
		$base_url = ($base_url) ? $base_url : '';
		if (is_array($param_array) && sizeof($param_array)){
			$return = $base_url.'?';
			foreach ($param_array as $name => $value){
				$return .= $name.'='.$value.'&';
			}	
			return rtrim($return, '&');
		}	
		return $base_url;
	}	
	
	private function render_footer(){
		if (!$this->footer){
			return $this;
		}	
		echo '<tr>';			
		foreach($this->columns as $val) { 
			$text = ($val['footer_text']) ? $val['footer_text'] : ' ';
			$text = ($val['footer'] == 'sum') ? $val['count'] : htmlspecialchars($text, ENT_QUOTES);
			$bgcolor = ($val['input']) ? ' bgcolor="lightblue" id="table_total"' : '';
			echo '<td'.$bgcolor.'><strong>'.$text.'</strong></td>';
		}
		echo '</tr>';
		return $this;
	}
	
	public function setRenderRowOptions($func){
		$this->render_row_options = $func;
		return $this;
	}
	
	
	private function render_rows(){
		if (!sizeof($this->data) && $this->no_results_message){
			echo '<tr><td colspan="'.sizeof($this->columns).'">Er zijn geen resultaten</td></tr>';
			return $this;
		}
		foreach ($this->data as $key => $row){
			echo '<tr'.call_user_func($this->render_row_options, $row).'>';
			foreach ($this->columns as &$td){
				echo '<td';
				if ($td['options_func']){
					echo call_user_func($td['options_func'], $row);	
				}	
				echo '>';
				if ($td['func']){
					echo call_user_func($td['func'], $row).'</td>';
					continue;
				} 

				$text = ($td['text']) ? $td['text'] : (($td['replace_by']) ? $row[$td['replace_by']] : $row[$td['key']]);
				$show = ((!$row[$td['show_when']] && $td['show_when']) || $row[$td['not_show_when']]) ? false : true;
				if ($td['input']){
					$this->req->set_output('nolabel')->render($td['key'].'-'.$row[$td['input']]);
					$td['count'] += ($td['footer'] == 'sum') ? $this->req->get($td['key'].'-'.$row[$td['input']]) : 0;					
				} else if (is_array($td['string_array'])){
					echo $td['string_array'][$row[$td['key']]];
				} else {
					$href = $td['href'];
					$href_param = ($td['href_param']) ? $td['href_param'] : 'id';
					$href = ($row[$td['href_id']]) ? $td['href_base'].'?'.$href_param.'='.$row[$td['href_id']] : $href;
					$href .= $td['href_static_param']; 
					if ($href){
						$href_target = ($td['href_target']) ? ' target="'.$td['href_target'].'"' : '';
						$td_1 = '<a href="'.$href.'"'.$href_target.'>';
						$td_2 = '</a>'; 
					} elseif ($td['href_mail'] && $row['abbrev'] == 'mail') {
						$td_1 = '<a href="mailto:'.$text.'">';
						$td_2 = '</a>';
					} elseif ($td['href_adr'] && $row['abbrev'] == 'adr') {
						$td_1 = '<a href="http://maps.google.be/maps?f=q&source=s_q&hl=nl&geocode=&q='.$text.'" target="_blank">';
						$td_2 = '</a>';	
					} else {
						$td_1 = $td_2 = '';
					}
					$td_1 = ($td['prefix'] && $row[$td['prefix']]) ? $row[$td['prefix']].'&nbsp'.$td_1 : $td_1; 
				//	$td_class = ($td['cond_td_class']) ? ' class="'.$td['cond_td_class'].'"' : '';
				//	$td_class = ($row[$td['cond_param']] == $td['cond_equals']) ? $td_class : '';	
				
				//	echo '<td'.$td_class.'>';
					echo ($show) ? $td_1.htmlspecialchars($text,ENT_QUOTES).$td_2 : '&nbsp;';						
					
					$td['count'] += ($td['footer'] == 'sum') ? $row[$td['key']] : 0;					
				}
				echo '</td>';
			}	
			echo '</tr>';
		}
		return $this;
	}		

}

?>
