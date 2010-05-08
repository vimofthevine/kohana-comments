<h2><?php echo $legend ?></h2>

<?php foreach ($comments as $comment): ?>
<dl>
	<dt>Classification:</dt>
	<dd><?php echo ucfirst($comment->state) ?></dd>
	<dt>Name:</dt>
	<dd><?php echo $comment->name ?></dd>
	<dt>Email:</dt>
	<dd><?php echo $comment->email ?></dd>
	<dt>URL:</dt>
	<dd><?php echo $comment->url ?></dd>
	<dt>Date:</dt>
	<dd><?php echo date('F jS, Y', $comment->date) ?></dd>
	<dt>Text:</dt>
	<dd><?php echo $comment->text ?></dd>
</dl>
<?php endforeach ?>
