<?php

require_once 'vendor/autoload.php';

use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Helper\TableSeparator;
use Symfony\Component\Console\Output\ConsoleOutput;

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

        $rows = [['v PC\User >']];
        foreach ($moves as $move) {
            $rows[0][] = $move;
        }
        $rows[] = new TableSeparator();

        foreach ($moves as $index => $move) {
            $row = [$move];
            foreach ($table[$index] as $result) {
                $row[] = $result;
            }
            $rows[] = $row;
        }

        $output = new ConsoleOutput();
        $table = new Table($output);
        $table->setRows($rows);
        $table->render();
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
    if ($count === 0) {
        echo 'No moves provided. Please provide at least 3 move.' . PHP_EOL;
        exit(1);
    }
    foreach ($moves as $move) {
        if (empty($move)) {
            echo 'Moves cannot be empty. Please provide non-empty moves.' . PHP_EOL;
            exit(1);
        }
    }
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

function runGame(array $moves)
{
    $movesCount = count($moves);
    validateMoves($moves);

    $movesTable = new MovesTable($moves);
    $gameHelper = new GameHelper($movesTable);
    $game = new Game($movesTable);

    while (true) {
        $hmac = $game->generateHMAC($game->getComputerMove());
        echo 'HMAC: ' . $hmac . PHP_EOL;

        displayMenu($moves);
        echo 'Enter your move: ';
        $input = trim(fgets(STDIN));

        if ($input === '?') {
            $gameHelper->displayHelp();
            continue;
        }

        if ($input === '0') {
            echo 'Exiting the game. Goodbye!' . PHP_EOL;
            break;
        }

        $userMove = getUserMove($moves, $input);
        if ($userMove === false) {
            echo 'Invalid move. Please enter a valid move number.' . PHP_EOL;
            continue;
        }

        echo 'Your move: ' . $userMove . PHP_EOL;

        $computerMove = $game->getComputerMove();
        echo 'Computer move: ' . $computerMove . PHP_EOL;

        $result = $game->play($userMove);
        echo 'Result: ' . $result . PHP_EOL;
        echo 'Key: ' . $game->getKey() . PHP_EOL;
    }
}

$moves = ['Rock', 'Paper', 'Scissors'];
runGame($moves);

