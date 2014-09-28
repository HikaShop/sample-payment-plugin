<!-- Here is the ending page, called at the end of the checkout, just before the user is redirected to the payment plateform -->
<div class="hikashop_example_end" id="hikashop_example_end">
  <!-- Waiting message -->
	<span id="hikashop_example_end_message" class="hikashop_example_end_message"><?php
	  echo JText::sprintf('PLEASE_WAIT_BEFORE_REDIRECTION_TO_X',$this->payment_name).'<br/>'. JText::_('CLICK_ON_BUTTON_IF_NOT_REDIRECTED');
  ?></span>
	<span id="hikashop_example_end_spinner" class="hikashop_example_end_spinner">
		<img src="<?php echo HIKASHOP_IMAGES.'spinner.gif';?>" />
	</span>
	<br/>
	<!-- To send all requiered information, a form is used. Hidden input are setted with all variables, and the form is auto submit with a POST method to the payment plateform URL -->
	<form id="hikashop_example_form" name="hikashop_example_form" action="<?php echo $this->payment_params->payment_url;?>" method="post">
		<div id="hikashop_example_end_image" class="hikashop_example_end_image">
			<input id="hikashop_example_button" class="btn btn-primary" type="submit" value="<?php echo JText::_('PAY_NOW');?>" name="" alt="<?php echo JText::_('PAY_NOW');?>" />
		</div>
<?php
	foreach( $this->vars as $name => $value )
	{
			echo '<input type="hidden" name="'.$name.'" value="'.htmlspecialchars((string)$value).'" />';
	}
	$doc = JFactory::getDocument();
	// We add some javascript code
	$doc->addScriptDeclaration("window.hikashop.ready(function(){ document.getElementById('hikashop_example_form').submit(); });");
	JRequest::setVar('noform',1);
?>
	</form>
</div>
