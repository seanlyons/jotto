<?PHP
class Player {
    static $alphabet = 'abcdefghijklmnopqrstuvwxyz';

    function __construct ($player_name, $word, $history)
        if (empty($player_name)) {
            $player_name = $this->generateString();
        }
        
    }
    
    //Generate a string from random characters; this is not optimized for anything like pronounceability.
    function generateString($max = 10) {
        $name = '';
        $length = strlen(static::$alphabet) - 1;
        
        for( $i = 0; $i < $max; $i++ ) {
            $name .= static::$alphabet[mt_rand($length)];
        }
        return $name;
    }
    
}

class Game {
    static $alphabet = 'abcdefghijklmnopqrstuvwxyz';
    //Values:   'waiting_room'
    //          'submit_words'
    //          'waiting_guess'
    //          ''
    //          ''
    //          ''
    $state = 'waiting';
    
    //Waiting room where players go before their opponent has connected.
    function waitingRoom($player_name) {
    	
	//All exceptions get caught and handled here.
	function wrapper($p1name, $p2name) {
		try {
			$response = $this->init($p1name, $p2name);
        } catch (Exception $e) {
            return array('error' => $e->message);
        }
		return $response;
	}
	
	//Create 2 players, initialize words, turns, alphabets, guesses. return Success.
    function init($p1name, $p2name) {
        $p1 = new Player($p1name);
        $p2 = new Player($p2name);
        //new db entry for p1; PK = hash of IP + name.
        //new db entry for p2; PK = hash of IP + name.
        
    }
    //set Player's word to input; validate 5 letters + no repeats + call validateWordRealness()
    function submitWord($player, $word) {
        //Throw an error if the word is not 5 letters
        //Throw an error if the word has any repeated letters
        //Return a warning if validateWordRealness returns false
        //Write their word to their player row; check to see if the other player  
        
        return;
    }
    
    //
    function confirmWordSubmission($player, $word) {
        //Throw an error if they have not submitted a word yet.
        //Check to see if other player has submitted
        //If other player has submitted, set game status to "waiting_guess"
    }  
    //Check dict + google to ensure this word is actually a word
    function validateWordRealness($word) {
        //$valid = cat words.txt | grep 'yearx' | wc -l
        //if 1, return true
        //TODO if false, curl google; if results are above some threshold, return 1.
        //return false;
    }

    //Get all of the words guessed by the player; color-code them by letter status (yes, no, unknown)
    function getHistory($player, $color_code = FALSE) {
        //get player's alphabet from db as associative array, letter => status
        //get player's history from db as associative array, word => #matches
        //if ($color_code == true) {
        //      foreach across their history, foreach across the letters in each word, color-code/strike-out each letter as necessary
        // }
        //return associative array of word => #matches
    }
   
    //User inputs word that they're guessing; validate that it's their turn, that it's a real word; return number of correct characters. If the word guessed is their opponent's actual word, they win.
    function addWord($player, $guess) {
        //Throw an error if they've already guessed this turn
        //Throw an error if validateWordRealness returns false
        //Get opponent's word and explode on ''
        //explode $guess on ''
        //$matches = array_intersect_key($guess, $word);
        //Add $guess => $matches to $player's db
        //if ($matches == 5 && $guess == $word) {
        //      set game_status to winner
        //      set winner to $player
        //      return
        //}
        //return $matches
    }
    
    //Display their alphabet back to them, color-coded by letter status
    function displayAlphabet($player) {
        //get player's delta alphabet from db as associative array, letter => status
        //return alphabet
    }

    //Cycle through letter status
    function changeAlphabet($player, $letter, $status) {
        //get player's alphabet from db as associative array, letter => status
        //alphabet[$letter] = $status
        //save player's alphabet
    }
}
?>