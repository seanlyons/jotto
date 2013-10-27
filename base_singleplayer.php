<style>
	#form_wrapper {
		position:absolute;
		height:100px;
		background:orange;
	}
	#guess {
		left:10px;
		position:absolute;
		top:10px;		
	}
	#restart_game {
		left:410px;
		position:absolute;
		top:32px;		
	}
	#reset_alphabet {
		left:210px;
		position:absolute;
		top:32px;		
	}
	#phps {
		position:absolute;
		top:100px;
	}
		
	.letter_list {	margin-left:10px; font-family:"Courier New", Courier, monospace; }
	.unknown {	color:black !important;		}
	.present {	color:red !important;	}
	.absent	 {	color:#BBB !important;	text-decoration:line-through; }
	.history_list {	color:blue;	font-size:18;	}
	.history_correct {	color:orange;	position:absolute;	left:200px; }
	.history_wrapper { font-family:"Courier New", Courier, monospace; } 
	
	a:link {text-decoration:none;}
	a:visited {text-decoration:none;}
	a:hover {text-decoration:underline;}
	a:active {text-decoration:underline;}
</style>
<div id="form_wrapper">
	<form id="guess" action="single.php" method="post">
		Guess: <input type="text" name="guess"><br>
		<input type="submit">
	</form>
	<form id="restart_game" action="single.php" method="post">
		<input type="hidden" name="restart" value="1"><br>
		<input type="submit" value="Restart Game">
	</form>
	<form id="reset_alphabet" action="single.php" method="post">
		<input type="hidden" name="resetalphabet" value="1"><br>
		<input type="submit" value="Reset Alphabet">
	</form>
</div>
<br/><br/><br/>
<div id="phps">
<?PHP
$request = sanitizeRequest($_REQUEST);
session_start();
$g = new Game();
print_r($g->wrapper($request));

//This is slower than htmlentities() or htmlspecialchars(), but we actually want a subset of that functionality.
function sanitizeRequest($r) {
	$request = array();
	foreach ($r as $k => $v) {
		$k = preg_replace("/[^A-Za-z]/", '', $k );
		$request[ strtolower($k) ] = strtolower($v);
		$v = preg_replace("/[^A-Za-z]/", '', $v );
	}
	return $request;
}

class Player {
    protected static $alphabet = 'abcdefghijklmnopqrstuvwxyz';
    protected $name = 'NAME';
    protected $turns = 0;
    protected $history = '';
	protected $word = '';
	protected $letter_list = array();
    
    function __construct ($player_name = NULL, $sid = NULL) {
        if ( ! isset($player_name)) {
            $this->name = (isset($player_name)) ? $player_name : $this->generateString();
        }
        $this->initLetterList();
		$this->chooseWord();

		$ip = Utility::get_user_ip();
		error_log("New word for user @$ip: ". $this->word);
		
		$_SESSION['player_object'] = $this;
    }

	//Get a random word out of the list of 5-letter non-proper words
    function chooseWord() {
		$split_word = array();
		
		$wordlist = $this->getWordList();
		//This ensures that the chosen random word has no repeated letters; we do this rather than pruning the list because it might be an option in a later version.
        while (count(array_unique($split_word)) != 5) {
			$word = (array_rand($wordlist));
			$word = $wordlist[$word];
			
			$split_word = str_split($word);
		}
		$this->word = $word;
        return $word;
    }
	
	function getWordList() {
        $wordlist = file(__dir__ . '/words.txt', FILE_IGNORE_NEW_LINES);
        if ($wordlist === FALSE) {
            throw new Exception('words.txt missing');
        }
		return $wordlist;
	}

    function initLetterList() {
        $aa = str_split(static::$alphabet);
        $this->letter_list = array_fill_keys($aa, -1);
        //Insert db row <- $this->name, json_encode($alphabet_states);
    }
    
    function getWord() {	return $this->word;		}
    function getTurns() { return $this->turns; }	
    function getName() {	return $this->name;	}
    function getHistory() {	return json_decode($this->history, TRUE);	}
    function getLetterList() {	return $this->letter_list;	}
    function setLetterList( $new ) {	$this->letter_list = $new;	}
	
    function incrementTurns() {
		$this->turns += 1;
        return $this->turns;
    }	

    
    function addHistory($word, $matching) {
		$h = array();
		if (!empty($this->history)) {
			$h = json_decode($this->history, TRUE);
		}
		if (array_key_exists($word, $h)) {
			throw new Exception('That word has already been guessed.');
		}
		$this->incrementTurns();
		$h[$word] = $matching;
		$this->history = json_encode($h);
        return $this->history;
    }
    
    //Generate a string from random characters; this is not optimized for anything like pronounceability.
    function generateString($max = 10) {
        $name = '';
        $length = strlen(static::$alphabet) - 1;

        for( $i = 0; $i < $max; $i++ ) {
            $name .= static::$alphabet[ mt_rand(0, $length) ];
        }
        return ucfirst($name);
    }
}

class Game {
    protected static $alphabet = 'abcdefghijklmnopqrstuvwxyz';
    
	//All exceptions get caught and handled here.
	function wrapper( $request = NULL) {
		try {
			if (isset($request['restart'])) {
				session_destroy();
				session_start();
			}		
			$response = $this->init( $request );
        } catch (Exception $e) {
            return ('EXCEPTION: ' . $e->getMessage());
        }
		return $response;
	}
	
	//Create player, choose a word, init turn, alphabet, guess.
    function init( $request ) {
		$name = (isset($request['name'])) ? $request['name'] : '';
		$p1 = isset($_SESSION['player_object']) ? $_SESSION['player_object'] : new Player( $name );
		//print_r($p1);
		$response = $this->delegateInput( $p1, $request );
		echo $this->displayAlphabet($p1);
		echo $this->displayHistory($p1, TRUE);
		
		return $response;
	}

	function delegateInput( $player, $request ) {
		if (isset($request['guess'])) {
			$response = $this->makeGuess( $player, $request );
		} elseif (isset($request['letter'])) {
			$this->changeAlphabet($player, $request['letter'][0] );
			$response = '';
		} elseif (isset($request['resetalphabet'])) {
			$player->initLetterList();
			$response = 'Resetting alphabet state.';
		} else {
			throw new Exception('Valid input missing: "guess" or "letter" required.');
		}
		return $response;
	}
	
	
    //Get all of the words guessed by the player; color-code them by letter status (yes, no, unknown)
    function displayHistory($player, $color_code = FALSE) {
		$letter_list = $player->getLetterList();
		$output = '<div class="history_wrapper">';
		foreach($player->getHistory() as $word => $correct) {
			if ($color_code == TRUE) {
				$ccword = '';
				$lword = str_split($word);
				foreach ($lword as $lk => $lv) {
					$status = $letter_list[$lv];
					$ccword .= '<span class="' . $this->getLetterStatus( $status ) . '">' . $lv . '</span>';
				}
				$word = $ccword;
			}
			$output .= '<span class="history_list">' . $word . '</span>';
			$output .= '<span class="history_correct">' . $correct . '</span><br/>';
		}
		$output .= '</div>';
		return $output;
	}
   
    //User inputs word that they're guessing; validate that it's their turn, that it's a real word; return number of correct characters. If the word guessed is their opponent's actual word, they win.
    function makeGuess($player, $request) {
		$guess = $request['guess'];
		
		if (strlen($guess) != 5) {
			throw new Exception("Your guess must be exactly 5 letters long.");
		}
		$wordlist = array_flip($player->getWordList());
		
		if ( ! isset($wordlist[$guess])) {
			throw new Exception('Retry with a real word.');
		}
		$guessed = array_flip(str_split(trim($guess)));
		$master_word = trim($player->getWord());
		$word = array_flip(str_split($master_word));
		$letters = count(array_intersect_key($guessed, $word));
		$history = $player->addHistory($guess, $letters);
		
		if ($letters == 5 && $master_word == $guess) {
			$turns = $player->getTurns();
			session_destroy();
			return "You won! ($turns turns)";
			error_log("Won: User @".Utility::get_user_ip().": ". $this->word." in $turns turns.");
		}
		//If their word had 0 right, go ahead and toggle their alphabet off for them.
		if ($letters == 0) {
			foreach ($guessed as $letter => $i) {
				$this->changeAlphabet($player, $letter, 0);
			}
		} elseif ($letters == 5) {
			foreach ($guessed as $letter => $i) {
				$this->changeAlphabet($player, $letter, 1);
			}
		}
		$turns = $player->getTurns();
		return 'You guessed: '.$guess."    ($turns turns)";
    }
    
    //Display their alphabet back to them, color-coded by letter status
    function displayAlphabet($player) {
		$output = '';
		foreach($player->getLetterList() as $letter => $status) {
			$output .= '<span class="letter_list ';
			$output .= $this->getLetterStatus($status);
			$output .= '"><a href="single.php?letter='.$letter.'">' . $letter . '</a></span>';
		}
		return $output;
    }
	
	function getLetterStatus($status) {
		switch($status) {
			case -1:
				return 'unknown';
			case 0:
				return 'absent';
			case 1:
				return 'present';
		}
		throw new Exception ('Invalid status code passed to ' . __class__);
	}

    //Cycle through letter status
    function changeAlphabet($player, $letter, $change_to = NULL) {
		$letter_list = $player->getLetterList();
		if ($change_to === NULL) {
			if ($letter_list[$letter] >= 1) {
				$letter_list[$letter] = -1;
			} else {
				$letter_list[$letter]++;
			}
		} else {
			$letter_list[$letter] = $change_to;
		}
		$player->setLetterList( $letter_list );
		return $letter_list;
    }
}

class Utility {
	function get_user_ip()
	{
		static $USER_IP;
		
		$result = FALSE;
		$fallback = false;

		//fill the array with candidates IP from various resources
		$ips = isset( $_SERVER['HTTP_X_FORWARDED_FOR'])  ? explode(',',$_SERVER['HTTP_X_FORWARDED_FOR']) : array();
		foreach( $ips as $i => $ip ){
			$ip = trim( $ip );
			$ips[ $i ] = $ip;
			if( ! ip2long( $ip ) ) {
				unset( $ips[ $i ]);
			}
		}
		if( empty( $ips ) ){
			if( isset( $_SERVER['REMOTE_ADDR'] ) ) {
				$ips[]=$_SERVER['REMOTE_ADDR'];
			}
			if( isset( $_SERVER['HTTP_CLIENT_IP'] ) ) {
				$ips[]=$_SERVER["HTTP_CLIENT_IP"];
			}
		}
		foreach ($ips as $ip) { //for all the ips, work on it one by one based on patterns given down here
			if (!preg_match("/^([0-9]+\.[0-9]+\.[0-9]+\.[0-9]+)/",$ip)) {
				//if it doesn't  match the pattern then skip
				continue;
			}
			if( ! ip2long($ip) ) {
				// in php5.3, this is same as line above.
				continue;
			}
			
			$result = $ip;
		}
		if ($result===false) {
			$result = $fallback; //if fallback is not found it will be false
		}
		if( $result === false ) {
			$result = '0.0.0.0';
		}
		return $USER_IP = $result; //if all resources are exhausted and not found, return false.
	}
	
	function print_pre($args) {
		// output the arrays for testing / viewing. reset arrays
		// after viewing. will take up to 5 incoming arrays..

		$i = 0;
		ob_start();
		foreach ($args as $argument) {
			if ($i > 0) {
				echo "\n-----------------------------------------\n\n";
			}
			if (is_array($argument) || is_object($argument)) {
				print_r($argument);
			} else {
				var_dump($argument);
			}
			$i++;
		}
		echo '<div align="left"><pre>' . htmlentities(ob_get_clean(), ENT_QUOTES, 'UTF-8') . '</pre></div>';
	}
}


?>
</div>