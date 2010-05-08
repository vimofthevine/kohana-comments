<?php
	echo Form::open();
	echo Form::hidden('classify_id', $id);

	$options = array('nop' => 'Do nothing');
	if ( ! $is_ham)
	{
		$options += array(
			'learn_ham'    => 'Approve more comments like this',
			'unlearn_spam' => 'Comments like these aren\'t spam',
		);
	}
	if ( ! $is_spam)
	{
		$options += array(
			'learn_spam'   => 'Mark more comments like this as spam',
			'unlearn_ham'  => 'Comments like these are spam',
		);
	}
	echo Form::label('classify_option', 'Classification Options: ');
	echo Form::select('classify_option', $options, 'nop');

	if ( ! $is_ham)
	{
		echo Form::submit('classify_ham', __('Approve'));
	}

	if ( ! $is_spam)
	{
		echo Form::submit('classify_spam', __('Mark as Spam'));
	}
	echo Form::close();
?> 
