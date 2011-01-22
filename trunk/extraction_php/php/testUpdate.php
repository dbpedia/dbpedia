<?php 
header('Content-Type: text/html; charset=UTF-8');
?><html>
<head>
<title>ultrapedia extraction test</title>
</head>
<body>
<form action="processUpdate.php" method="post">
Action:<br/>
<input type="radio" name="newarticle" value="true" checked="<?= @$_REQUEST['action'] === 'create' ? 'true' : 'false' ?>"/> Create
<input type="radio" name="newarticle" value="false" checked="<?= @$_REQUEST['action'] === 'create' ? 'false' : 'true' ?>"/> Update
<br/>
Mode:<br/>
<input type="radio" name="mode" value="forward" checked="<?= @$_REQUEST['mode'] === 'forward' ? 'true' : 'false' ?>"/> Forward
<input type="radio" name="mode" value="reply" checked="<?= @$_REQUEST['mode'] === 'forward' ? 'false' : 'true' ?>"/> Reply
<br/>
Page title:<br/>
<input name="title" size="80" value="<?= @htmlspecialchars($_REQUEST['title']) ?>"/>
<br/>
Page source:<br/>
<textarea name="source" cols="80" rows="25">
<?= @htmlspecialchars($_REQUEST['source']) ?>
</textarea>
<br/>
<input type="submit"/>
</form>
</body>
</html>
