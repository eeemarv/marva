  </div>
  <div class="clearer"></div>
 </div>
 <div id="footer">
	<div id="footerleft">
	<?php
	if(isset($s_id)){
		echo $s_name." (".trim($s_letscode)."), ";
		echo " <a href='".$rootpath."logout.php'>Uitloggen</a>";
	}
	?>
	</div>
	<div id="footerright"><a href="#">Marva 
	<?php
	echo exec('git describe --long --abbrev=10 --tags');
	?>
	</a>
	</div>
</div>
</body>
</html>
