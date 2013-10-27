jotto
=====
Jotto is a puzzle game for one player, in this implementation.

The server chooses a 5-letter word from a preset list that the player must
guess, by submitting their own 5-letter words. With every guess, the server
will tell them how many matching letters they have. The chosen word will have
no repeated letters, but the words guessed may. There are no proper nouns, and
all of the words guessed must be present on the same list that the chosen word
comes from.

This is very quickly thrown together, providing some sample code. This is one
of my first excursions into vanilla PHP; I'm going to expand functionality to
an ORM, and plan to make this be an async 2-player game.
