<?PHP
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
    protected $opponents_word = '';
    protected $letter_list = array();
    protected $time_started = 0;
    protected $letters_guessed = '';
    protected $last_word = '';
    protected $hidden_word = '';
    
    function __construct ($player_name = NULL, $sid = NULL) {
        if ( ! isset($player_name)) {
            $this->name = (isset($player_name)) ? $player_name : $this->generateString();
        }
        $this->initLetterList();
        $this->chooseWord();
        $this->time_started = time();

        $ip = Utility::get_user_ip();
        error_log("New word for user @$ip: ". $this->opponents_word);
            
        $_SESSION['player_object'] = $this;
    }

	//Take the alphabet and break it into an array of [letter] => [knowledge state]
    function initLetterList() {
        $aa = str_split(static::$alphabet);
        $this->letter_list = array_fill_keys($aa, -1);
    }
    
	//Get a random word out of the list of 5-letter words
    function chooseWord() {
		$split_word = array();
		
		//Get words out of the word list file.
		$wordlist = $this->getWordList();
		//Choose a word from the list, and ensure it has no repeated letters.
		//We do this rather than pruning the list because it might be an option in a later version.
        while (count(array_unique($split_word)) != 5) {
			$word = (array_rand($wordlist));
			$word = $wordlist[$word];
			
			$split_word = str_split($word);
		}
		//Save the word we ended up with.
		$this->opponents_word = $word;
        return $word;
    }
	
	//Retrieve the word list from its file. 
	//Todo: Save to memcache.
	function getWordList() {
        $wordlist = file(__dir__ . '/words.txt', FILE_IGNORE_NEW_LINES);
        if ($wordlist === FALSE) {
            throw new Exception('words.txt missing');
        }
		return $wordlist;
	}
    
	function getGuessableList() {
        $wordlist = file(__dir__ . '/guessable.txt', FILE_IGNORE_NEW_LINES);
        if ($wordlist === FALSE) {
            throw new Exception('guessable.txt missing');
        }
		return $wordlist;
	}
    
	//Standard battery of setters and getters
    function getOpponentsWord() {	return $this->opponents_word;		}
    function getTurns() { return $this->turns; }	
    function getName() {	return $this->name;	}
    function getHistory() {	return json_decode($this->history, TRUE);	}
    function getLastWord() {    return $this->last_word;    }
    function getLetterList() {	return $this->letter_list;	}
    function getTimeStarted() {	return $this->time_started;	}
    function getLettersGuessed() {	return $this->letters_guessed;	}

    function setLetterList( $new ) {	$this->letter_list = $new;	}
    function setLastWord($last_word) {    $this->last_word = $last_word;    }
    
    //Save the letters in all of the words they've guessed in a string; to be compared later.
    function setLettersGuessed( $word ) {
        $letters_guessed = $this->letters_guessed;
        $letters = str_split($word);
        if (empty($letters)) {
            return;
        }        
        foreach ($letters as $num => $word_letter) {
            if (strpos($letters_guessed, $word_letter) === FALSE) {
                $this->letters_guessed .= $word_letter;
            }
        }
        return;
    }
	
    function incrementTurns() {
		$this->turns += 1;
        return $this->turns;
    }	

	//Take the word as it's guessed, do a last-minute check to compare the guess to the existing list, and toss it on the stack.
    function addHistory($word, $matching) {
		$h = array();
		if (!empty($this->history)) {
			$h = json_decode($this->history, TRUE);
		}
		if (array_key_exists($word, $h)) {
			$this->err_msg = 'That word has already been guessed.';
		}
		$this->incrementTurns();
        $this->last_word = $word;
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
    protected $last_turn = '';
    
	//All exceptions get caught and handled here.
	function wrapper( $request = NULL) {
		try {
			// if (isset($request['restart'])) {
				// session_destroy();
				// session_start();
			// }
			$response = $this->init( $request );
        } catch (Exception $e) {
            return ('EXCEPTION: ' . $e->getMessage());
        }
        if (!empty($this->last_turn)) {
            echo $this->last_turn;
        }
		return $response;
	}
	
	//Create or access an existing player, then perform all relevant business logic, and display the alphabet and history as needed.
    function init( $request ) {
		$name = (isset($request['name'])) ? $request['name'] : '';
		$p1 = isset($_SESSION['player_object']) ? $_SESSION['player_object'] : new Player( $name );
		$response = $this->delegateInput( $p1, $request );
		echo $this->displayAlphabet($p1);
		echo $this->displayHistory($p1, TRUE);
print_r($p1);
		return $response;
	}
	
	//Perform business logic as mandated by $_REQUEST vars.
	function delegateInput( $player, $request ) {
		if (isset($request['guess'])) {
			$response = $this->makeGuess( $player, $request );
            $this->lastTurn($player);
		} elseif (isset($request['letter'])) {
			$this->changeAlphabet($player, $request['letter'][0] );
			$response = '';
            $this->lastTurn($player);
		} elseif (isset($request['resetalphabet'])) {
			$player->initLetterList();
			$this->reZeroNulls($player);
			$response = 'Resetting alphabet state.';
            $this->lastTurn($player);
		} elseif (isset($request['resign'])) {
            $response = $this->userLoses($player);
		} else {
			$response = 'Enter a 5-letter word to begin.';
			//throw new Exception('Valid input missing: "guess" or "letter" required.');
		}
		return $response;
	}
    
    function userLoses($player) {
        session_destroy();
        session_start();
        $turns = $player->getTurns();
        $word = $player->getOpponentsWord();
        error_log("Resigned: User @".Utility::get_user_ip().": ". $word." after $turns turns.");
        return "Giving up. Word was <a href='https://www.google.com/search?q=define+$word'>$word</a>. Enter another word to start again.";
    }
	
    //Get all of the words guessed by the player; color-code them by letter status (yes, no, unknown).
    function displayHistory($player, $color_code = FALSE) {
        $output = '<br/>';
        $letter_list = $player->getLetterList();
		$history = $player->getHistory();
		
        if (sizeof($history) > 0) {
            $output = '<div class="history_wrapper">';
			$i = 0;
			foreach($history as $word => $correct) {
				if ($color_code == TRUE) {
					$ccword = '';
					$lword = str_split($word);
					foreach ($lword as $lk => $lv) {
						$status = $letter_list[$lv];
						$ccword .= '<span class="' . $this->getLetterStatus( $status ) . '">' . $lv . '</span>';
					}
					$word = $ccword;
				}
				$output .= '<span id="history_list_'.$i.'" class="history_list">' . $word . '</span>';
				$output .= '<span class="history_correct">' . $correct . '</span><br/>';
				$i++;
			}
            $output .= '</div>';
		}
		$notes_textbox = (21 * sizeof($history)) - 3.5;
		print_r("<style> #notes_textbox { height:$notes_textbox; } </style>");
		
		return $output;
	}
   
    //User inputs valid word that they're guessing; return number of correct characters.
	//If the word guessed is their opponent's actual word, they win.
    function makeGuess($player, $request) {
		$guess = $request['guess'];
		
		if (strlen($guess) != 5) {
			return "Your guess must be exactly 5 letters long.";
		}
		$wordlist = array_flip($player->getGuessableList());
		
		if ( ! isset($wordlist[$guess])) {
			return 'Retry with a real word.';
		}
		$guessed = array_flip(str_split(trim($guess)));
		$master_word = trim($player->getOpponentsWord());
		$word = array_flip(str_split($master_word));
		$letters = count(array_intersect_key($guessed, $word));
        try {
            $history = $player->addHistory($guess, $letters);
        } catch (Exception $e) {
            return $e->getMessage;
        }
		if ($letters == 5 && $master_word == $guess) {
			return $this->userWins($player, $word);
		}
        $player->setLettersGuessed( $guess );
		//If their word had 0 right, go ahead and toggle their alphabet off for them.
		if ($letters == 0) {
			foreach ($guessed as $letter => $i) {
				$this->changeAlphabet($player, $letter, 0);
			}
		//If their word had all the letters right, toggle all of the present letters as correct.
		} elseif ($letters == 5) {
			foreach ($guessed as $letter => $i) {
				$this->changeAlphabet($player, $letter, 1);
			}
		}
        return;
    }

    function lastTurn($player) {
		$turns = $player->getTurns();
        if ($turns == 0) {
            return '';
        }
		$last_word = $player->getLastWord();
		$this->last_turn = "Your last guess was: $last_word    ($turns turns)<br/>";
    }
    
	//The user has won; display a message and clean up.
	function userWins($player, $word) {
		$turns = $player->getTurns();
		session_destroy();
		$duration = time() - $player->getTimeStarted();
		$minutes = (int) ($duration / 60);
		$seconds = (int) ($duration % 60);
		return "You won! ($turns turns; ".$minutes."m".$seconds."s)";
		error_log("Won: User @".Utility::get_user_ip().": ". $word." in $turns turns.");
	}
	
    //Display their alphabet back to them, color-coded by letter status
    function displayAlphabet($player) {
		$output = '<span id="alphabet">';
        $letters_guessed = $player->getLettersGuessed($player);
		foreach($player->getLetterList() as $letter => $status) {
			$letter_status = $this->getLetterStatus($status);
            $was_guessed = (strpos($letters_guessed, $letter) === FALSE) ? '' : 'was_guessed';
            $classes = trim("letter_list $letter_status $was_guessed");
			$output .= "<a href='single.php?letter=$letter'><span class='".$classes."'>$letter</span></a>";
		}
        $output .= '</span>';
		return $output;
    }
    
	
	//Simple translation function.
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
	
	function reZeroNulls($player) {
		$h = $player->getHistory();
		if (empty($h)) {
			return;
		}
		foreach ($h as $word => $correct) {
			if ($correct > 0) {
				continue;
			}
			foreach (str_split($word) as $letter => $ignore) {
				$this->changeAlphabet($player, $ignore, 0);
			}
		}
	}
    
    function debug($line) {
        //echo 'line: #'.$line."\n";
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
		foreach ($ips as $ip) { //for all the ips, work on it one-by-one based on patterns given down here
			if (!preg_match("/^([0-9]+\.[0-9]+\.[0-9]+\.[0-9]+)/",$ip)) {
				//if it doesn't match the pattern, skip
				continue;
			}
			if( ! ip2long($ip) ) {
				continue;
			}
			
			$result = $ip;
		}
		if ($result===false) {
			$result = $fallback; //if fallback is not found, it will be false
		}
		if( $result === false ) {
			$result = '0.0.0.0';
		}
		return $USER_IP = $result; //if all resources are exhausted and not found, return false.
	}
}
