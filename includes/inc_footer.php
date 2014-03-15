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

</div>



<footer class="footer">
  <div class="container">
	<p><a href="https://github.com/marttii/marva"><i class="fa fa-github fa-lg"></i>marva
	<?php echo exec('git describe --long --abbrev=10 --tags'); ?>			
	</a></p>
  </div>
</footer>
    
</body>
</html>
