<head>
	<link rel="stylesheet" type="text/css" href="jotto.css">
	<script src="jotto.js"></script>
</head>
<body>
<div id="form_wrapper">
	<form id="guess" action="single.php" method="post">
		Guess: <a href="https://github.com/seanlyons/jotto/blob/master/README.md">[?]</a><input id="guess_textbox" autofocus="autofocus" type="text" name="guess"><br>
		<input type="submit" value="Guess">
	</form>
	<button id="anagram" onclick="anagramify()" >&#8634;</button>
	<form id="restart_game" action="single.php" method="post">
		<input type="hidden" name="restart" value="1"><br>
		<input type="submit" value="Restart Game">
	</form>
	<form id="reset_alphabet" action="single.php" method="post">
		<input type="hidden" name="resetalphabet" value="1"><br>
		<input type="submit" value="Reset Alphabet">
	</form>
	<form id="resign" action="single.php" method="post">
		<input type="hidden" name="resign" value="1"><br>
		<input type="submit" value="Give Up">
	</form>
</div>
<br/><br/><br/>
<div id="phps">
    <?PHP
        include_once "business_logic.php";

        $request = sanitizeRequest($_REQUEST);
        session_start();
        $g = new Game();
        print_r($g->wrapper($request));
    ?>
</div>
</body>