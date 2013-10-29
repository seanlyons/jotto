<head>
	<link rel="stylesheet" type="text/css" href="jotto.css">
	<script src="jotto.js"></script>
</head>
<body>
<div id="title_header">
	<a href="">jotto</a>
</div>
<div id="wrapper">
    <div id="col1" class="col">
		<form id="guess" action="single.php" method="post">
			Guess: <input id="guess_textbox" autofocus="autofocus" type="text" name="guess"><br>
			<!--input id="notes_textbox" rows="10" cols="100" type="textarea" name="notes"><br-->
			<input id="guess_submit" type="submit" value="Guess">
		</form>
		<button id="anagram" onclick="anagramify()" >&#8634;</button>
    </div>
    <div id="col2" class="col">
		<form id="restart_game" action="single.php" method="post">
			<input type="hidden" name="restart" value="1"><br>
			<input type="submit" value="Restart Game">
		</form>
	</div>
	<div id="col3" class="col">
		<form id="reset_alphabet" action="single.php" method="post">
			<input type="hidden" name="resetalphabet" value="1"><br>
			<input type="submit" value="Reset Alphabet">
		</form>
	</div>
	<div id="col4" class="col">
		<form id="resign" action="single.php" method="post">
			<input type="hidden" name="resign" value="1"><br>
			<input type="submit" value="Give Up">
		</form>
	</div>
</div>
<div id="spacer"></div>
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