<?php
$Header = ProxyRequest('http://localhost/wordpress/?VanillaGetTemplate=header');
$VanillaHead = $this->GetAsset('Head');
$Header = str_replace(array('</head>', '<body'), array($VanillaHead."\r\n</head>", '<body id="'.$BodyIdentifier.'" class="'.$this->CssClass.'"'), $Header);
echo $Header;
?>
<div id="Body">
	<div id="Content"><?php $this->RenderAsset('Content'); ?></div>
	<div id="Panel"><?php $this->RenderAsset('Panel'); ?></div>
</div>
<div id="Foot">
	<?php
		$this->RenderAsset('Foot');
		echo Wrap(Anchor(T('Powered by Vanilla'), C('Garden.VanillaUrl')), 'div');
	?>
</div>
<?php
$this->FireEvent('AfterBody');
echo ProxyRequest('http://localhost/wordpress/?VanillaGetTemplate=footer');