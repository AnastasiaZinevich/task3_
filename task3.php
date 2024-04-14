<?php

class MovesTable
{
    private $moves;
    private $table;

    public function __construct(array $moves)
    {
        $this->moves = $moves;
        $this->generateTable();
    }

    private function generateTable()
    {
        $count = count($this->moves);
        $this->table = array_fill(0, $count, array_fill(0, $count, ''));
        for ($i = 0; $i < $count; $i++) {
            for ($j = 0; $j < $count; $j++) {
                if ($i === $j) {
                    $this->table[$i][$j] = 'Draw';
                } elseif (($i + 1) % $count === $j) {
                    $this->table[$i][$j] = 'Win';
                } else {
                    $this->table[$i][$j] = 'Lose';
                }
            }
        }
    }

    public function getMoves()
    {
        return $this->moves;
    }

    public function getTable()
    {
        return $this->table;
    }
}

class Game
{
    private $movesTable;
    private $key;
    private $computerMove;

    public function __construct(MovesTable $movesTable)
    {
        $this->movesTable = $movesTable;
        $this->key = bin2hex(random_bytes(32));
        $this->computerMove = $this->getRandomMove();
    }

    private function getRandomMove()
    {
        $moves = $this->movesTable->getMoves();
        return $moves[array_rand($moves)];
    }    


    public function generateHMAC($move)
    {
        return hash_hmac('sha256', $move, $this->key);
    }

    public function getComputerMove()
    {
        return $this->computerMove;
    }

    public function getKey()
    {
        return $this->key;
    }

    public function play($userMove)
    {
        $moves = $this->movesTable->getMoves();
        $userIndex = array_search($userMove, $moves);
        $computerIndex = array_search($this->computerMove, $moves);

        $result = $this->movesTable->getTable()[$userIndex][$computerIndex];
        return $result;
    }
}

class GameHelper
{
    private $movesTable;

    public function __construct(MovesTable $movesTable)
    {
        $this->movesTable = $movesTable;
    }

    public function displayHelp()
    {
        $moves = $this->movesTable->getMoves();
        $table = $this->movesTable->getTable();

        $headerRow = array_merge(['v PC\User >'], $moves);
        $header = '| ' . implode(' | ', $headerRow) . ' |';
        $divider = str_repeat('-', strlen($header));
        
        echo $divider . PHP_EOL;
        echo $header . PHP_EOL;
        echo $divider . PHP_EOL;
        
        foreach ($moves as $index => $move) {
            $row = array_merge([$move], $table[$index]);
            $rowText = '| ' . implode(' | ', $row) . ' |';
            echo $rowText . PHP_EOL;
            echo $divider . PHP_EOL;
        }
    }
}

function displayMenu(array $moves)
{
    echo 'Available moves:' . PHP_EOL;
    foreach ($moves as $index => $move) {
        echo ($index + 1) . ' - ' . $move . PHP_EOL;
    }
    echo '0 - Exit' . PHP_EOL;
    echo '? - Help' . PHP_EOL;
}

function validateMoves(array $moves)
{
    $count = count($moves);
    if ($count % 2 === 0 || $count < 3) {
        echo 'Invalid number of moves. Please provide an odd number of unique moves (at least 3).' . PHP_EOL;
        exit(1);
    }
    if (count(array_unique($moves)) !== $count) {
        echo 'Moves must be unique.' . PHP_EOL;
        exit(1);
    }
}

function getUserMove(array $moves, $input)
{
    $index = intval($input) - 1;
    if (array_key_exists($index, $moves)) {
        return $moves[$index];
    }
    return false;
}

function runGame()
{
        $movesCount = 0;
    while ($movesCount < 2 || $movesCount % 2 == 0) {
        $movesCount = intval(readline('Enter the number of moves (more than 1, odd number): '));
        if ($movesCount < 2 || $movesCount % 2 == 0) {
            echo 'Invalid number of moves. Enter more than 1 and make sure it is an odd number.' . PHP_EOL;
        }
    }

    $moves = [];
    for ($i = 0; $i < $movesCount; $i++) {
        $move = readline('Enter a move: ');
        $move = str_replace(' ', '', $move);
        $move = substr($move, 0, 10);
        
        if (empty($move)) {
            echo 'Move cannot be empty. Please enter a valid move.' . PHP_EOL;
            $i--;
            continue;
        }
        
        if (in_array($move, $moves)) {
            echo 'Move "' . $move . '" has already been entered. Please enter a different move.' . PHP_EOL;
            $i--;
            continue;
        }
        
        $moves[] = $move;
    }

    $movesTable = new MovesTable($moves);
    $gameHelper = new GameHelper($movesTable);
    $game = new Game($movesTable);
    $gameTitle = str_replace(' ', '', implode('-', $moves));
    echo 'Welcome to ' . $gameTitle . '!' . PHP_EOL;
    echo 'Key: ' . $game->getKey() . PHP_EOL . PHP_EOL;

    while (true) {
        displayMenu($moves);
        $userInput = readline('Enter your choice: ');

        if ($userInput === '?') {
            $gameHelper->displayHelp();
            continue;
        }
        if ($userInput === '0') {
            echo 'Goodbye!';
            break;
        }
        $userMove = getUserMove($moves, $userInput); 
        if ($userMove === false) {
            echo 'Invalid move. Please try again.' . PHP_EOL;
            continue;
        }
        $hmac = $game->generateHMAC($userMove);
        echo 'Your move: ' . $userMove . PHP_EOL;
        echo 'Computer move: ' . $game->getComputerMove() . PHP_EOL;
        echo 'HMAC: ' . $hmac . PHP_EOL;
        $result = $game->play($userMove);
        echo 'Result: ' . $result . PHP_EOL;
        echo PHP_EOL;
    }
}

runGame();