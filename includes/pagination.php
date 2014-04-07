<?php

// copyleft 2013 martti info@martti.be

class pagination{
	private $req;
	private $start = 0;
	private $limit = 25;
	private $page = 0; 
	
	private $adjacent_num = 2; 	
	private $row_count = 0;
	private $page_num = 0;
	private $base_url = '';
	private $sum = 0;
	private $render_sum = false;
	private $text_sum = '';
	
	public function __construct($req){
		$this->req = $req;
		$this->limit = $req->get('limit');
		$this->start = $req->get('start');
	}
	
	public function setQuery($query){
		global $db;

		$query = 'select count(*) '.substr($query, stripos($query, ' from ') + 1);
		$this->row_count = (int) $db->fetchColumn($query);  //
		
		$this->page_num = ceil($this->row_count / $this->limit);
		$this->page = floor($this->start / $this->limit);		
	}
	
	public function setSum($query, $column, $text = ''){
		global $db;

		$query = 'select sum('.$column.') '.substr($query, stripos($query, ' from ') + 1);
		$this->sum = $db->fetchColumn($query);  //
		$this->render_sum = true;
		$this->text_sum = $text;
	}		
	
	public function setUrl($url = ''){
		$this->base_url = $url;
		return $this;
	}
	
	public function getUrlParameters(){
		return 'start=' . ($this->page * $this->limit) . '&limit=' . $this->limit;
	}
	
	public function getLimit(){
		return $this->limit;
	}
	
	public function setLimit($limit){
		$this->limit = $limit;
		return $this;
	}
	
	public function getStart(){
		return $this->start;
	}
	
	public function setStart($start){
		$this->start = $start;
		return $this;
	}	
		
	
	public function render(){

		echo '<div class="row"><div class="col-md-12">';
		echo '<ul class="pagination">';
		$result_str = ($this->row_count == 1) ? 'Resultaat' : 'Resultaten';
		echo '<li class="details">'.$this->row_count.' '.$result_str.'. Pagina ' . ($this->page + 1).' van ' . $this->page_num.'</li>';

		if ($this->page){
			echo $this->addLink($this->page - 1, '&#9668;');
		}
		
		$min_adjacent = $this->page - $this->adjacent_num;
		$max_adjacent = $this->page + $this->adjacent_num;
		
		$min_adjacent = ($min_adjacent < 0) ? 0 : $min_adjacent;
		$max_adjacent = ($max_adjacent > $this->page_num - 1) ? $this->page_num - 1 : $max_adjacent;
		
		if ($min_adjacent){
			echo $this->addLink(0);		
		}
		
		if ($min_adjacent > 1){
			echo '<li class="dots">...</li>';
		}

		for($page = $min_adjacent; $page < $max_adjacent + 1; $page++){
			echo $this->addLink($page);
		}
		
		if ($max_adjacent < $this->page_num - 3){
			echo '<li class="dots">...</li>';
		}
		
		if ($max_adjacent != $this->page_num - 1){
			echo $this->addLink($this->page_num - 1);
		}
	
		if ($this->page < $this->page_num - 1){
			echo $this->addLink($this->page + 1, '&#9658;');
		}
		
		echo '</ul>';
		
		if ($this->render_sum && $this->row_count){
			echo '<div class="pull-right">'.$this->text_sum.$this->sum.'</div>';
		}	
		
		echo '</div></div>';
	}
	
	public function addLink($page, $text = ''){
		$pag_link = '<li><a';	
		$pag_link .= ($page == $this->page) ? ' class="current"' : '';
		$pag_link .= ' href="'.$this->req->get_link(array('start' => $page * $this->limit, 'limit' => $this->limit)).'">';
		$pag_link .= ($text == '') ? ($page + 1) : $text;
		$pag_link .= '</a></li>';
		return $pag_link;	
	}
	
	public function getSqlLimit(){
		return ' LIMIT ' . $this->start . ', ' . $this->limit;
	}
	
}

?>
