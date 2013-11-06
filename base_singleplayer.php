<?PHP
            include_once "business_logic.php";
            
            $request = sanitizeRequest($_REQUEST);
            session_start();
?>

<head>
	<link rel="stylesheet" type="text/css" href="jotto.css">
	<script src="jotto.js"></script>
</head>
<body>
<div id="container">
    <button id="rules_cta" onclick="rules_modal()">[?]</button>
    <div id="title_header">
        jotto
    </div>
    <div id="wrapper">
        <div id="col1" class="col">
            <form id="guess" action="single.php" method="post">
                Guess: <input id="guess_textbox" autofocus="autofocus" type="text" name="guess"><br>
                <!--input id="notes_textbox" type="textarea" name="notes"-->
                <input id="guess_submit" type="submit" value="Guess">
            </form>
            <button id="anagram" onclick="anagramify()" >&#8634;</button>
        </div>
        <div id="col2" class="col">
            <form id="reset_alphabet" action="single.php" method="post">
                <input type="hidden" name="resetalphabet" value="1"><br>
                <input type="submit" value="Reset Alphabet">
            </form>
        </div>
        <div id="col3" class="col">
            <form id="resign" action="single.php" method="post">
                <input type="hidden" name="resign" value="1"><br>
                <input type="submit" value="Give Up">
            </form>
        </div>
    </div>
    <div id="spacer"></div>
    <div id="phps">
        <?PHP
            $g = new Game();
            print_r($g->wrapper($request));
        ?>
    </div>

    <div id="rules_modal">
        <div class="transparency_cover"></div>
        <div id="rules_explanation">
            <div id="rules_header">
                <span id="close_modal" onclick="rules_modal()">
                    [X]
                </span>
                Rules
            </div>
            <div id="overflow_container">
                <span id="rules_text">
                    There is a secret word you must guess. The word is 5 letters long, has no repeated letters, and is in American English.<br/><br/>
                    In order to guess this word, you must submit words and you will be told how many letters in the secret word are present in the word that you guessed. Repeated letters are permitted.<br/><br/>
                    You may click the letters on the alphabet to keep track of what letters may be present in the secret word. black means you don't know whether it is present; <span class="absent">grey and struck-through</span> means you know it is absent; <span class="present">red</span> means you know it is present.<br/><br/>
                    Good luck!
                </span>        
            </div>
        </div>
    </div>
<div>
</body>