# 3D Backgammon

A browser-based 3D backgammon game built with **JavaScript and Three.js**, with a **PHP MVC backend** and **MySQL** for online game rooms.

The frontend is responsible for rendering the board, animations, sound, and player interactions. The PHP backend provides the API for creating and joining online rooms, rolling dice, validating moves, and saving the game state.

## Features

- 3D backgammon board rendered with Three.js
- Interactive checker selection and movement
- Dice rolling, move highlights, and game animations
- Local gameplay
- Play against a computer opponent using a heuristic move-scoring algorithm
- PHP API structured with the MVC pattern
- MySQL storage for online rooms and game states
- Room-based online multiplayer using HTTP requests and periodic state polling

## Tech Stack

| Part | Technologies |
|---|---|
| Frontend | HTML, CSS, JavaScript, Three.js, jQuery |
| Backend | PHP 8.1+, PDO |
| Database | MySQL |
| Communication | Fetch API, JSON |
| Architecture | MVC |

## Project Structure

```text
Backgammon/
├── app/
│   ├── Controllers/
│   │   └── GameController.php
│   ├── Core/
│   │   ├── Database.php
│   │   ├── Response.php
│   │   └── Router.php
│   ├── Models/
│   │   └── Game.php
│   └── Services/
│       └── BackgammonEngine.php
├── config/
│   └── config.php
├── database/
│   └── schema.sql
├── public/
│   ├── assets/
│   ├── game.html
│   ├── index.php
│   ├── router.php
│   └── .htaccess
├── routes/
│   └── api.php
├── composer.json
└── README.md
```

## How the Game Logic Works

The game state contains:

- The 24 board points and the checkers on each point
- The number of checkers on the bar for each player
- The number of checkers borne off
- The current player's turn
- The dice values and remaining moves
- The winner, if the game has ended

In online mode, the PHP game engine is responsible for generating dice values and validating submitted moves. The server stores the updated state in MySQL and returns it as JSON. The JavaScript frontend then updates the 3D board to match the server state.

## Computer Opponent

The computer opponent currently uses a **heuristic scoring approach**. It evaluates the available legal moves and selects the move with the highest score.

The scoring function considers factors such as:

- Entering a checker from the bar
- Bearing a checker off the board
- Hitting an opponent's lone checker
- Making or strengthening a point with multiple checkers
- Leaving a lone checker exposed
- A small random factor to avoid completely repetitive choices

This is a lightweight decision-making strategy, not a neural network or a full Minimax search.

## Online Multiplayer

Online games use a room code:

1. One player creates a room and receives a room code.
2. The second player joins using that code.
3. Both browsers send requests to the PHP API.
4. PHP generates dice rolls and validates moves.
5. MySQL stores the room and current game state.
6. The browsers periodically request the latest state and update their boards.

The current synchronization approach uses **polling** (periodic HTTP requests). A WebSocket-based solution could be added later for lower-latency updates.

## Installation

### Requirements

- PHP 8.1 or later
- MySQL
- Composer
- Apache or another PHP-compatible web server
- PDO MySQL extension enabled

### Setup with XAMPP

1. Clone or copy this repository into your XAMPP `htdocs` directory.
2. Start Apache and MySQL from the XAMPP Control Panel.
3. Open phpMyAdmin.
4. Import `database/schema.sql` to create the `backgammon` database and its table.
5. Check the database credentials in `config/config.php`. The default configuration is intended for a typical local XAMPP setup (`root` with an empty password).
6. Open a terminal in the project directory and run:

   ```bash
   composer install
   composer dump-autoload
   ```

7. Open the game in your browser:

   ```text
   http://localhost/Backgammon/public/game.html
   ```

> If you use a different folder name, update the URL accordingly.

## Playing Online

For two players on the same computer, open the game in two separate browser windows and use the room code to join the same room.

For players on different devices or networks, deploy the project to a publicly accessible server with PHP and MySQL. Both players must access the same website URL. `localhost` is only accessible from the local computer.

Use HTTPS when deploying the game publicly.

## API Endpoints

The backend exposes JSON endpoints using POST requests:

| Endpoint | Purpose |
|---|---|
| `api/rooms/create` | Create a new game room |
| `api/rooms/join` | Join an existing room |
| `api/game/state` | Get the current game state |
| `api/game/roll` | Roll the dice |
| `api/game/move` | Submit a checker move |

## Future Improvements

- WebSocket support for real-time multiplayer updates
- Stronger computer opponent using Minimax and search depth
- User accounts and player profiles
- Match history and statistics
- Room expiration and cleanup
- Additional server-side validation and concurrency protection
- More comprehensive testing of all official backgammon rules

## Project Status

This project is under development. The 3D JavaScript frontend is retained, while the PHP/MySQL backend provides the foundation for server-managed online games. Test the backend and online flow in your own PHP/MySQL environment before deploying it publicly.
